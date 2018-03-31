<?php


class module_log extends module_base {

	public static $log = array();
	public static $show_log = false;
	public static $start_time = 0;

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function init() {
		$this->module_name     = "log";
		$this->module_position = 1;
		$this->version         = 2.1;
		// 2.1 - 2016-04-30 - initial release
	}

	public static function log( $owner_table, $owner_id, $user_id = false, $comment, $data ) {

		if ( ! module_config::c( 'log_history_for_' . $owner_table, 1 ) ) {
			return;
		}
		if ( ! $user_id ) {
			$user_id = module_security::get_loggedin_id();
		}
		update_insert( 'log_id', false, 'log', array(
			'owner_table' => $owner_table,
			'owner_id'    => $owner_id,
			'user_id'     => $user_id,
			'log_comment' => $comment,
			'log_data'    => serialize( $data ),
		) );

	}

	public static function log_history( $owner_table, $owner_id, $title = false ) {
		if ( ! module_config::c( 'log_history_for_' . $owner_table, 1 ) || ! module_log::can_i( 'view', $owner_table ) ) {
			return false;
		}
		if ( ! $title ) {
			$title = _l( 'View History' );
		}
		ob_start();
		?>
		<a href="#" class="view_log_history" data-owner-table="<?php echo $owner_table; ?>"
		   data-owner-id="<?php echo $owner_id; ?>"><?php echo htmlspecialchars( $title ); ?></a>
		<?php
		return ob_get_clean();
	}


	public function get_install_sql() {
		ob_start();
		?>
		CREATE TABLE `<?php echo _DB_PREFIX; ?>log` (
		`log_id` int(11) NOT NULL auto_increment,
		`owner_table` VARCHAR(255) NOT NULL DEFAULT '',
		`owner_id` INT(11) NOT NULL DEFAULT '0',
		`user_id` INT NOT NULL DEFAULT  '0',
		`urgent` TINYINT (1) NOT NULL DEFAULT  '0',
		`log_comment` TEXT NOT NULL DEFAULT  '',
		`log_data` LONGTEXT NOT NULL DEFAULT  '',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` DATETIME NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`log_id`),
		KEY `owner_id` (`owner_id`),
		KEY `owner_table` (`owner_table`),
		KEY `urgent` (`urgent`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		<?php
		return ob_get_clean();

	}

}

