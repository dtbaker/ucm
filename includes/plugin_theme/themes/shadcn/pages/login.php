<div class="auth-card">
	<div class="auth-brand">
		<?php if ( $header_logo = module_theme::logo_url() ) { ?>
			<img src="<?php echo htmlspecialchars( $header_logo ); ?>" alt=""
			     title="<?php echo htmlspecialchars( module_config::s( 'header_title', 'UCM' ) ); ?>">
		<?php } else { ?>
			<span><?php echo module_config::s( 'header_title', 'UCM' ); ?></span>
		<?php } ?>
	</div>
	<?php if ( isset( $_REQUEST['signup'] ) && module_config::c( 'customer_signup_on_login', 0 ) ) { ?>
		<div class="auth-panel">
			<h2><?php echo _l( 'Sign Up' ); ?></h2>
			<?php
			$form_html = module_customer::get_customer_signup_form_html();
			$form_html = str_replace( '<p><input type="submit" value="Signup Now" /></p>', '', $form_html );
			$form_html = str_replace( '</form>', '<p><input type="submit" class="submit_button" value="' . _l( 'Sign Up' ) . '"></p><p><a href="?login">Cancel</a></p></form>', $form_html );
			echo $form_html;
			?>
		</div>
	<?php } elseif ( isset( $_GET['forgot'] ) ) { ?>
		<div class="auth-panel">
			<?php module_form::print_form_open_tag( array(
				'action' => '',
				'class'  => 'form-signin',
			) ); ?>
			<input type="hidden" name="_process_reset" value="true">
			<h2><?php echo _l( 'Reset Password' ); ?></h2>
			<p class="auth-help"><?php echo _l( 'Please enter your email address below to reset your password.' ); ?></p>
			<input type="email" placeholder="mail@domain.com" id="email" name="email" required="required"
			       class="form-control">
			<?php if ( class_exists( 'module_captcha', false ) ) { ?>
				<?php echo module_captcha::display_captcha_form(); ?>
			<?php } ?>
			<button class="btn btn-primary btn-block" name="reset"
			        type="submit"><?php echo _l( 'Reset Password' ); ?></button>
			<?php hook_handle_callback( 'forgot_password_screen' ); ?>
			<?php module_form::print_form_close_tag(); ?>
		</div>
		<ul class="auth-links">
			<li><a href="?"><?php _e( 'Login' ); ?></a></li>
			<?php if ( module_config::c( 'customer_signup_on_login', 0 ) ) { ?>
				<li><a href="<?php echo module_config::c( 'customer_signup_on_login_url', '' ) ?: '?signup'; ?>"><?php _e( 'Sign Up' ); ?></a></li>
			<?php } ?>
		</ul>
	<?php } else { ?>
		<div class="auth-panel">
			<?php module_form::print_form_open_tag( array(
				'action' => '',
				'class'  => 'form-signin',
			) ); ?>
			<input type="hidden" name="_process_login" value="true">
			<h2><?php _e( 'Login' ); ?></h2>
			<p class="auth-help"><?php _e( 'Enter your username and password' ); ?></p>
			<?php if ( _DEMO_MODE ) { ?>
				<p class="auth-demo">
					<strong>Login details for the UCM demo are:</strong><br/>
					Administrator: admin@example.com / password <br/>
					Customer: user@example.com / password <br/>
					<a href="https://ultimateclientmanager.com/support/faq-knowledge-base/faq-item/?ucm_faq_id=20"
					   target="_blank">FAQ: create your own Customer login</a>
				</p>
			<?php } ?>
			<label for="email"><?php _e( 'Username' ); ?></label>
			<input type="text" placeholder="Username" class="form-control" name="email" id="email"
			       value="<?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? 'admin@example.com' : ''; ?>">
			<label for="password"><?php _e( 'Password' ); ?></label>
			<input type="password" placeholder="Password" class="form-control" name="password" id="password"
			       value="<?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? 'password' : ''; ?>">
			<?php if ( class_exists( 'module_captcha', false ) && module_config::c( 'login_recaptcha', 0 ) ) { ?>
				<?php echo module_captcha::display_captcha_form(); ?>
			<?php } ?>
			<button class="btn btn-primary btn-block"
			        type="submit"><?php echo _l( 'Login' ); ?><?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? ' to demo' : ''; ?></button>
			<?php hook_handle_callback( 'login_screen' ); ?>
			<?php module_form::print_form_close_tag(); ?>
		</div>
		<ul class="auth-links">
			<li><a href="?forgot"><?php _e( 'Forgot Password' ); ?></a></li>
			<?php if ( module_config::c( 'customer_signup_on_login', 0 ) ) { ?>
				<li><a href="<?php echo module_config::c( 'customer_signup_on_login_url', '' ) ?: '?signup'; ?>"><?php _e( 'Sign Up' ); ?></a></li>
			<?php } ?>
		</ul>
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
</div>
