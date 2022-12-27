<?php

// when a particular data entry is opened, this page is displayed.
// this reads the layout of the page structure from the database (configured through the drag/drop settings) and displays the info.

// we could pass rendering this type of layout off to one of the /layout/

// this file could be included multiple times from within itself, maybe... so we do some if(!isset()) checking first..


$view_revision_id = isset( $view_revision_id ) ? $view_revision_id : ( ( isset( $_REQUEST['revision_id'] ) ) ? $_REQUEST['revision_id'] : false );


if ( ! isset( $data_record_id ) || ! $data_record_id ) {
	$data_record_id = $_REQUEST['data_record_id'];
	if ( $data_record_id && $data_record_id != 'new' ) {
		$data_record             = $module->get_data_record( $data_record_id );
		$data_type_id            = $data_record['data_type_id']; // so we dont have to pass it in all the time.
		$data_record_revision_id = $data_record['last_revision_id'];
		if ( $view_revision_id ) {
			$data_record_revision_id = $view_revision_id;
		}
		$data_items = $module->get_data_items( $data_record_id, $data_record_revision_id );
		//$data_notes = $module->get_notes($data_record_id);
		$create_user = module_user::get_user( $data_record['create_user_id'] );
		/*if($data_record['create_user_id']){
			// hack to get the name of the login from a custom login box.
			$user_login = module_user::get_user($data_record['create_user_id']);
			if($user_login['name']){
				$create_user['name'] .= ' (' . htmlspecialchars($user_login['name']).')';
			}
		}*/
		$update_user = module_user::get_user( $data_record['update_user_id'] );
		/*if($data_record['update_user_id']){
			// hack to get the name of the login from a custom login box.
			$user_login = module_user::get_user($data_record['update_user_id']);
			if($user_login['name']){
				$update_user['name'] .= ' (' . htmlspecialchars($user_login['name']).')';
			}
		}*/
		$revision_count        = 1; // get list of history.
		$data_record_revisions = $module->get_data_record_revisions( $data_record_id );
		$revision_count        = count( $data_record_revisions );

		/*if(getlevel()!="administrator" && $data_record['create_user_id'] != $_SESSION['_user_id']){
			// dodgy security check. but works.
			echo 'Sorry, you do not have permission to access this record';
			exit;
		}*/

		// record that a record was accessed
		$module->record_access( $data_record_id );

	} else {
		// for printing otu the summary:
		$data_record['status']                = 'New';
		$data_record['data_record_id']        = $module->next_record_id();
		$data_record['create_ip_address']     = $_SERVER['REMOTE_ADDR'];
		$data_record['update_ip_address']     = '';
		$data_record['create_user_id']        = module_security::get_loggedin_id();
		$data_record['date_created']          = time();
		$data_record['date_updated']          = false;
		$data_record['parent_data_record_id'] = isset( $_REQUEST['parent_data_record_id'] ) ? (int) $_REQUEST['parent_data_record_id'] : 0;
		$update_user                          = $create_user = module_user::get_user( $data_record['create_user_id'] );
		$data_record_revisions                = array();
		$data_record_revision_id              = false;
		$revision_count                       = 0;
		$data_items                           = array();
	}
}


if ( ! isset( $data_type_id ) || ! $data_type_id ) {
	$data_type_id = isset( $_REQUEST['data_type_id'] ) ? (int) $_REQUEST['data_type_id'] : false;
}
if ( ! isset( $data_type ) || ! $data_type ) {
	if ( $data_type_id ) {
		$data_type = $module->get_data_type( $data_type_id );
	} else {
		die( 'No data type, please try again' );
	}
}

if ( ! $module->can_i( 'view', $data_type['data_type_name'] ) ) {
	die( 'no permissions' );
}


if ( ! isset( $data_field_groups ) || ! $data_field_groups ) {
	$data_field_groups = $module->get_data_field_groups( $data_type_id );
}

$rendered_field_groups = array();

// starting work on form error handling:
$GLOBALS['form_id'] = 'data_form';


$mode               = ( isset( $_REQUEST['mode'] ) && $_REQUEST['mode'] ) ? $_REQUEST['mode'] : 'view'; // edit revisions
$show_incident_menu = true;

if ( isset( $embed_form ) ) {
	$show_incident_menu = false;
}

if ( isset( $_SESSION['admin_mode'] ) && $_SESSION['admin_mode'] && ! isset( $_REQUEST['mode'] ) ) {
	$mode = 'admin';
}

