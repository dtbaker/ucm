<?php
if ( ! module_social::can_i( 'edit', 'Facebook', 'Social', 'social' ) ) {
	die( 'No access to Facebook accounts' );
}

$social_facebook_id = isset( $_REQUEST['social_facebook_id'] ) ? (int) $_REQUEST['social_facebook_id'] : 0;
$facebook           = new ucm_facebook_account( $social_facebook_id );
if ( $facebook->get( 'social_facebook_id' ) ) {

	// check for postback from our UCM facebook code handling script
	if ( isset( $_REQUEST['c'] ) ) {
		$response = isset( $_REQUEST['c'] ) ? @json_decode( $_REQUEST['c'], true ) : false;
		//print_r($response);
		if ( ! $response || ! $response['code'] ) {
			die( 'Failed to get code from API, please press back and try again.' );
		}
		$code = $response['code'];
		// https://graph.facebook.com/oauth/access_token?code=...&client_id=...&redirect_uri=...&machine_id= ...
		$url        = 'https://graph.facebook.com/oauth/access_token?code=' . urlencode( $code ) . '&redirect_uri=' . urlencode( 'http://ultimateclientmanager.com/api/facebook/logindone.php' ) . '&client_id=608055809278761';
		$machine_id = isset( $response['machine_id'] ) ? $response['machine_id'] : false;
		if ( $machine_id ) {
			$url .= '&machine_id=' . $machine_id;
		}
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$data   = curl_exec( $ch );
		$result = @json_decode( $data, true );
		if ( ! $result || ! isset( $result['access_token'] ) ) {
			die( 'Failed to get client access token from API, please press back and try again: ' . $data );
		}
		$machine_id = isset( $result['machine_id'] ) ? $result['machine_id'] : $machine_id;
		// todo - save machine id along with access_token, use in future requests.
		$facebook->update( 'facebook_token', $result['access_token'] );
		$facebook->update( 'machine_id', $machine_id );
		// success!

		// now we load in a list of facebook pages to manage and redirect the user back to the 'edit' screen where they can continue managing the account.
		$facebook->graph_load_available_pages();
		redirect_browser( module_social_facebook::link_open( $social_facebook_id ) );
	} else if ( $facebook->get( 'facebook_app_id' ) && $facebook->get( 'facebook_app_secret' ) ) {
		// we connect using our own app id / secret
		require_once( 'includes/plugin_social_facebook/inc/facebook.php' );
		$settings     = array(
			'appId'  => $facebook->get( 'facebook_app_id' ),
			'secret' => $facebook->get( 'facebook_app_secret' ),
		);
		$facebook_api = new Facebook( $settings );

		//echo $facebook_api->getLoginUrl(array());

		if ( isset( $_REQUEST['login_completed'] ) && ! empty( $_REQUEST['login_completed'] ) ) {
			// we have logged in, time to test it out!

			ini_set( 'display_errors', true );
			ini_set( 'error_reporting', E_ALL );


			$_SESSION[ 'fb_' . $facebook->get( 'facebook_app_id' ) . '_access_token' ] = $_REQUEST['login_completed'];
			$facebook_api->setAccessToken( $_REQUEST['login_completed'] );


			$access_token = $facebook_api->getAccessToken();;
			echo '<div style="display:none;">';
			echo 'Current access token is: ' . $access_token . '<br>';
			echo '</div>';

			$user = $facebook_api->getUser();

			if ( $user ) {
				try {
					$user_profile = $facebook_api->api( '/me' );
				} catch ( FacebookApiException $e ) {
					$user = null;
				}
			}

			if ( $user ) {
				$facebook_api->setExtendedAccessToken();
				//echo 'extending access token...<br>';
				$access_token = $facebook_api->getAccessToken();;
				//echo 'Current access token is: '.$access_token.'<br>';
				if ( $access_token ) {
					$facebook->update( 'facebook_token', $access_token );
					// success!

					// now we load in a list of facebook pages to manage and redirect the user back to the 'edit' screen where they can continue managing the account.
					$facebook->graph_load_available_pages();
					$url = $facebook->link_edit();
					?>
					<p>Congratulations! You have successfully linked your Facebook account with the Simple Social plugin
						through your own Facebook App. Please click the button below:</p>
					<p><a href="<?php echo $facebook->link_edit(); ?>" class="button">Click here to
							continue.</a></p>
					<?php

					/* $url = 'https://graph.facebook.com/oauth/client_code?access_token='.$access_token.'&redirect_uri='.urlencode( admin_url($facebook->link_connect()) ).'&client_id='.$settings['appId'].'&client_secret='.$settings['secret'].'';
						$response = wp_remote_get( $url );
						if ( is_wp_error( $response ) || !$response['body'] ) {
							$error_message = $response->get_error_message();
							die("Something went wrong: $error_message");
						}
						$result = json_decode($response['body'],true);
						if(isset($result['code']) && strlen($result['code'])){
							// same code as above
							$code = $result['code'];
							// https://graph.facebook.com/oauth/access_token?code=...&client_id=...&redirect_uri=...&machine_id= ...
							$url        = 'https://graph.facebook.com/oauth/access_token?code=' . urlencode( $code ) . '&redirect_uri=' . urlencode( admin_url($facebook->link_connect()) ) . '&client_id=' . $facebook->get( 'facebook_app_id' );
							$machine_id = isset( $result['machine_id'] ) ? $result['machine_id'] : false;
							if ( $machine_id ) {
								$url .= '&machine_id=' . $machine_id;
							}
							$response = wp_remote_get( $url );
							if ( is_wp_error( $response ) || !$response['body'] ) {
								$error_message = $response->get_error_message();
								die("Something went wrong with oauth/access_token: $error_message");
							}
							$result = @json_decode( $response['body'], true );
							if ( ! $result || ! isset( $result['access_token'] ) ) {
								die( 'Failed to get client access token from API, please press back and try again: ' . $data );
							}
							$machine_id = isset( $result['machine_id'] ) ? $result['machine_id'] : $machine_id;
							// todo - save machine id along with access_token, use in future requests.
							$facebook->update( 'facebook_token', $result['access_token'] );
							$facebook->update( 'machine_id', $machine_id );
							// success!

							// now we load in a list of facebook pages to manage and redirect the user back to the 'edit' screen where they can continue managing the account.
							$facebook->graph_load_available_pages();
							$url = $facebook->link_edit();
							?>
							<p>Congratulations! You have successfully linked your Facebook account with the Simple Social plugin through your own Facebook App. Please click the button below:</p>
							<p><a href="<?php echo $facebook->link_edit(); ?>" class="button">Click here to continue.</a></p>
							<?php  */
				} else {
					echo 'Error getting client code from API. Please press back and try again.';
					exit;
				}

				// yay!
			} else {
				echo "incorrect permissions from facebook, go back and try again.";
				exit;
			}
		} else {
			?>

			<div id="fb-root"></div>
			<script type="text/javascript">
          var ucmfacebook = {
              api_url: '',
              loaded: function () {

                  // first thing we do is check the database to see if we have a valid user token.
                  // we use this token along with our app id to see if the user has access via this accout.
                  // this all happens server side without hitting the FB javascript frontend.
                  jQuery('#facebook-login-button').click(function () {
                      ucmfacebook.login_clicked();
                  });


              },
              login_clicked: function () {
                  // only load the js client if we need it.
                  var args = {
                      appId: '<?php echo htmlspecialchars( $facebook->get( 'facebook_app_id' ) );?>', // SS fb app ID
                      status: true,
                      cookie: true
                  };
                  FB.init(args);
                  FB.Event.subscribe('auth.authResponseChange', function (response) {
                      if (response.status === 'connected') {
                          // user has logged in
                          // hide the login button and show the connected state.
                          //alert('connected');
                          ucmfacebook.loggedin(response);
                          jQuery('.facebook-login-button').hide();
                      } else if (response.status === 'not_authorized') {
                          // logged into facebook, but not authorized the app, prompt them to login
                          //alert('Not auth');
                          //FB.login();
                      } else {
                          //alert('Not logged in');
                          // not logged into facebook at the moment
                          //FB.login();
                      }
                  });
                  ucmfacebook.do_initial_login();
              },
              do_initial_login: function () {
                  FB.login(
                      function (response) {
                          if (response.status === 'connected') {
                              //alert('connected during login!');
                              ucmfacebook.loggedin(response);
                          } else if (response.session) {
                              //alert('logged in');
                              //alert('got session!');
                          } else {
                              console.debug(response);
                              alert('Login failed. Please try again.');
                          }
                      },
                      {scope: "manage_pages,read_page_mailboxes,publish_actions,publish_pages"} //, auth_type: 'reauthenticate'
                  );
              },
              loggedin: function (response) {
                  console.log(response);
                  if (typeof response.authResponse != 'undefined' && typeof response.authResponse.accessToken != 'undefined') {
                      // valid token! push this to UCM server so we can get an extended access token for use on our client side.
                      /*amble.api('/facebook_token',{token: response.authResponse.accessToken}, function(data){
											 amble.log('Result from facebook token: ');
											 amble.log(data);
											 })*/
                      window.location = '<?php echo $facebook->link_connect();?>&login_completed=' + response.authResponse.accessToken;
                  }
                  FB.api('/me', function (response) {
                      console.log(response);
                  });
              }
          };
          if (typeof FB == 'undefined') {
              jQuery.getScript('//connect.facebook.net/en_US/all.js', ucmfacebook.loaded);
          } else {
              ucmfacebook.loaded();
          }
			</script>
			<p>Please click the button below to connect your Facebook account:</p>
			<a href="#" id="facebook-login-button"><img
					src="<?php echo full_link( '/includes/plugin_social_facebook/inc/connect.jpg' ); ?>" width="90"
					height="25" title="Connect to Facebook" border="0"></a>

			<?php
		}
	} else {
		// no app / secret defined, use the default UCM ones.
		?>
		<iframe
			src="http://ultimateclientmanager.com/api/facebook/login.php?return=<?php echo urlencode( module_social_facebook::link_open( $social_facebook_id, false, false, 'facebook_account_connect' ) ); ?>&codes=<?php echo urlencode( htmlspecialchars( module_config::c( '_installation_code' ) ) ); ?>"
			frameborder="0" style="width:100%; height:600px; background: transparent" ALLOWTRANSPARENCY="true"></iframe>
		<?php
	}
}
