<?php if ( $header_logo = module_theme::get_config( 'theme_logo', _BASE_HREF . 'images/logo.png' ) ) { ?>
	<div class="text-center logo-box">
		<img src="<?php echo htmlspecialchars( $header_logo ); ?>" border="0"
		     title="<?php echo htmlspecialchars( module_config::s( 'header_title', 'UCM' ) ); ?>">
	</div>
<?php } ?>

<?php if ( isset( $_REQUEST['signup'] ) && module_config::c( 'customer_signup_on_login', 0 ) ) { ?>

	<div id="signup" class="tab-pane">

		<div class="form-box" id="forgot-box">
			<div class="header"><?php _e( 'Sign Up' ); ?></div>
			<div class="body bg-gray">
				<?php
				$form_html = module_customer::get_customer_signup_form_html();
				$form_html = str_replace( '<p><input type="submit" value="Signup Now" /></p>', '<div class="footer"> <button type="submit" class="btn bg-loginbtn btn-block">' . _l( 'Sign Up' ) . '</button> <p><a href="?login">Cancel</a></p></div>', $form_html );
				echo $form_html;
				?>
			</div>
		</div>
	</div>

<?php } else if ( isset( $_REQUEST['forgot_password'] ) ) { ?>

	<div id="forgot" class="tab-pane">
		<div class="form-box" id="forgot-box">
			<div class="header"><?php _e( 'Reset Password' ); ?></div>
			<form action="" class="form-signin" method="post">
				<input type="hidden" name="_process_reset" value="true">
				<div class="body bg-gray">
					<p
						class="text-muted text-center"><?php echo _l( 'Please enter your email address below to reset your password.' ); ?></p>
					<div class="form-group">
						<input type="email" name="email" id="email" class="form-control" placeholder="mail@domain.com" value=""/>
					</div>
				</div>
				<?php if ( class_exists( 'module_captcha', false ) ) { ?>
					<div class="body bg-gray">
						<?php echo module_captcha::display_captcha_form(); ?>
						<br/>
					</div>
				<?php } ?>
				<div class="footer">
					<button type="submit" name="reset"
					        class="btn bg-loginbtn btn-block"><?php echo _l( 'Reset Password' ); ?></button>
					<p><a href="?login"><?php _e( 'Back to Login' ); ?></a></p>
				</div>
				<?php hook_handle_callback( 'login_screen' ); ?>
			</form>
		</div>
	</div>
<?php } else { ?>
	<div id="login" class="tab-pane active">
		<div class="form-box" id="login-box">
			<div class="header"><?php _e( 'Sign In' ); ?></div>
			<form action="" class="form-signin" method="post">
				<input type="hidden" name="_process_login" value="true">
				<?php if ( _DEMO_MODE ) { ?>
					<div class="body bg-gray">
						<div class="callout callout-info" style="margin-bottom: 0">
							<strong>Welcome to the <a
									href="http://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker&utm_source=Demo&utm_medium=Header&utm_campaign=AdminLTE&utm_content=AdminLTE"
									target="_blank">UCM Pro</a> Demo. <br/>Login details are:</strong><br/>
							Admin: admin@example.com / password <br/>
							Customer: user@example.com / password <br/>
							Staff: staff@example.com / password <br/>
						</div>
					</div>
				<?php } ?>
				<div class="body bg-gray">
					<div class="form-group">
						<input type="text" name="email" id="email" class="form-control" placeholder="<?php _e( 'Username' ); ?>"
						       value="<?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? 'admin@example.com' : ''; ?>"/>
					</div>
					<div class="form-group">
						<input type="password" name="password" id="password" class="form-control"
						       placeholder="<?php _e( 'Password' ); ?>"
						       value="<?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? 'password' : ''; ?>"/>
					</div>
				</div>
				<?php if ( class_exists( 'module_captcha', false ) && module_config::c( 'login_recaptcha', 0 ) ) { ?>
					<div class="body bg-gray">
						<?php echo module_captcha::display_captcha_form(); ?>
						<br/>
					</div>
				<?php } ?>
				<div class="footer">
					<button type="submit"
					        class="btn bg-loginbtn btn-block"><?php echo _l( 'Login' ); ?><?php echo ( defined( '_DEMO_MODE' ) && _DEMO_MODE ) ? ' to demo' : ''; ?></button>
					<p>
						<a href="?forgot_password"><?php _e( 'Forgot Password' ); ?></a>
						<?php
						if ( module_config::c( 'customer_signup_on_login', 0 ) ) {
							?>
							| <a
								href="<?php echo module_config::c( 'customer_signup_on_login_url', '' ) ?: '?signup'; ?>"><?php _e( 'Sign Up' ); ?></a>
							<?php
						}
						?>
					</p>
				</div>
				<?php hook_handle_callback( 'login_screen' ); ?>
			</form>
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
