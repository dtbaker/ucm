<?php

class module_job_discussion extends module_base {

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	function init() {
		$this->links           = array();
		$this->module_name     = "job_discussion";
		$this->module_position = 17.1; //17 is job

		$this->version = 2.153;
		// 2.1 - initial
		// 2.11 - date change
		// 2.12 - better linking and auto open
		// 2.13 - possible bug fix in discussion saving
		// 2.14 - bug fixing.
		// 2.141 - bug fix for IE.
		// 2.142 - send email to staff option (as well as customer)
		// 2.143 - 2013-07-29 - new _UCM_SECRET hash in config.php
		// 2.144 - 2013-12-06 - javascript for for new jquery
		// 2.145 - 2014-03-17 - make discussion button stand out a bit more ( job_discussion_button_label )
		// 2.146 - 2014-03-18 - fix for disabling job discussion plugin
		// 2.147 - 2014-05-27 - email discussions to multiple customer contacts
		// 2.148 - 2015-01-08 - AdminLTE modal popup discussions (thanks 3wCorner!)
		// 2.149 - 2015-01-10 - job discussion bug fix
		// 2.150 - 2016-07-10 - big update to mysqli
		// 2.151 - 2016-11-16 - fontaweome icons
		// 2.152 - 2017-02-21 - email log errors
		// 2.153 - 2017-02-26 - job discussion email issue

		if ( self::is_plugin_enabled() ) {
			module_config::register_css( 'job_discussion', 'job_discussion.css' );
			module_config::register_js( 'job_discussion', 'job_discussion.js' );

			//if(self::can_i('view','Job Discussions')){
			if ( get_display_mode() != 'mobile' ) {
				hook_add( 'job_task_after', 'module_job_discussion::hook_job_task_after' );
			}
		}


		if ( class_exists( 'module_template', false ) ) {
			module_template::init_template( 'job_discussion_email_customer', 'Dear {CUSTOMER_NAME},<br>
<br>
A new comment has been added to a task in your job: {JOB_NAME}.<br><br>
Task: {TASK_NAME} <br/><br/>
Note: {NOTE}<br/><br/>
You can view this job and the comments online by <a href="{JOB_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'New Job Comment: {JOB_NAME}', array(
				'CUSTOMER_NAME' => 'Customers Name',
				'JOB_NAME'      => 'Job Name',
				'FROM_NAME'     => 'Your name',
				'JOB_URL'       => 'Link to job for customer',
				'TASK_NAME'     => 'name of the task the note was added to',
				'NOTE'          => 'Copy of the note',
			) );

			module_template::init_template( 'job_discussion_email_staff', 'Dear {STAFF_NAME},<br>
<br>
A new comment has been added to a task in your job: {JOB_NAME}.<br><br>
Task: {TASK_NAME} <br/><br/>
Note: {NOTE}<br/><br/>
You can view this job and the comments online by <a href="{JOB_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'New Comment on your Job: {JOB_NAME}', array(
				'STAFF_NAME' => 'Staff Name',
				'JOB_NAME'   => 'Job Name',
				'FROM_NAME'  => 'Your name',
				'JOB_URL'    => 'Link to job for staff member',
				'TASK_NAME'  => 'name of the task the note was added to',
				'NOTE'       => 'Copy of the note',
			) );
		}


	}

	public static function link_public( $job_id, $task_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for job discussion ' . _UCM_SECRET . ' ' . $job_id . ' with task ' . $task_id );
		}
		$url = _EXTERNAL_TUNNEL_REWRITE . 'm.job_discussion/h.public/i.' . $job_id . '/t.' . $task_id . '/hash.' . self::link_public( $job_id, $task_id, true );

		return full_link( $url );
	}


	public static function external_hook( $hook ) {

		switch ( $hook ) {
			case 'public':
				$job_id  = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$task_id = ( isset( $_REQUEST['t'] ) ) ? (int) $_REQUEST['t'] : false;
				$hash    = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $job_id && $task_id && $hash ) {
					$correct_hash = self::link_public( $job_id, $task_id, true );
					if ( $correct_hash == $hash ) {
						module_job_discussion::print_discussion( $job_id, $task_id );
					}
				}
				break;
		}
	}

	public static function print_discussion( $job_id, $task_id, $job_data = array(), $task_data = array(), $allow_new = true ) {

		$job_data = $job_data ? $job_data : module_job::get_job( $job_id, true, true );

		if ( $job_data && isset( $job_data['job_discussion'] ) && $job_data['job_discussion'] == 1 ) {
			// disabled & hidden.
			return;
		}
		$task_data = $task_data ? $task_data : module_job::get_task( $job_id, $task_id );

		$comments        = get_multiple( 'job_discussion', array(
			'job_id'  => $job_id,
			'task_id' => $task_id
		), 'job_discussion_id', 'exact', 'job_discussion_id' );
		$current_user_id = module_security::get_loggedin_id();
		$customer        = module_customer::get_customer( $job_data['customer_id'] );
		if ( ! $current_user_id ) {
			if ( $job_data['customer_id'] && $customer['primary_user_id'] ) {
				$current_user_id = $customer['primary_user_id'];
			}
		}

		include module_theme::include_ucm( 'includes/plugin_job_discussion/inc/comment_list.php' );

	}

	public static function hook_job_task_after( $hook, $job_id, $task_id, $job_data, $task_data ) {

		$comments = get_multiple( 'job_discussion', array( 'job_id'  => $job_id,
		                                                   'task_id' => $task_id
		), 'job_discussion_id', 'exact', 'job_discussion_id' );

		if ( $job_data && isset( $job_data['job_discussion'] ) && $job_data['job_discussion'] == 1 ) {
			// disabled & hidden.
			return;
		}
		if ( $job_data && isset( $job_data['job_discussion'] ) && $job_data['job_discussion'] == 2 && count( $comments ) == 0 ) {
			// disabled & shown.
			return;
		}


		if ( isset( $_POST['job_discussion_add_job_id'] ) && isset( $_POST['job_discussion_add_task_id'] ) && $_POST['job_discussion_add_job_id'] == $job_id && $_POST['job_discussion_add_task_id'] == $task_id && isset( $_POST['note'] ) && strlen( $_POST['note'] ) ) {

			$x = 0;
			while ( ob_get_level() && $x ++ < 10 ) {
				ob_end_clean();
			}

			$current_user_id = module_security::get_loggedin_id();
			$customer        = module_customer::get_customer( $job_data['customer_id'] );
			if ( ! $current_user_id ) {
				if ( $job_data['customer_id'] && $customer['primary_user_id'] ) {
					$current_user_id = $customer['primary_user_id'];
				}
			}

			$result = array();

			// adding a new note.
			$job_discussion_id           = update_insert( 'job_discussion_id', 0, 'job_discussion', array(
				'job_id'  => $job_id,
				'task_id' => $task_id,
				'user_id' => $current_user_id,
				'note'    => $_POST['note'],
			) );
			$result['job_discussion_id'] = $job_discussion_id;
			$result['count']             = count( $comments ) + 1;
			$tasks                       = module_job::get_tasks( $job_id );
			$result['email_customer']    = array();
			if ( isset( $_POST['sendemail_customer'] ) && is_array( $_POST['sendemail_customer'] ) ) { //$_POST['sendemail_customer'] == 'yes' && $customer['primary_user_id']){
				// send email to customer primary user id.
				$customer_contacts = module_user::get_contacts( array( 'customer_id' => $job_data['customer_id'] ) );
				foreach ( $_POST['sendemail_customer'] as $user_id ) {
					$user_id = (int) $user_id;
					if ( $user_id && isset( $customer_contacts[ $user_id ] ) ) {
						// we can email this user.
						$user = module_user::get_user( $user_id, false );
						if ( $user && $user['user_id'] == $user_id ) {
							$values                  = array_merge( $user, $job_data );
							$values['job_url']       = module_job::link_public( $job_id );
							$values['job_url']       .= ( strpos( $values['job_url'], '?' ) === false ? '?' : '&' ) . 'discuss=' . $task_id . '#discuss' . $task_id;
							$values['job_name']      = $job_data['name'];
							$values['customer_name'] = $user['name'] . ' ' . $user['last_name'];
							$values['note']          = $_POST['note'];
							//todo: no order if no showning numbers
							$values['task_name'] = '#' . $tasks[ $task_id ]['task_order'] . ': ' . $tasks[ $task_id ]['description'];

							$template = module_template::get_template_by_key( 'job_discussion_email_customer' );
							$template->assign_values( $values );
							$html = $template->render( 'html' );

							$email                 = module_email::new_email();
							$email->replace_values = $values;
							$email->set_to( 'user', $user['user_id'] );
							//		                    $email->set_from('user',$current_user_id);
							$from_user = module_user::get_user( $current_user_id );
							$email->set_reply_to( $from_user['email'], $from_user['name'] );
							$email->set_subject( $template->description );
							// do we send images inline?
							$email->set_html( $html );

							if ( $email->send() ) {
								// it worked successfully!!
								$result['email_customer'][] = $user['user_id'];
							} else {
								/// log err?
								$result['email_customer_error'] = array(
									'status'     => $email->status,
									'error_text' => $email->error_text,
								);
							}
						}
					}
				}
				/*$user = module_user::get_user($customer['primary_user_id'],false);
				if($user['user_id'] == $customer['primary_user_id']){
						$values = array_merge($user,$job_data);
						$values['job_url'] = module_job::link_public($job_id);
						$values['job_url'] .= (strpos($values['job_url'],'?')===false ? '?' : '&').'discuss='.$task_id.'#discuss'.$task_id;
						$values['job_name'] = $job_data['name'];
						$values['customer_name'] = $user['name'].' '.$user['last_name'];
						$values['note'] = $_POST['note'];
						//todo: no order if no showning numbers
						$values['task_name'] = '#'.$tasks[$task_id]['task_order'].': '.$tasks[$task_id]['description'];

						$template = module_template::get_template_by_key('job_discussion_email_customer');
						$template->assign_values($values);
						$html = $template->render('html');

						$email = module_email::new_email();
						$email->replace_values = $values;
						$email->set_to('user',$user['user_id']);
						$email->set_from('user',$current_user_id);
						$email->set_subject($template->description);
						// do we send images inline?
						$email->set_html($html);

						if($email->send()){
								// it worked successfully!!
								$result['email_customer'] = 1;
						}else{
								/// log err?
								$result['email_customer'] = 0;
						}
				}else{
						// log error?
						$result['email_customer'] = 0;
				}*/

			}
			if ( isset( $_POST['sendemail_staff'] ) && is_array( $_POST['sendemail_staff'] ) ) { // == 'yes' && $job_data['user_id']
				// todo: handle the restul better when sending to multiple people
				$result['email_staff_list'] = $_POST['sendemail_staff'];
				foreach ( $_POST['sendemail_staff'] as $staff_id ) {
					// send email to staff
					$staff_id = (int) $staff_id;
					if ( ! $staff_id ) {
						$result['nostaff'] = 1;
						continue;
					}

					if (
						isset( $task_data['user_id'] ) && $task_data['user_id'] == $staff_id
						||
						isset( $job_data['user_id'] ) && $job_data['user_id'] == $staff_id
					) {

						//$user = module_user::get_user($job_data['user_id'],false);
						$user = module_user::get_user( $staff_id, false );
						if ( $user['user_id'] == $staff_id ) {
							$values               = array_merge( $user, $job_data );
							$values['job_url']    = module_job::link_public( $job_id );
							$values['job_url']    .= ( strpos( $values['job_url'], '?' ) === false ? '?' : '&' ) . 'discuss=' . $task_id . '#discuss' . $task_id;
							$values['job_name']   = $job_data['name'];
							$values['staff_name'] = $user['name'] . ' ' . $user['last_name'];
							$values['note']       = $_POST['note'];
							//todo: no order if no showning numbers
							$values['task_name'] = '#' . $tasks[ $task_id ]['task_order'] . ': ' . $tasks[ $task_id ]['description'];

							$template = module_template::get_template_by_key( 'job_discussion_email_staff' );
							$template->assign_values( $values );
							$html = $template->render( 'html' );

							$email                 = module_email::new_email();
							$email->replace_values = $values;
							$email->set_to( 'user', $staff_id );
							$from_user = module_user::get_user( $current_user_id );
							$email->set_reply_to( $from_user['email'], $from_user['name'] );
							//$email->set_from('user',$current_user_id);
							$email->set_subject( $template->description );
							// do we send images inline?
							$email->set_html( $html );

							if ( $email->send() ) {
								// it worked successfully!!
								$result['email_staff'] = 1;
							} else {
								$result['email_staff']       = 0;
								$result['email_staff_error'] = array(
									'status'     => $email->status,
									'error_text' => $email->error_text,
								);
								/// log err?
							}
						} else {
							// log error?
							$result['email_staff']       = 0;
							$result['email_staff_error'] = array(
								'user_id_match' => $staff_id . ' != ' . $user['user_id'],
							);
						}
					}
				}

			}
			$x = 0;
			while ( $x ++ < 5 && ob_get_level() ) {
				ob_end_clean();
			}
			header( "Content-type: text/javascript", true );
			echo json_encode( $result );
			exit;
		}

		$label = htmlspecialchars( module_config::c( 'job_discussion_button_label', 'Task Comments' ) );
		?>
		<a href="<?php echo self::link_public( $job_id, $task_id ); ?>" id="discuss<?php echo $task_id; ?>"
		   class="task_job_discussion <?php echo $label ? 'with_text' : ''; ?>"
		   title="<?php _e( 'View Discussion' ); ?>"><span><?php echo count( $comments ) > 0 ? count( $comments ) : ''; ?></span><?php echo $label; ?>
		</a>
		<div
			class="task_job_discussion_holder"<?php echo isset( $_REQUEST['discuss'] ) && $_REQUEST['discuss'] == $task_id ? ' style="display:block;"' : ''; ?>>
			<?php if ( isset( $_REQUEST['discuss'] ) && $_REQUEST['discuss'] == $task_id ) {
				$_REQUEST['t']    = $task_id;
				$_REQUEST['i']    = $job_id;
				$_REQUEST['hash'] = self::link_public( $job_id, $task_id, true );
				self::external_hook( 'public' );
			} ?>
		</div>
		<?php
	}


	public function get_install_sql() {
		ob_start();
		?>
		CREATE TABLE `<?php echo _DB_PREFIX; ?>job_discussion` (
		`job_discussion_id` int(11) NOT NULL auto_increment,
		`job_id` INT(11) NULL,
		`task_id` INT(11) NULL,
		`user_id` INT NOT NULL DEFAULT  '0',
		`seen` TINYINT (1) NOT NULL DEFAULT  '0',
		`note` TEXT NOT NULL DEFAULT  '',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` DATETIME NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`job_discussion_id`),
		KEY `job_id` (`job_id`),
		KEY `task_id` (`task_id`),
		KEY `seen` (`seen`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		<?php
		return ob_get_clean();

	}
}