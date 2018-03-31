<?php
/*
chdir('includes/plugin_encrypt/phpseclib/');
set_time_limit(120);
require_once('Crypt/RSA.php');
$rsa = new Crypt_RSA();
$rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_RAW);
$key = $rsa->createKey(1024);
echo $key['privatekey'];
echo ' <br><br> ';
$e = new Math_BigInteger($key['publickey']['e'], 10);
$n = new Math_BigInteger($key['publickey']['n'], 10);
echo $e->toHex();
echo ' <br><br> ';
echo $n->toHex();
echo ' <br><br> ';
*/

// are we creating a new encrypution value or trying to decrypt an existing one?

$encrypt_field_id = isset( $_REQUEST['encrypt_field_id'] ) ? (int) $_REQUEST['encrypt_field_id'] : false;
if ( ! $encrypt_field_id && module_encrypt::can_i( 'create', 'Encrypts' ) ) {
	// are we creating a new encryption for this field??
	// ooooooooooooooooo.
	$encrypt_field_name = isset( $_REQUEST['encrypt_field_name'] ) ? $_REQUEST['encrypt_field_name'] : false;
	$page_name          = isset( $_REQUEST['page_name'] ) ? $_REQUEST['page_name'] : false;
	if ( ! $encrypt_field_name || ! $page_name ) {
		die( 'Unable to encrypt this field. Sorry' );
	}
	// ready to create our field!
	// for now we just create an entry in the db ready to go.
	$encrypt_field_id = update_insert( 'encrypt_field_id', 0, 'encrypt_field', array(
		'page_name'      => $page_name,
		'field_name'     => $encrypt_field_name,
		'encrypt_key_id' => 0,
	) );
}
if ( ! $encrypt_field_id ) {
	die( 'no encrypt field id' );
}
$encrypt_field = module_encrypt::get_encrypt_field( $encrypt_field_id );
//if(!$encrypt_field||$encrypt_field['encrypt_field_id']!=$encrypt_field_id)die('invalid field specified');

$callback_id = isset( $_REQUEST['callback_id'] ) ? $_REQUEST['callback_id'] : '';


$encrypt_id      = isset( $_REQUEST['encrypt_id'] ) ? (int) $_REQUEST['encrypt_id'] : 0;
$existing_value  = isset( $_REQUEST['value'] ) ? html_entity_decode( @base64_decode( $_REQUEST['value'] ) ) : '';
$encrypt         = module_encrypt::get_encrypt( $encrypt_id );
$encryption_keys = module_encrypt::get_encrypt_keys();
if ( $encrypt && $encrypt['encrypt_key_id'] && isset( $encryption_keys[ $encrypt['encrypt_key_id'] ] ) ) {
	$encryption_key = $encryption_keys[ $encrypt['encrypt_key_id'] ];
} else {
	$encryption_key = isset( $encryption_keys[ $encrypt_field['encrypt_key_id'] ] ) ? $encryption_keys[ $encrypt_field['encrypt_key_id'] ] : false;
}

// if there is no "encrypt_id" it means we're adding a new encrypted value here.
// there is no need to ask for the password if we're adding a new value to the system.
// we can encrypt this value with the public key.
$adding_new_blank_field = ! ( $encrypt_id > 0 );

if ( $encrypt_id ) {
	// log an "attempt" status against this field.
	module_encrypt::log_access( $encrypt_id, 'attempt' );
}

// we will have our "secured_private_key" within the encryption keys array.
// this is encrypted with AES using our "master" password.
// we unlock this private key using our "master" password that is entered in the do_crypt() function.
// if this do_crypt() function successfully unlocks the "secured_private_key" we can

// if we're encrypting a new value then we can simply encrypt it against the free text public key.

