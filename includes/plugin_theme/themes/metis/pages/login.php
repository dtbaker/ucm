<div class="container">
	<?php if ( isset( $_REQUEST['signup'] ) && module_config::c( 'customer_signup_on_login', 0 ) ) { ?>
		<div class="tab-content">
			<div id="signup" class="tab-pane active">
				<h2><?php echo _l( 'Sign Up' ); ?> </h2>
				<?php
				$form_html = module_customer::get_customer_signup_form_html();
				$form_html = str_replace( '<p><input type="submit" value="Signup Now" /></p>', '', $form_html );
				$form_html = str_replace( '</form>', '<p><input type="submit" class="submit_button" value="' . _l( 'Sign Up' ) . '"></p><p><a href="?login">Cancel</a></p></form>', $form_html );
				echo $form_html;
				?>
			</div>
		</div>


	<?php }else if ( isset( $_GET['forgot'] ) ){ ?>

		<div class="tab-content">
			<div id="forgot" class="tab-pane active">
				<form action="" method="post" class="form-signin">
					<input type="hidden" name="_process_reset" value="true">
					<p
						class="text-muted text-center"><?php echo _l( 'Please enter your email address below to reset your password.' ); ?></p>
					<input type="email" placeholder="mail@domain.com" id="email" name="email" required="required"
					       class="form-control">
					<br>
					<?php if ( class_exists( 'module_captcha', false ) ) { ?>
						<?php echo module_captcha::display_captcha_form(); ?>
						<br>
					<?php } ?>
					<button class="btn btn-lg btn-danger btn-block" name="reset"
					        type="submit"><?php echo _l( 'Reset Password' ); ?></button>
					<?php hook_handle_callback( 'forgot_password_screen' ); ?>
				</form>
			</div>
		</div>
		<div class="text-center">
			<ul class="list-inline">
				<li><a class="text-muted" href="?""><?php _e( 'Login' ); ?></a></li>
				<li><a class="text-muted" href="?forgot"><?php _e( 'Forgot Password' ); ?></a></li>
				<?php
				if ( module_config::c( 'customer_signup_on_login', 0 ) ) {
					?>
					<li><a class="text-muted"
					       href="<?php echo module_config::c( 'customer_signup_on_login_url', '' ) ?: '?signup'; ?>"><?php _e( 'Sign Up' ); ?></a>
					</li>
					<?php
				}
				?>
			</ul>
		</div>

	<?php }else{ ?>

		<div class="tab-content">
			<div id="login" class="tab-pane active">
				<form action="" class="form-signin" method="post">
					<input type="hidden" name="_process_login" value="true">
					<?php if ( _DEMO_MODE ) { ?>
						<p class="text-muted text-center"
						   style="background: #222; border-radius: 10px; font-size: 0.9em; padding:10px 5px;">
							<strong>Login details for the UCM demo are:</strong><br/>
							Administrator: admin@example.com / password <br/>
							Customer: user@example.com / password <br/>
							<a href="http://ultimateclientmanager.com/support/faq-knowledge-base/faq-item/?ucm_faq_id=20"
							   target="_blank">FAQ: create your own Customer login</a>
						</p>
					<?php } ?>
					<p class="text-muted text-center">
						<?php _e( 'Enter your username and password' ); ?>
					</p>
					<input type="text" placeholder="Username" class="form-control" name="email" id="email"
					       value="<?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? 'admin@example.com' : ''; ?>">
					<input type="password" placeholder="Password" class="form-control" name="password" id="password"
					       value="<?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? 'password' : ''; ?>">
					<?php if ( class_exists( 'module_captcha', false ) && module_config::c( 'login_recaptcha', 0 ) ) { ?>
						<?php echo module_captcha::display_captcha_form(); ?>
						<br>
					<?php } ?>
					<button class="btn btn-lg btn-primary btn-block"
					        type="submit"><?php echo _l( 'Login' ); ?><?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? ' to demo' : ''; ?></button>
					<?php hook_handle_callback( 'login_screen' ); ?>
				</form>
			</div>
		</div>
		<div class="text-center">
			<ul class="list-inline">
				<li><a class="text-muted" href="?"><?php _e( 'Login' ); ?></a></li>
				<li><a class="text-muted" href="?forgot"><?php _e( 'Forgot Password' ); ?></a></li>
				<?php
				if ( module_config::c( 'customer_signup_on_login', 0 ) ) {
					?>
					<li><a class="text-muted"
					       href="<?php echo module_config::c( 'customer_signup_on_login_url', '' ) ?: '?signup'; ?>"><?php _e( 'Sign Up' ); ?></a>
					</li>
					<?php
				}
				?>
			</ul>
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

</div> <!-- /container -->
