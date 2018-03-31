<?php

ob_start();

defined('_UCM_SECRET') || die('No access');


include( module_theme::include_ucm( 'includes/plugin_customer/portal/header.php') );

if(!$customer_id || !$customer_data){
	echo ('Invalid customer ID');
}else {

	if ( ! module_customer::c( 'customer_portal_allow', 1, $customer_id ) ) {
		echo 'Portal Access Not Allowed';
	}else {

		$password = module_customer::c( 'customer_portal_password', '', $customer_id );
		if(!empty($_POST['password']) && $_POST['password'] == $password){
			setcookie( 'portal_password', md5($_POST['password']), 0, _BASE_HREF );
			$_COOKIE['portal_password'] = md5($_POST['password']);
			header("Location: ".module_customer::link_public( $customer_id ));
			exit;
		}
		if ( ! empty( $password ) && ( empty($_COOKIE['portal_password']) || md5($password) != $_COOKIE['portal_password']) ) {


			include( module_theme::include_ucm( 'includes/plugin_customer/portal/login.php') );

		}else{

			$invoices = module_invoice::get_invoices(array('customer_id' => $customer_id ));
			foreach($invoices as $invoice_id => $invoice){
			    if(!$invoice['date_create'] || $invoice['date_create'] == '0000-00-00'){
			        unset($invoices[$invoice_id]);
                }
            }
			$quotes = module_quote::get_quotes(array('customer_id' => $customer_id ));
			$jobs = module_job::get_jobs(array('customer_id' => $customer_id ));
			$websites = module_website::get_websites(array('customer_id' => $customer_id ));
			$tickets = query_to_array(module_ticket::get_tickets(array('customer_id' => $customer_id )));
			$ucmtimers = new UCMTimers();
			$timers = $ucmtimers->get(array('customer_id' => $customer_id, 'billable' => 1), array('timer_id'=>'DESC'));
			foreach($timers as $timer_id => $timer){
			    if($timer['timer_status'] == _TIMER_STATUS_AUTOMATIC){
			        unset($timers[$timer_id]);
                }
            }
			$UCMContracts = new UCMContracts();
			$contracts = $UCMContracts->get(array('customer_id' => $customer_id, 'archived' => 0), array('ucm_contract_id'=>'DESC'));

			$messages = array();

			if(!empty($_POST['portal_action']) && module_form::check_secure_key()) {
				switch ( $_POST['portal_action'] ) {
					case 'generate_invoice':

						// quantity and stuff.
						$products = array();
						foreach ( $contracts as $contract ) {
							$UCMContract = UCMContract::singleton( $contract['contract_id'] );
							if ( $UCMContract->is_active() ) {
								foreach ( $UCMContract->get_products() as $product_id => $contract_id ) {
									$products[ $product_id ] = UCMProduct::singleton( $product_id );
								}
							}
						}

						$invoice_products = array();
						if ( ! empty( $_POST['qty'] ) && is_array( $_POST['qty'] ) ) {
							foreach ( $_POST['qty'] as $product_id => $qty ) {
								if ( (int) $qty > 0 && isset( $products[ $product_id ] ) ) {
									$invoice_products[ $product_id ] = $qty;
								}
							}
						}

						if ( $invoice_products ) {

							$invoice_data                      = module_invoice::get_invoice( 'new', true );
							$invoice_data['customer_id']       = $customer_id;
							$invoice_data['user_id']           = 0;
							$invoice_data['date_sent']         = '0000-00-00';
							$invoice_data['date_cancel']       = '0000-00-00';
							$invoice_data['date_create']       = '0000-00-00';
							$invoice_data['date_due']          = '0000-00-00';
							$invoice_data['default_task_type'] = _TASK_TYPE_AMOUNT_ONLY;

							$invoice_data['invoice_invoice_item'] = array();

							foreach ( $invoice_products as $product_id => $qty ) {
								$invoice_data['invoice_invoice_item'][ 'new' . $product_id ] = array(
									'description'      => $products[ $product_id ]->get( 'name' ),
									'product_id'       => $product_id,
									'hourly_rate'      => $products[ $product_id ]->get( 'amount' ),
									'hours'            => $products[ $product_id ]->get( 'quantity' ) * $qty,
									'completed'        => 1, // not needed?
									'manual_task_type' => $products[ $product_id ]->get( 'default_task_type' ),
									'date_done'        => date( 'Y-m-d' ),
								);

							}
							$invoice_id = module_invoice::save_invoice( 'new', $invoice_data );
							if ( $invoice_id ) {
								$messages[] = 'Invoice Request Submitted. We will email you the invoice for payment shortly. Thank you!';

								// send an email to the assigned staff member letting them know the contract was approved.
								$template = module_template::get_template_by_key( 'customer_portal_invoice' );
								$replace  = module_customer::get_replace_fields( $customer_id );
								$replace['customer_link'] = module_customer::link_open($customer_id,true,$customer_data);
								$replace['invoice_link'] = module_invoice::link_open($invoice_id,true);
								$template->assign_values( $replace );
								$html = $template->render( 'html' );
								// send an email to this user.
								$email                 = module_email::new_email();
								$email->replace_values = $replace;
								$email->set_to_manual( module_config::c( 'admin_email_address', '' ), '' );
								//$email->set_from('user',); // nfi
								$email->set_subject( $template->description );
								// do we send images inline?
								$email->set_html( $html );
								$email->customer_id        = $customer_id;
								$email->prevent_duplicates = true;
								if ( $email->send() ) {
									// it worked successfully!!
								} else {
									/// log err?
								}

							}

						}

						break;
				}
			}


			function portal_is_visible($section, $customer_id){
				$config_key = 'customer_portal_visible_' . preg_replace('$[^a-z]$', '', strtolower($section));
				return module_customer::c( $config_key, '1', $customer_id );
			}

			include( module_theme::include_ucm( 'includes/plugin_customer/portal/menu.php') );

			?>

			<!-- /. NAV SIDE  -->
			<div id="page-wrapper" >
				<div id="page-inner">
					<div class="row">
						<div class="col-md-12">

			<?php

            if($messages){
                foreach($messages as $message){ ?>
                    <div class="alert success">
                        <strong>Success!</strong> <?php echo $message;?>
                    </div>
                <?php
                }
            }

			echo '<div class="section active" data-section="dashboard">';

			module_template::init_template('customer_portal_header','<h3>Customer Details</h3>
Customer Name: <strong>{CUSTOMER_NAME}</strong> <br/> 
Contact: <strong>{CONTACT_NAME} {CONTACT_EMAIL} {CONTACT_PHONE} {CONTACT_MOBILE}</strong> <br/>
','Displayed at the top of the portal area.','code');
			// correct!
			// load up the receipt template.
			$template_header = module_template::get_template_by_key('customer_portal_header');
			// generate the html for the task output
			$template_header->assign_values($customer_data);
			$template_header->assign_values(module_customer::get_replace_fields($customer_id));
			echo $template_header->render();

			echo '</div>';

			if($contracts && portal_is_visible('Contracts', $customer_id)){
				echo '<div class="section" data-section="contracts">';
				print_heading(array(
					'title' => 'Contracts',
					'type' => 'h3',
					/*'button' => array(
						'title' => 'New Invoice',
						'url'   => module_invoice::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => 0,
							)
						) ),
					)*/
				));
				include( module_theme::include_ucm( 'includes/plugin_customer/portal/contracts.php') );
				echo '</div>';
			}

			if($quotes && portal_is_visible('Quotes', $customer_id)){
				echo '<div class="section" data-section="quotes">';
				print_heading(array(
					'title' => 'Quotes',
					'type' => 'h3',
					/*'button' => array(
						'title' => 'New Invoice',
						'url'   => module_invoice::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => 0,
							)
						) ),
					)*/
				));
				include( module_theme::include_ucm( 'includes/plugin_customer/portal/quotes.php') );
				echo '</div>';
			}

			if($jobs && portal_is_visible('Jobs', $customer_id)){
				echo '<div class="section" data-section="jobs">';
				print_heading(array(
					'title' => 'Jobs',
					'type' => 'h3',
					/*'button' => array(
						'title' => 'New Invoice',
						'url'   => module_invoice::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => 0,
							)
						) ),
					)*/
				));
				include( module_theme::include_ucm( 'includes/plugin_customer/portal/jobs.php') );
				echo '</div>';
			}

			if($invoices && portal_is_visible('Invoices & Payments', $customer_id)){
				echo '<div class="section" data-section="invoices">';
				print_heading(array(
					'title' => 'Invoices & Payments',
					'type' => 'h3',
					/*'button' => array(
						'title' => 'New Invoice',
						'url'   => module_invoice::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => 0,
							)
						) ),
					)*/
				));
				include( module_theme::include_ucm( 'includes/plugin_customer/portal/invoices.php') );
				echo '</div>';
			}


			if($websites && portal_is_visible('Websites', $customer_id)){
				echo '<div class="section" data-section="websites">';
				print_heading(array(
					'title' => 'Websites',
					'type' => 'h3',
					/*'button' => array(
						'title' => 'New Invoice',
						'url'   => module_invoice::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => 0,
							)
						) ),
					)*/
				));
				include( module_theme::include_ucm( 'includes/plugin_customer/portal/websites.php') );
				echo '</div>';
			}


			if($tickets && portal_is_visible('Tickets', $customer_id)){
				echo '<div class="section" data-section="tickets">';
				print_heading(array(
					'title' => 'Tickets',
					'type' => 'h3',
					/*'button' => array(
						'title' => 'New Invoice',
						'url'   => module_invoice::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => 0,
							)
						) ),
					)*/
				));
				include( module_theme::include_ucm( 'includes/plugin_customer/portal/tickets.php') );
				echo '</div>';
			}

			if($timers && portal_is_visible('Timers', $customer_id)){
				echo '<div class="section" data-section="timers">';
				print_heading(array(
					'title' => 'Timers',
					'type' => 'h3',
					/*'button' => array(
						'title' => 'New Invoice',
						'url'   => module_invoice::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => 0,
							)
						) ),
					)*/
				));
				include( module_theme::include_ucm( 'includes/plugin_customer/portal/timers.php') );
				echo '</div>';
			}



			if(portal_is_visible('Shop', $customer_id)){
				echo '<div class="section" data-section="shop">';
				print_heading(array(
					'title' => 'Shop',
					'type' => 'h3',
					/*'button' => array(
						'title' => 'New Invoice',
						'url'   => module_invoice::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => 0,
							)
						) ),
					)*/
				));
				include( module_theme::include_ucm( 'includes/plugin_customer/portal/shop.php') );
				echo '</div>';
			}

			?>

						</div>
					</div>
					<!-- /. ROW  -->

					<!-- /. ROW  -->
				</div>
				<!-- /. PAGE INNER  -->
			</div>
			<?php


		}
	}

}

include( module_theme::include_ucm( 'includes/plugin_customer/portal/footer.php') );