?>

	<script language="JavaScript" type="text/javascript"
	        src="<?php echo full_link( 'includes/plugin_encrypt/js/json2.js' ); ?>?ver=<?php echo _SCRIPT_VERSION; ?>"></script>
	<script language="JavaScript" type="text/javascript"
	        src="<?php echo full_link( 'includes/plugin_encrypt/js/jsbn.js' ); ?>?ver=<?php echo _SCRIPT_VERSION; ?>"></script>
	<script language="JavaScript" type="text/javascript"
	        src="<?php echo full_link( 'includes/plugin_encrypt/js/jsbn2.js' ); ?>?ver=<?php echo _SCRIPT_VERSION; ?>"></script>
	<script language="JavaScript" type="text/javascript"
	        src="<?php echo full_link( 'includes/plugin_encrypt/js/prng4.js' ); ?>?ver=<?php echo _SCRIPT_VERSION; ?>"></script>
	<script language="JavaScript" type="text/javascript"
	        src="<?php echo full_link( 'includes/plugin_encrypt/js/rng.js' ); ?>?ver=<?php echo _SCRIPT_VERSION; ?>"></script>
	<script language="JavaScript" type="text/javascript"
	        src="<?php echo full_link( 'includes/plugin_encrypt/js/rsa.js' ); ?>?ver=<?php echo _SCRIPT_VERSION; ?>"></script>
	<script language="JavaScript" type="text/javascript"
	        src="<?php echo full_link( 'includes/plugin_encrypt/js/rsa2.js' ); ?>?ver=<?php echo _SCRIPT_VERSION; ?>"></script>
	<script type="text/javascript">
      var existing_encrypt_keys = {};
			<?php foreach($encryption_keys as $existing_encryption_key){ ?>
      existing_encrypt_keys[<?php echo $existing_encryption_key['encrypt_key_id'];?>] = {
          'public_key': '<?php echo $existing_encryption_key['public_key'];?>',
          'secured_private_key': '<?php echo $existing_encryption_key['secured_private_key'];?>',
          'e': '<?php echo $existing_encryption_key['e'];?>'
      };
			<?php } ?>
      var rsa2 = {
          e: '<?php echo isset( $encryption_key['e'] ) && strlen( $encryption_key['e'] ) ? $encryption_key['e'] : '010001';?>', // 010001 (65537 was the old value, this was bad!)
          bits: 1024,
          public_key: '<?php echo isset( $encryption_key['public_key'] ) ? $encryption_key['public_key'] : '';?>',
          private_key: {},
          private_encrypted: '<?php echo isset( $encryption_key['secured_private_key'] ) ? $encryption_key['secured_private_key'] : '';?>', // this is what we get from our server.
          generate: function (passphrase) {
              // we generate a brand new key when creating a new encryption.
              var rsakey = new RSAKey();
              var dr = document.rsatest;
              rsakey.generate(parseInt(this.bits), this.e);
              this.public_key = rsakey.n.toString(16);
              //console.debug(this.public_key);
              this.private_key.d = rsakey.d.toString(16);
              this.private_key.p = rsakey.p.toString(16);
              this.private_key.q = rsakey.q.toString(16);
              this.private_key.dmp1 = rsakey.dmp1.toString(16);
              this.private_key.dmq1 = rsakey.dmq1.toString(16);
              this.private_key.coeff = rsakey.coeff.toString(16);
              var private_string = JSON.stringify(this.private_key);
              this.private_key = {};
              // encrypt this private key with our password?
              this.private_encrypted = sjcl.encrypt(passphrase, private_string);
              //console.debug(this.private_encrypted);
          },
          decrypt_private_key: function (passphrase) {
              try {
                  var p = sjcl.decrypt(passphrase, this.private_encrypted);
                  if (p) {
                      var j = JSON.parse(p);
                      if (j) {
                          this.private_key = j;
                          //console.debug(this.private_key);
                          return true;
                      }
                  }
              } catch (e) {
              }
              return false;
          },
          encrypt: function (value) {
              var rsakey = new RSAKey();
              rsakey.setPublic(this.public_key, this.e);
              return rsakey.encrypt(value);
          },
          decrypt: function (ciphertext) {
              var rsakey = new RSAKey();
              //console.log(this.public_key);
              //console.log(this.e);
              //console.log(this.private_key.d);
              //console.log(this.private_key.p);
              //console.log(this.private_key.q);
              //console.log(this.private_key.dmp1);
              //console.log(this.private_key.dmq1);
              //console.log(this.private_key.coeff);
              rsakey.setPrivateEx(this.public_key, this.e, this.private_key.d, this.private_key.p, this.private_key.q, this.private_key.dmp1, this.private_key.dmq1, this.private_key.coeff);
              return rsakey.decrypt(ciphertext);
          }
      };
      var do_crypt_success = false;

      function do_crypt(passphrase) {
          if (do_crypt_success) return;
          // decrypt our private key from the string.
          if (rsa2.decrypt_private_key(passphrase)) {
              do_crypt_success = true;
              var raw = '<?php echo $encrypt['data'];?>';
              var decrypt = rsa2.decrypt(raw);
              //alert($('#decrypted_value').val().length);
              $('#password_box').hide();
              $('#unlocking_box').show();
              // do an ajax post to tell our logging that we successfully unlocked this entry.
              // this is not fool proof. turn off your internet after unlocked the password will get around this.
              // but it's a start.
              $.ajax({
                  type: 'GET',
                  url: '<?php echo $plugins['encrypt']->link( 'note_admin', array(
										'_process'         => 'encrypt_successful',
										'encrypt_field_id' => $encrypt_field_id,
										'encrypt_id'       => $encrypt_id,
									) );?>',
                  success: function () {
                      $('#unlocking_box').hide();
                      if (decrypt && decrypt.length > 0) {
                          $('#decrypted_value').val(decrypt);
                      }
                      $('#decrypt_box').show();
                      $('#decrypted_value')[0].focus();
                  },
                  fail: function () {
                      alert('Decryption failed. Refresh and try again.');
                  }
              });
          }
      }

      function do_save_decrypted() {
          $('#<?php echo htmlspecialchars( $callback_id );?>').val($('#decrypted_value').val());
          $('#<?php echo htmlspecialchars( $callback_id );?>')[0].form.submit();
      }

      function do_save() {
          var enc = rsa2.encrypt($('#decrypted_value').val());
          if (enc) {
              $.ajax({
                  type: 'POST',
                  url: '<?php echo $plugins['encrypt']->link( 'note_admin', array(
										'_process'         => 'save_encrypt',
										'encrypt_field_id' => $encrypt_field_id,
										'encrypt_id'       => $encrypt_id,
									) );?>',
                  data: {
                      encrypt_key_id: $('#encrypt_key_id').val(),
                      data: enc
                  },
                  dataType: 'json',
                  success: function (h) {
                      // update our hidden field back in the other page.
                      $('#<?php echo htmlspecialchars( $callback_id );?>').val('encrypt:' + h.encrypt_id);
                      //alert('<?php _e( 'Encrypted successfully! Saving...' );?>');
                      $('#<?php echo htmlspecialchars( $callback_id );?>')[0].form.submit();
                  },
                  fail: function () {
                      alert('Something went wrong');
                  }
              });
          }
      }

      function create_new() {
          var password = $('#new_passphrase').val();
          var name = $('#encrypt_key_name').val();
          if (name.length > 1 && password.length > 1) {
              rsa2.generate(password);
              // post this to our server so we can save it in the db.
              if (rsa2.public_key.length > 2 && rsa2.private_encrypted.length > 5) {
                  // it worked.
                  $.ajax({
                      type: 'POST',
                      url: '<?php echo $plugins['encrypt']->link( 'note_admin', array(
												'_process'         => 'save_encrypt_key',
												'encrypt_field_id' => $encrypt_field_id,
											) );?>',
                      data: {
                          encrypt_key_id: 0,
                          encrypt_key_name: name,
                          public_key: rsa2.public_key,
                          secured_private_key: rsa2.private_encrypted,
                          e: rsa2.e
                      },
                      success: function (h) {
                          //alert(h);
                          //alert('<?php _e( 'Created successfully!' );?>');
                          $('#env_vault_name').html(name);
                          $('#enc_create_new').hide();
                          $('#enc_existing').show();
                          do_crypt(password);
                      },
                      fail: function () {
                          alert('Something went wrong');
                      }
                  });

              } else {
                  alert('generation error');
              }
          } else {
              alert('error');
          }
      }

	</script>