if ( isset( $_REQUEST['print'] ) ) {
	$show_incident_menu = false;
	$mode               = 'view';
	ob_start();
}

?>

<div class="custom_data_nav_wrap ">
	<?php if ( $show_incident_menu ) { ?>
		<?php if ( $data_record_id && $data_record_id != 'new' ) { ?>
			<div class="ui-tabs ui-widget ui-widget-content ui-corner-all ui-tabs-collapsible">
				<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
					<?php if ( $data_record['parent_data_record_id'] ) {
						$parent_data_record = $module->get_data_record( $data_record['parent_data_record_id'] );
						?>
						<li class="ui-state-default ui-corner-top">
							<a href="<?php echo $module->link( '', array(
								'data_type_id'   => $parent_data_record['data_type_id'],
								'data_record_id' => $data_record['parent_data_record_id'],
								'mode'           => 'view',
							) ); ?>" class="ui-tabs-anchor">&laquo; Return</a>
						</li>
					<?php } ?>
					<li
						class="ui-state-default ui-corner-top <?php echo ( $mode == 'view' ) ? 'link_current ui-tabs-active ui-state-active' : ''; ?>">
						<a href="<?php echo $module->link( '', array(
							'data_type_id'   => $data_type_id,
							'data_record_id' => $data_record_id,
							'mode'           => 'view',
						) ); ?>" class="ui-tabs-anchor"><?= _l( 'View' ); ?></a>
					</li>
					<?php if ( $module->can_i( 'edit', $data_type['data_type_name'] ) ) { ?>
						<li
							class="ui-state-default ui-corner-top <?php echo ( $mode == 'edit' ) ? 'link_current ui-tabs-active ui-state-active' : ''; ?>">
							<a href="<?php echo $module->link( '', array(
								'data_type_id'   => $data_type_id,
								'data_record_id' => $data_record_id,
								'mode'           => 'edit',
							) ); ?>" class="ui-tabs-anchor"><?= _l( 'Edit' ); ?></a>
						</li>
					<?php } ?>
					<li
						class="ui-state-default ui-corner-top <?php echo ( $mode == 'revisions' ) ? 'link_current ui-tabs-active ui-state-active' : ''; ?>">
						<a href="<?php echo $module->link( '', array(
							'data_type_id'   => $data_type_id,
							'data_record_id' => $data_record_id,
							'mode'           => 'revisions',
						) ); ?>" class="ui-tabs-anchor"><?= _l( 'Revisions' ); ?></a>
					</li>
					<li class="ui-state-default ui-corner-top">
						<a href="<?php echo $_SERVER['REQUEST_URI'] . '&print=true'; ?>"
						   onclick="window.open(this.href,'pop','width=900,height=700,scrollbars=1'); return false;"
						   class="ui-tabs-anchor"><?= _l( 'Print' ); ?></a>
					</li>
					<?php if ( isset( $data_type['print_pdf_template'] ) && $data_type['print_pdf_template'] ) { ?>
						<li class="ui-state-default ui-corner-top">
							<a href="<?php echo $module->link( '', array(
								'data_type_id'   => $data_type_id,
								'data_record_id' => $data_record_id,
								'mode'           => 'pdf',
							) ); ?>" class="ui-tabs-anchor"><?= _l( 'PDF' ); ?></a>
						</li>
					<?php } ?>
					<?php if ( isset( $data_type['email_template'] ) && $data_type['email_template'] ) { ?>
						<li class="ui-state-default ui-corner-top">
							<a href="<?php echo $module->link( '', array(
								'data_type_id'   => $data_type_id,
								'data_record_id' => $data_record_id,
								'mode'           => 'email',
							) ); ?>" class="ui-tabs-anchor"><?= _l( 'Email' ); ?></a>
						</li>
					<?php } ?>
					<?php if ( $module->can_i( 'edit', _MODULE_DATA_NAME ) ) { ?>
						<li
							class="ui-state-default ui-corner-top <?php echo ( $mode == 'admin' ) ? 'link_current ui-tabs-active ui-state-active' : ''; ?>">
							<a href="<?php echo module_data::link_open_data_type( $data_type_id ); ?>"
							   class="ui-tabs-anchor"><?= _l( 'Settings' ); ?></a>
						</li>
					<?php } ?>
				</ul>
			</div>
			<?php
		}
	} ?>
	<div class="custom_data_wrapper">

		<div id="revisions" style="<?php if ( $mode != 'revisions' ) { ?>display:none;<?php } ?>">
			<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
				<thead>
				<tr class="title">
					<th><?php echo _l( 'Revision #' ); ?></th>
					<th><?php echo _l( 'Date' ); ?></th>
					<!-- <th><?php echo _l( 'Status' ); ?></th> -->
					<th><?php echo _l( 'User' ); ?></th>
					<th><?php echo _l( 'What Fields Were Changed' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				$x                       = 1;
				$c                       = 1;
				$current_revision        = array();
				$last_revision_id        = false;
				$next_revision_id        = false;
				$previous_revision_id    = false;
				$temp_revision_id        = - 1;
				$custom_highlight_fields = array();
				foreach ( $data_record_revisions as $data_record_revision ) {
					$user = module_user::get_user( $data_record_revision['create_user_id'] );
					if ( $previous_revision_id && ! $next_revision_id ) {
						$next_revision_id = $data_record_revision['data_record_revision_id'];
					}
					if ( $data_record_revision['data_record_revision_id'] == $view_revision_id ) {
						$current_revision           = $data_record_revision;
						$current_revision['number'] = $x;
						$previous_revision_id       = $temp_revision_id;
					}
					$temp_revision_id = $data_record_revision['data_record_revision_id'];
					?>
					<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
						<td class="row_action"><a href="<?php echo $module->link( '', array(
								"data_type_id"   => $data_type_id,
								"data_record_id" => $data_record_id,
								"revision_id"    => $data_record_revision['data_record_revision_id']
							) ); ?>">#<?php echo $x ++; ?></a></td>
						<td><?php echo print_date( $data_record_revision['date_created'], true ); ?></td>
						<!-- <td><?php echo $data_record_revision['status']; ?></td> -->
						<td><?php echo $user['name']; ?> (<?php echo $data_record_revision['create_ip_address']; ?>)</td>
						<td>
							<?php if ( $x == 2 ) {
								echo 'Initial Version';
							} else {
								// find out changed fields.
								$sql = "SELECT * FROM `" . _DB_PREFIX . "data_store` WHERE data_record_revision_id = '" . $data_record_revision['data_record_revision_id'] . "' AND data_record_id = '" . $data_record_id . "'";
								$res = qa( $sql );
								if ( ! count( $res ) ) {
									echo 'no changes';
								}
								foreach ( $res as $field ) {
									$field_data = @unserialize( $field['data_field_settings'] );
									echo isset( $field_data['title'] ) ? $field_data['title'] . ',' : '';
								}
							}
							?>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
		<?php
		switch ( $mode ) {
			case 'email':
				end( $data_record_revisions );
				$current_revision           = current( $data_record_revisions );
				$current_revision['number'] = count( $data_record_revisions );
				$view_revision_id           = $current_revision['data_record_revision_id'];
				$current_revision           = array(); // delete this if you want to display view revisions at the top.
				if ( $current_revision && $view_revision_id ) {
					// user wants a custom revision, we pull out the custom $data_field_groups
					// and we tell the form layout to use the serialized cached field layout information
					$data_field_groups = unserialize( $current_revision['field_group_cache'] );
					// we dont always read from cache, because then any ui changes wouldn't be reflected in older reports (if we want to change older reports)
				}
				$replace              = ! empty( $_REQUEST['customer_id'] ) ? module_customer::get_replace_fields( $_REQUEST['customer_id'] ) : array();
				$replace['from_name'] = module_security::get_loggedin_name();
				foreach ( $data_field_groups as $data_field_group ) {
					$data_field_group_id = $data_field_group['data_field_group_id'];
					$data_field_group    = $module->get_data_field_group( $data_field_group_id );
					$data_fields         = $module->get_data_fields( $data_field_group_id );
					foreach ( $data_fields as $data_field ) {
						$data_field_id = $data_field['data_field_id'];
						if ( isset( $data_items[ $data_field_id ] ) ) {
							$data_field['value'] = $data_items[ $data_field_id ]['data_text']; // todo, could be data_number or data_varchar as well... hmmm
						}
						$replace[ $data_field['title'] ] = $module->get_form_element( $data_field, true, isset( $data_record ) ? $data_record : array() );
					}
				}
				//$pdf = module_invoice::generate_pdf($invoice_id);

				// template for sending emails.
				// are we sending the paid one? or the dueone.
				$original_template_name = $template_name = $data_type['email_template'];
				$template_name          = isset( $_REQUEST['template_name'] ) ? $_REQUEST['template_name'] : $template_name;
				$template               = module_template::get_template_by_key( $template_name );
				$template->assign_values( $replace );


				$module->page_title = htmlspecialchars( $data_type['data_type_name'] );
				print_heading( array(
					'main'  => true,
					'type'  => 'h2',
					'title' => htmlspecialchars( $data_type['data_type_name'] ),
				) );
				module_email::print_compose(
					array(
						'title'                => _l( 'Email: %s', htmlspecialchars( $data_type['data_type_name'] ) ),
						'find_other_templates' => $original_template_name, // find others based on this name, eg: job_email*
						'current_template'     => $template_name,
						'customer_id'          => isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : 0,
						'to'                   => array(),
						'bcc'                  => module_config::c( 'admin_email_address', '' ),
						'content'              => $template->render( 'html' ),
						'subject'              => $template->replace_description(),
						'success_url'          => $module->link( '', array(
							'data_type_id'   => $data_type_id,
							'data_record_id' => $data_record_id,
							'mode'           => 'view',
						) ),
						'cancel_url'           => $module->link( '', array(
							'data_type_id'   => $data_type_id,
							'data_record_id' => $data_record_id,
							'mode'           => 'view',
						) ),
						/*'attachments' => array(
								array(
										'path'=>$pdf,
										'name'=>basename($pdf),
										'preview'=>module_invoice::link_generate($invoice_id,array('arguments'=>array('go'=>1,'print'=>1),'page'=>'invoice_admin','full'=>false)),
								),
						),*/
					)
				);
				?>
				<p>
					Available Template Keys: </p>
				<ul>
					<?php foreach ( $replace as $key => $val ) {
						if ( strpos( $val, 'encrypt_popup' ) ) {
							continue;
						} ?>
						<li><strong>{<?php echo strtoupper( htmlspecialchars( $key ) ); ?>}</strong> = <?= $val; ?></li>
					<?php } ?>
				</ul>
				<?php
				break;
			case 'pdf':

				if ( ! function_exists( 'convert_html2pdf' ) ) {
					die( 'PDF generation not available' );
				}


				end( $data_record_revisions );
				$current_revision           = current( $data_record_revisions );
				$current_revision['number'] = count( $data_record_revisions );
				$view_revision_id           = $current_revision['data_record_revision_id'];
				$current_revision           = array(); // delete this if you want to display view revisions at the top.
				if ( $current_revision && $view_revision_id ) {
					// user wants a custom revision, we pull out the custom $data_field_groups
					// and we tell the form layout to use the serialized cached field layout information
					$data_field_groups = unserialize( $current_revision['field_group_cache'] );
					// we dont always read from cache, because then any ui changes wouldn't be reflected in older reports (if we want to change older reports)
				}
				$replace              = ! empty( $_REQUEST['customer_id'] ) ? module_customer::get_replace_fields( $_REQUEST['customer_id'] ) : array();
				$replace['from_name'] = module_security::get_loggedin_name();
				foreach ( $data_field_groups as $data_field_group ) {
					$data_field_group_id = $data_field_group['data_field_group_id'];
					$data_field_group    = $module->get_data_field_group( $data_field_group_id );
					$data_fields         = $module->get_data_fields( $data_field_group_id );
					foreach ( $data_fields as $data_field ) {
						$data_field_id = $data_field['data_field_id'];
						if ( isset( $data_items[ $data_field_id ] ) ) {
							$data_field['value'] = $data_items[ $data_field_id ]['data_text']; // todo, could be data_number or data_varchar as well... hmmm
						}
						$replace[ $data_field['title'] ] = $module->get_form_element( $data_field, true, isset( $data_record ) ? $data_record : array() );
					}
				}

				ob_end_clean();


				ob_start();
				$template = module_template::get_template_by_key( $data_type['print_pdf_template'] );
				if ( ! $template || $template->template_key != $data_type['print_pdf_template'] ) {
					echo "PDF template " . $data_type['print_pdf_template'] . " not found";
				} else {
					$template->assign_values( $replace );
					echo $template->render( 'html' );
				}
				$html_output = ob_get_clean();

				$pdf_name       = basename( preg_replace( '#[^a-zA-Z0-9_]#', '_', $data_type['data_type_name'] ) );
				$html_file_name = _UCM_FILE_STORAGE_DIR . 'temp/data_' . $pdf_name . '.html';
				$pdf_file_name  = _UCM_FILE_STORAGE_DIR . 'temp/data_' . $pdf_name . '.pdf';

				file_put_contents( $html_file_name, $html_output );

				$pdf_file = convert_html2pdf( $html_file_name, $pdf_file_name );

				@ob_end_clean();
				@ob_end_clean();

				// send pdf headers and prompt the user to download the PDF

				header( "Pragma: public" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Cache-Control: private", false );
				header( "Content-Type: application/pdf" );
				header( "Content-Disposition: attachment; filename=\"" . basename( $pdf_file ) . "\";" );
				header( "Content-Transfer-Encoding: binary" );
				$filesize = filesize( $pdf_file );
				if ( $filesize > 0 ) {
					header( "Content-Length: " . $filesize );
				}
				// some hosting providershave issues with readfile()
				$read = readfile( $pdf_file );
				if ( ! $read ) {
					echo file_get_contents( $pdf_file );
				}

				exit;


				break;
			case 'view':
				// view the most recent revision, or the specified revision.
				if ( ! $view_revision_id && $data_record_revisions ) {
					end( $data_record_revisions );
					$current_revision           = current( $data_record_revisions );
					$current_revision['number'] = count( $data_record_revisions );
					$view_revision_id           = $current_revision['data_record_revision_id'];
					$current_revision           = array(); // delete this if you want to display view revisions at the top.
				}
				if ( $current_revision && $view_revision_id ) {
					// user wants a custom revision, we pull out the custom $data_field_groups
					// and we tell the form layout to use the serialized cached field layout information
					$data_field_groups = unserialize( $current_revision['field_group_cache'] );
					// we dont always read from cache, because then any ui changes wouldn't be reflected in older reports (if we want to change older reports)
				}
			case 'edit':
				// edit the latest revision.


				if ( $view_revision_id && $current_revision ) {


					print_heading( array(
						'type'  => 'h2',
						'title' => 'Viewing Revision: #' . $current_revision['number'] . ' - ' . print_date( $current_revision['date_created'] ),
					) );

					?>
					<!--
					<a href="<?php echo $module->link( '', array(
						"data_type_id"   => $data_type_id,
						"data_record_id" => $data_record_id
					) ); ?>">&laquo; Cancel and return to editor</a> <br>
					-->

					<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
						<thead>
						<tr class="title">
							<th><?php echo _l( 'Revisions' ); ?></th>
							<th><?php echo _l( 'Date' ); ?></th>
							<th><?php echo _l( 'User' ); ?></th>
							<th><?php echo _l( 'What Changed' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<tr class="odd">
							<td valign="top">
								<?php if ( $previous_revision_id > 0 ) { ?>
									<?php echo create_link( "&laquo; Previous", "link", $module->link( '', array(
										"data_type_id"   => $data_type_id,
										"data_record_id" => $data_record_id,
										"revision_id"    => $previous_revision_id
									) ) ); ?>
								<?php } ?>

								<?php if ( $next_revision_id ) {
									echo create_link( "Next &raquo;", "link", $module->link( '', array(
										"data_type_id"   => $data_type_id,
										"data_record_id" => $data_record_id,
										"revision_id"    => $next_revision_id
									) ) );
								} ?>
							</td>
							<td><?php echo print_date( $current_revision['date_created'], true ); ?></td>
							<td><?php echo $user['name']; ?> (<?php echo $current_revision['create_ip_address']; ?>)</td>
							<td>
								<?php if ( $current_revision['number'] == 1 ) {
									echo 'Initial Version';
								} else {
									// find out changed fields.
									$sql = "SELECT * FROM `" . _DB_PREFIX . "data_store` WHERE data_record_revision_id = '" . $current_revision['data_record_revision_id'] . "' AND data_record_id = '" . $data_record_id . "'";
									$res = qa( $sql );
									if ( ! count( $res ) ) {
										echo 'no changes';
									}
									foreach ( $res as $field ) {
										//if($current_revision['data_record_revision_id'] == $view_revision_id){
										$custom_highlight_fields[ $field['data_field_id'] ] = true;
										//}
										$field_data = unserialize( $field['data_field_settings'] );
										echo $field_data['title'] . ',';
									}
								}
								?>
							</td>
						</tr>
						</tbody>
					</table>

					<?php
				}

				$module->page_title = htmlspecialchars( $data_type['data_type_name'] );


				if ( ! isset( $embed_form ) ) {

					if ( get_display_mode() != 'iframe' ) {
						print_heading( array(
							'main'  => true,
							'type'  => 'h2',
							'title' => htmlspecialchars( $data_type['data_type_name'] ),
						) );
					}

					?>
					<form action="" method="post" class="validate" enctype="multipart/form-data">
					<?php if ( ! $view_revision_id ) { ?>
						<input type="hidden" name="form_id" value="<?php echo $GLOBALS['form_id']; ?>">
						<input type="hidden" name="_process" value="save_data_record"/>
						<input type="hidden" name="_redirect" value="<?php echo $module->link( "", array(
							"saved"          => true,
							"data_type_id"   => $data_type_id,
							"data_record_id" => $data_record_id
						) ); ?>"/>
						<input type="hidden" name="data_record_id" value="<?php echo $data_record_id; ?>"/>
						<input type="hidden" name="parent_data_record_id"
						       value="<?php echo (int) $data_record['parent_data_record_id']; ?>"/>
						<input type="hidden" name="data_type_id" value="<?php echo $data_type_id; ?>"/>
						<input type="hidden" name="data_save_hash"
						       value="<?php echo $module->save_hash( $data_record_id, $data_type_id ); ?>"/>
						<?php foreach ( $module->get_data_link_keys() as $key ) {
							if ( isset( $_REQUEST[ $key ] ) ) {
								?>
								<input type="hidden" name="<?php echo $key; ?>"
								       value="<?php echo (int) $_REQUEST[ $key ]; ?>">
								<?php
							}
						}
					}
				}
				// time to format the fields onto the page.
				// fields goes into groups.
				$form_actions = array(
					'class'    => 'action_bar action_bar_center',
					'elements' => array(
						array(
							'type'  => 'save_button',
							'name'  => 'butt_save',
							'value' => _l( 'Save Information' ),
						),
						array(
							'ignore' => ! ( $module->can_i( 'delete', $data_type['data_type_name'] ) && (int) $data_record_id > 0 ),
							'type'   => 'delete_button',
							'name'   => 'butt_del',
							'value'  => _l( 'Delete' ),
						),
						array(
							'type'    => 'button',
							'name'    => 'cancel',
							'value'   => _l( 'Cancel' ),
							'class'   => 'submit_button',
							'onclick' => "window.location.href='" . (
								( $data_record['parent_data_record_id'] )
									? $module->link( '', array( "data_record_id" => $data_record['parent_data_record_id'] ) )
									: $module->link( '', array( 'data_type_id' => $data_type_id ) )
								) . "';",
						),
					),
				);


				foreach ( $data_field_groups as $data_field_group ) {
					$data_field_group_id = $data_field_group['data_field_group_id'];
					include( 'render_group.php' );
				}
				if ( ! $view_revision_id && ( ! isset( $embed_form ) || ! $embed_form ) ) {


					echo module_form::generate_form_actions( $form_actions );
				}
				if ( ! isset( $embed_form ) ) {
					?>
					</form>
					<?php
				}
				?>

				<hr class="clear">
				<?php
				break;
		}
		?>
	</div>
</div>

<?php
$_SESSION['_form_highlight'][ $GLOBALS['form_id'] ] = array();

if ( isset( $_REQUEST['print'] ) ) {
	$content = ob_get_clean();
	?>
	<html>
	<head>
		<title>Print</title>
		<link rel="stylesheet" href="css/styles.css" type="text/css"/>
		<link type="text/css" href="css/ui-lightness/jquery-ui-1.8.1.custom.css" rel="stylesheet"/>
		<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.1.custom.min.js"></script>

		<style type="text/css">
			body {
				background-color: #FFFFFF !important;
			}

			.data_field_view {
				background-color: #FFFFFF !important;
				border: 1px solid #EFEFEF;
			}

			th, td {
				font-size: 12px;
			}

			.hidden {
				display: none;
			}
		</style>
	</head>
	<body>
	<input type="button" name="print" value="Click here to print" onclick="$(this).hide(); window.print(); ">
	<?php echo $content; ?>
	</body>
	</html>
	<?php
	exit;
}
?>


