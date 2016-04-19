<?php

/**
 * Responsible for Appointments installation, uninstall cleanup and maintenance.
 */
class App_Installer {

	public function __construct () {}

	public static function serve () {
		$me = new self;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		register_activation_hook(APP_PLUGIN_FILE, array($this, 'install'));
		register_uninstall_hook(APP_PLUGIN_FILE, array('App_Installer', 'uninstall'));

		add_action('wpmu_new_blog', array($this, 'new_blog'), 10, 6); // Install database tables for a new blog
		add_action('delete_blog', array($this, 'delete_blog'), 10, 2); // Uninstall tables for a deleted blog

		$this->_run_integrity_check();
	}

	public function install () {
		// Create a salt, if it doesn't exist from the previous installation
		if ( !$salt = get_option( "appointments_salt" ) ) {
			$salt = mt_rand();
			add_option( "appointments_salt", $salt ); // Save it to be used until it is cleared manually
		}

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$max_index_length = 191;

		$appmeta = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}app_appointmentmeta` (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  app_appointment_id bigint(20) unsigned NOT NULL default '0',
  meta_key varchar(255) default NULL,
  meta_value longtext,
  PRIMARY KEY  (meta_id),
  KEY app_appointment_id (app_appointment_id),
  KEY meta_key (meta_key($max_index_length))
) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->query( $appmeta );

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}app_appointments` (
		`ID` bigint(20) unsigned NOT NULL auto_increment,
		`created` datetime,
		`user` bigint(20) NOT NULL default '0',
		`name` varchar(250) default NULL,
		`email` varchar(250) default NULL,
		`phone` varchar(250) default NULL,
		`address` varchar(250) default NULL,
		`city` varchar(250) default NULL,
		`location` bigint(20) NOT NULL default '0',
		`service` bigint(20) NOT NULL default '0',
		`worker` bigint(20) NOT NULL default '0',
		`price` bigint(20) default NULL,
		`status` varchar(35) default NULL,
		`start` datetime default NULL,
		`end` datetime default NULL,
		`sent` text,
		`sent_worker` text,
		`note` text,
		`gcal_ID` varchar(250) default NULL,
		`gcal_updated` datetime,
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		// V1.2.0: New gcal field
		$sql_ = "ALTER TABLE `{$wpdb->prefix}app_appointments` ADD (`gcal_ID` varchar(250) default NULL, `gcal_updated` datetime default NULL) ";

		// V1.2.2: make gcal_ID unique
		$sql_0 = "ALTER TABLE `{$wpdb->prefix}app_appointments` ADD UNIQUE (`gcal_ID`) ";

		$sql1 = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}app_transactions` (
		`transaction_ID` bigint(20) unsigned NOT NULL auto_increment,
		`transaction_app_ID` bigint(20) NOT NULL default '0',
		`transaction_paypal_ID` varchar(30) default NULL,
		`transaction_stamp` bigint(35) NOT NULL default '0',
		`transaction_total_amount` bigint(20) default NULL,
		`transaction_currency` varchar(35) default NULL,
		`transaction_status` varchar(35) default NULL,
		`transaction_note` text,
		PRIMARY KEY  (`transaction_ID`),
		KEY `transaction_app_ID` (`transaction_app_ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		$sql2 = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}app_working_hours` (
		`ID` bigint(20) unsigned NOT NULL auto_increment,
		`location` bigint(20) NOT NULL default '0',
		`service` bigint(20) NOT NULL default '0',
		`worker` bigint(20) NOT NULL default '0',
		`status` varchar(30) default NULL,
		`hours` text,
		`note` text,
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		// TODO: Make this WP time format free
		$sql21 = "INSERT INTO {$wpdb->prefix}app_working_hours (ID, location, worker,  `status`, hours, note) VALUES
		(NULL, 0, 0, 'open', 'a:7:{s:6:\"Sunday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"1:00 pm\";}s:6:\"Monday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:7:\"Tuesday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:9:\"Wednesday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:8:\"Thursday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:6:\"Friday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:8:\"Saturday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"1:00 pm\";}}', NULL),
		(NULL, 0, 0, 'closed', 'a:7:{s:6:\"Sunday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:6:\"Monday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:7:\"Tuesday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:9:\"Wednesday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:8:\"Thursday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:6:\"Friday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:8:\"Saturday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}}', NULL);	 	 	 	 	   		 	 			
		";

		$sql3 = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}app_exceptions` (
		`ID` bigint(20) unsigned NOT NULL auto_increment,
		`location` bigint(20) NOT NULL default '0',
		`service` bigint(20) NOT NULL default '0',
		`worker` bigint(20) NOT NULL default '0',
		`status` varchar(30) default NULL,
		`days` text,
		`note` text,
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";


		$sql4 = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}app_services` (
		`ID` bigint(20) unsigned,
		`name` varchar(255) default NULL,
		`capacity` bigint(20) NOT NULL default '0',
		`duration` bigint(20) NOT NULL default '0',
		`price` varchar(255) default NULL,
		`page` bigint(20) NOT NULL default '0',
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		$sql41 = "INSERT INTO {$wpdb->prefix}app_services (ID, `name`, capacity, duration, `price`, page)
		VALUES (1, 'Default Service', 0, 30, '1', 0)
		";

		$sql5 = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}app_workers` (
		`ID` bigint(20) unsigned,
		`dummy` varchar(255) default NULL,
		`price` varchar(255) default NULL,
		`services_provided` text,
		`page` bigint(20) NOT NULL default '0',
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		// V1.0.6: New dummy field
		$sql51 = "ALTER TABLE `{$wpdb->prefix}app_workers` ADD `dummy` varchar(255) DEFAULT NULL AFTER `ID` ";

		$sql6 = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}app_cache` (
		`uri` varchar(255) default NULL,
		`created` datetime,
		`content` longtext,
		`script` longtext,
		 UNIQUE (`uri`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		$wpdb->query($sql);
		$wpdb->query($sql1);
		// Add default working hours
		$wpdb->query($sql2);
		$count = $wpdb->get_var( "SELECT COUNT(ID) FROM " . $wpdb->prefix . "app_working_hours " );
		if ( !$count )
			$wpdb->query($sql21);
		$wpdb->query($sql3);
		// Add default service
		$wpdb->query($sql4);
		$count = $wpdb->get_var( "SELECT COUNT(ID) FROM " . $wpdb->prefix . "app_services " );
		if ( !$count )
			$wpdb->query($sql41);
		$wpdb->query($sql5);
		$wpdb->query($sql6);

		// If no DB version, this is a new installation
		if ( !$db_version = get_option( 'app_db_version' ) ) {
			$db_version = "1.2.3";
			update_option( 'app_db_version', '1.2.3' );
		}

		// Update database for versions less than 1.0.6
		if ( version_compare( $db_version, '1.0.6', '<' ) ) {
			$result = $wpdb->query($sql51);
			if ( $result || $wpdb->query( "SHOW COLUMNS FROM " . $wpdb->prefix . "app_workers" . " LIKE 'dummy' " ) )
				update_option( 'app_db_version', '1.0.6' );
		}
		// Check and update database for versions less than 1.1.8
		if( version_compare( $db_version, '1.1.8', '<' ) ) {
			$table_status = $wpdb->get_row( "SHOW TABLE STATUS LIKE '" . $wpdb->prefix . "app_services' " );

			if ( 'utf8_general_ci' != $table_status->Collation ) {
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_appointments" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_transactions" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_working_hours" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_exceptions" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_services" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_workers" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_cache" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
			}
			update_option( 'app_db_version', '1.1.8' );
		}
		// Update database for versions less than 1.2.0
		if ( version_compare( $db_version, '1.2.0', '<' ) ) {
			$result = $wpdb->query($sql_);
			if ( $result || $wpdb->query( "SHOW COLUMNS FROM " . $wpdb->prefix . "app_appointments" . " LIKE 'gcal_ID' " ) )
				update_option( 'app_db_version', '1.2.0' );
		}

		// Update database for versions less than 1.2.2
		// Aaand... this never ran previously :/ 
		// Okay then, leave this as is and deal with it differently.
		if ( version_compare( $db_version, '1.2.2', '<' ) ) {
			$result = $wpdb->query($sql_0);
			update_option( 'app_db_version', '1.2.2' );
		}

		// Check AppointmentsGcal::import_and_update_events()
		// for v1.2.3 DB schema update

		$this->_upgrade_to_124();

	}

	/**
	 * For some reason, appointment prices were integer only!
	 */
	private function _upgrade_to_124 () {
		global $wpdb;
		$db_version = get_option('app_db_version');
		// Check for floating point prices in appointments
		if ( version_compare( $db_version, '1.2.4', '<' ) ) {
			$sql = "ALTER TABLE `{$wpdb->prefix}app_appointments` CHANGE COLUMN `price` `price` FLOAT NULL DEFAULT NULL AFTER `worker`;";
			$result = $wpdb->query($sql);
			update_option( 'app_db_version', '1.2.4' );
		}
	}

	public static function uninstall () {
		global $wpdb;

		wp_unschedule_event( current_time( 'timestamp' ), 'appointments_gcal_sync' );

		delete_option( 'appointments_options' );
		delete_option( 'app_last_update' );
		delete_option( 'app_db_version' );

		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_working_hours" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_exceptions" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_services" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_workers" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_appointments" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_appointmentmeta" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_transactions" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_cache" );

		// Delete user metas
		$wpdb->query( "DELETE FROM " . $wpdb->usermeta . " WHERE meta_key='app_api_mode' OR meta_key='app_service_account'
			OR meta_key='app_key_file' OR meta_key='app_selected_calendar' OR meta_key='app_gcal_summary' OR meta_key='app_gcal_description' OR meta_key LIKE 'app_dismiss%' " );

		// Remove all possible folders with their contents
		$uploads = wp_upload_dir();
		if (isset($uploads["basedir"])) $uploads_dir = $uploads["basedir"] . "/";
		else $uploads_dir = WP_CONTENT_DIR . "/uploads/";

		self::_rmdir_p( $uploads_dir . '__app/' );
		if (defined('AUTH_KEY')) self::_rmdir_p( $uploads_dir . md5( 'AUTH_KEY' ) . '/' );
	}

	public function new_blog ($blog_id, $user_id, $domain, $path, $site_id, $meta) {
		global $wpdb;

		if (!function_exists('is_plugin_active_for_network')) require_once(ABSPATH . '/wp-admin/includes/plugin.php');

		if (is_plugin_active_for_network('appointments/appointments.php')) {
			//$old_blog = $wpdb->blogid;
			switch_to_blog($blog_id);
			$this->install();
			//switch_to_blog( $old_blog );
			restore_current_blog();
		}
	}

	public function delete_blog ($blog_id, $drop) {
		global $wpdb;

		if ( $blog_id >1 ) {
			//$old_blog = $wpdb->blogid;
			switch_to_blog( $blog_id );
			//_wpmudev_appointments_uninstall( );
			self::uninstall();
			restore_current_blog();
			//switch_to_blog( $old_blog );
		}
	}

	private static function _rmdir_p ($dir) {
		foreach( glob($dir . '/*') as $file ) {
			if( is_dir( $file ) )
				@self::_rmdir_p( $file );
			else
				@unlink( $file );
		}
		@rmdir( $dir );
	}

	private function _run_integrity_check () {
		$db_version = get_option('app_db_version', false);
		if (!$db_version) {
			$this->install();
		} else if (version_compare($db_version, '1.2.2', '<') && is_multisite()) {
			if (!function_exists('is_plugin_active_for_network')) require_once(ABSPATH . '/wp-admin/includes/plugin.php');
			if (is_plugin_active_for_network('appointments/appointments.php')) {
				$this->install();
			}
		} else {
			$this->_upgrade_to_124();
		}
	}
}