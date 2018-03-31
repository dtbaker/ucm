<?php if ( $header_logo = module_theme::get_config( 'theme_logo', _BASE_HREF . 'images/logo.png' ) ) { ?>
	<div class="text-center logo-box">
		<img src="<?php echo htmlspecialchars( $header_logo ); ?>" border="0"
		     title="<?php echo htmlspecialchars( module_config::s( 'header_title', 'UCM' ) ); ?>">
	</div>
<?php } ?>

<?php if ( isset( $_REQUEST['signup'] ) && module_config::c( 'customer_signup_on_login', 0 ) ) { ?>

	<div id="signup" class="tab-pane">
		<div id="wrapper">

			<div class="form-box" id="signup-box">
				<h1><?php _e( 'Sign Up' ); ?></h1>
				<div class="body bg-gray">
					<?php
					$form_html = module_customer::get_customer_signup_form_html();
					$form_html = str_replace( '<p><input type="submit" value="Signup Now" /></p>', '<div class="footer"> <button type="submit" class="btn bg-loginbtn btn-block">' . _l( 'Sign Up' ) . '</button> <p class="cancel"><a href="?login">Cancel</a></p></div>', $form_html );
					echo $form_html;
					?>
				</div>
			</div>
		</div>
	</div>

<?php } else if ( isset( $_REQUEST['forgot_password'] ) ) { ?>

	<div class="fake-table">
		<div class="fake-table-cell">
			<div id="login">
				<div class="top left clearfix welcometitle">
					<p><?php echo htmlspecialchars( module_config::s( 'admin_system_name' ) ); ?>
						<br><span><?php _e( 'Reset Password' ); ?></span></p>
				</div>
				<form action="" class="form-signin" method="post">
					<input type="hidden" name="_process_reset" value="true">
					<div class="fields">
						<fieldset>
							<input type="text" name="email" placeholder="mail@example.com" class="loginform" value="">
							<span class="icon"><i class="fa fa-user"></i></span>
						</fieldset>
						<?php if ( class_exists( 'module_captcha', false ) ) { ?>
							<fieldset>
								<div class="recaptcha">
									<?php echo module_captcha::display_captcha_form(); ?>
								</div>
							</fieldset>
						<?php } ?>
						<input type="submit" value="OK" class="loginbutton">
					</div>
					<div class="bottom clearfix has-pretty-child">
						<a href="?login" class="forgot right"><?php _e( 'Back to Login' ); ?></a>
					</div>
					<?php hook_handle_callback( 'login_screen' ); ?>
				</form>
			</div>
		</div>
	</div>

<?php } else { ?>

	<div class="fake-table">
		<div class="fake-table-cell">
			<div id="login">
				<div class="top clearfix welcometitle">
					<p><?php echo htmlspecialchars( module_config::s( 'admin_system_name' ) ); ?>
						<br><span><?php _e( 'Login Page' ); ?></span></p>
				</div>
				<?php if ( _DEMO_MODE ) { ?>
					<div class="demo-welcome clearfix">
						<div class="">
							<strong>Welcome to the <a
									href="http://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker&utm_source=Demo&utm_medium=Header&utm_campaign=blocks&utm_content=blocks"
									target="_blank">UCM Pro</a> Demo. <br/>Login details are:</strong><br/>
							Admin: admin@example.com / password <br/>
							Customer: user@example.com / password <br/>
							Staff: staff@example.com / password <br/>
						</div>
					</div>
				<?php } ?>
				<form action="" class="form-signin" method="post">
					<input type="hidden" name="_process_login" value="true">
					<div class="fields">
						<fieldset>
							<input type="text" name="email" id="email" placeholder="LOGIN" class="loginform"
							       value="<?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? 'admin@example.com' : ''; ?>">
							<span class="icon"><i class="fa fa-user"></i></span>
						</fieldset>
						<fieldset>
							<input type="password" name="password" id="password" placeholder="PASSWORD" class="loginform"
							       value="<?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? 'password' : ''; ?>">
							<span class="icon"><i class="fa fa-key"></i></span>
						</fieldset>
						<?php if ( class_exists( 'module_captcha', false ) && module_config::c( 'login_recaptcha', 0 ) ) { ?>
							<fieldset>
								<div class="recaptcha">
									<?php echo module_captcha::display_captcha_form(); ?>
								</div>
							</fieldset>
						<?php } ?>
						<input type="submit" value="OK" class="loginbutton">
					</div>
					<div class="bottom clearfix has-pretty-child">
						<a href="?forgot_password" class="forgot right"><?php _e( 'Forgot Password' ); ?></a>
						<?php
						if ( module_config::c( 'customer_signup_on_login', 0 ) ) {
							?>
							<a href="<?php echo module_config::c( 'customer_signup_on_login_url', '' ) ?: '?signup'; ?>"
							   class="forgot right"><?php _e( 'Sign Up' ); ?></a>
							<?php
						}
						?>
					</div>
					<?php hook_handle_callback( 'login_screen' ); ?>
				</form>
			</div>
		</div>
	</div>
	<script type="text/javascript">
      $(function () {
          $('#email')[0].focus();
          setTimeout(function () {
              if ($('#email').val() != '') {
                  $('#password')[0].focus();
              }
          }, 100);
      });
	</script>

<?php } ?>