<?php //print_heading(array('title'=>'Encryption','type'=>'h3')); ?>
	<table class="tableclass tableclass_form tableclass_full" id="enc_existing">
		<tbody>
		<tr>
			<th class="width2">
				<?php _e( 'Encryption Vault:' ); ?>
			</th>
			<td id="env_vault_name">
				<?php
				// drop down list of available encryption methods.
				if ( $encryption_key && $encrypt && $encrypt['encrypt_key_id'] ) {
					// we actually have an encrypted value here. don't let them change the key.
					echo htmlspecialchars( $encryption_key['encrypt_key_name'] );
				} else {
					// no encryption saved yet for this field.
					// let them pick
					echo print_select_box( $encryption_keys, 'encrypt_key_id', isset( $encrypt_field['encrypt_key_id'] ) ? $encrypt_field['encrypt_key_id'] : false, '', true, 'encrypt_key_name', false );
					?>
					<a href="#"
					   onclick="$('#enc_existing').hide(); $('#enc_create_new').show();$('#encrypt_key_name')[0].focus(); return false;"><?php _e( 'Create New Vault' ); ?></a>
					<?php
				}
				?>
			</td>
		</tr>
		</tbody>
		<tbody id="password_box" style="<?php echo $adding_new_blank_field ? 'display:none;' : ''; ?>">
		<tr>
			<th>
				<?php _e( 'Enter Passphrase:' ); ?>
			</th>
			<td>
				<input type="password" name="encryptpass" id="encryptpass" value="" onchange="do_crypt(this.value);"
				       onkeyup="do_crypt(this.value);">
				<script type="text/javascript">
            $(function () {
                $('#encryptpass')[0].focus();
                $('#encrypt_key_id').change(function () {
                    var new_id = $(this).val();
                    if (typeof existing_encrypt_keys[new_id] != 'undefined') {
                        rsa2.public_key = existing_encrypt_keys[new_id].public_key;
                        rsa2.private_encrypted = existing_encrypt_keys[new_id].secured_private_key;
                        rsa2.e = existing_encrypt_keys[new_id].e;
                    }
                });
            })
				</script>
			</td>
		</tr>
		<tr>
			<th>
				<?php _e( 'Last Decryption:' ); ?>
			</th>
			<td>
				<?php
				$last = get_multiple( 'encrypt_access', array(
					'encrypt_id' => $encrypt_id,
					'status'     => 2
				), 'encrypt_access_id', 'exact', 'encrypt_access_id DESC' );
				if ( ! $last ) {
					_e( 'N/A' );
				} else {
					$last = array_shift( $last );
					_e( 'By %s at %s from %s', module_user::link_open( $last['create_user_id'], true ), print_date( $last['date_created'], true ), preg_replace( '#^(\d*\.\d*).*$#', '$1.**.**', $last['create_ip_address'] ) );
				}
				?>
			</td>
		</tr>
		<!-- <tr>
        <th>
            <?php _e( 'Raw Value:' ); ?>
        </th>
        <td>
            <textarea name="data" rows="7" cols="60" id="raw_value"><?php echo $encrypt['data']; ?></textarea>
        </td>
    </tr> -->
		</tbody>
		<tbody id="unlocking_box" style="display:none;">
		<tr>
			<th>
				<?php _e( 'Enter Passphrase:' ); ?>
			</th>
			<td>
				<?php _e( 'Success! Decrypting... please wait...' ); ?>
			</td>
		</tr>
		</tbody>
		<tbody id="decrypt_box" style="<?php echo $adding_new_blank_field ? '' : 'display:none;'; ?>">
		<tr>
			<th>
				<?php _e( 'Decrypted Value:' ); ?>
			</th>
			<td>
				<textarea name="decrypted_value" <?php if ( ! module_encrypt::can_i( 'edit', 'Encrypts' ) ) {
					echo 'disabled="disabled" ';
				} ?> rows="7" cols="60" id="decrypted_value"><?php echo htmlspecialchars( $existing_value ); ?></textarea>
			</td>
		</tr>
		<!-- <tr>
        <th >
            <?php _e( 'Encrypted Value:' ); ?>
        </th>
        <td>
            <textarea name="encrypted_value" rows="7" cols="60" id="encrypted_value"></textarea>
        </td>
    </tr> -->
		<?php if ( module_encrypt::can_i( 'edit', 'Encrypts' ) && isset( $_REQUEST['editable'] ) ) { // not really safe - used in custom data feature in view only mode ?>
			<tr>
				<th>
				</th>
				<td>
					<input type="submit" name="save" value="<?php _e( 'Encrypt and Save' ); ?>" onclick="do_save();"
					       class="submit_button save_button">
					<?php if ( $encrypt_id > 0 ) { ?>
						<input type="submit" name="decrypt" value="<?php _e( 'Save Decrypted' ); ?>" onclick="do_save_decrypted();"
						       class="submit_button delete_button">
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php if ( module_encrypt::can_i( 'create', 'Encrypts' ) ) { ?>
	<form action="" method="post">
		<table class="tableclass tableclass_form tableclass_full" id="enc_create_new" style="display:none;">
			<tbody>
			<tr>
				<th class="width2">
					<?php _e( 'Encryption Vault:' ); ?>
				</th>
				<td>
					<input type="text" name="encrypt_key_name" id="encrypt_key_name">
				</td>
			</tr>
			<tr>
				<th>
					<?php _e( 'Choose Passphrase:' ); ?>
				</th>
				<td>
					<input type="password" name="new_passphrase" id="new_passphrase" value="">
				</td>
			</tr>
			<tr>
				<th>
				</th>
				<td>
					<?php _e( '(this may take a few moments)' ); ?> <br/>
					<input type="button" name="save" value="<?php _e( 'Create New' ); ?>" onclick="create_new();"
					       class="submit_button save_button">
				</td>
			</tr>
			</tbody>
		</table>
	</form>
<?php } ?>