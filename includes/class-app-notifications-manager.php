<?php
/**
 * Manage all notifications sent to admin/users/workers
 */
class Appointments_Notifications_Manager {

	/** @var Appointments_Notifications_Confirmation */
	public $confirmation;

	/** @var Appointments_Notifications_Removal  */
	public $removal;

	public function __construct() {
		add_action( 'wpmudev_appointments_update_appointment_status', array( $this, 'change_status' ), 100, 3 );
		add_action( 'wpmudev_appointments_insert_appointment', array( $this, 'insert_appointment' ), 100 );
		add_action( 'appointments_init', array( $this, 'on_init' ) );

		include_once( appointments_plugin_dir() . 'includes/notifications/abstract-app-notification.php' );
		include_once( appointments_plugin_dir() . 'includes/notifications/class-app-notification-confirmation.php' );
		include_once( appointments_plugin_dir() . 'includes/notifications/class-app-notification-removal.php' );
		include_once( appointments_plugin_dir() . 'includes/notifications/class-app-notification-reminder.php' );
		include_once( appointments_plugin_dir() . 'includes/notifications/class-app-notification-notification.php' );
		include_once( appointments_plugin_dir() . 'includes/notifications/class-app-notification-cancel.php' );

		$this->confirmation = new Appointments_Notifications_Confirmation( $this );
		$this->removal = new Appointments_Notifications_Removal( $this );
		$this->reminder = new Appointments_Notifications_Reminder( $this );
		$this->notification = new Appointments_Notifications_Notification( $this );
		$this->cancel = new Appointments_Notifications_Cancel( $this );
	}

	public function on_init( $appointments ) {
		if ( ( time() - get_option( "app_last_update" ) ) < apply_filters( 'app_update_time', 600 ) ) {
			return;
		}

		$this->reminder->send( false );
	}

	public function send_notification( $app_id, $cancel = false ) {
		$options = appointments_get_options();
		if ( ! $cancel && ! isset( $options["send_notification"] ) || 'yes' != $options["send_notification"] ) {
			return appointments_send_notification( $app_id );
		}
		elseif ( $cancel ) {
			return appointments_send_cancel_notification( $app_id );
		}

		return false;
	}


	public function change_status( $app_id, $new_status, $old_status ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return;
		}

		if ( ( 'confirmed' == $new_status || 'paid' == $new_status ) && $new_status != $old_status ) {
			$this->confirmation->send( $app_id );
		}

		if ( 'removed' === $new_status && $new_status != $old_status ) {
			$this->removal->send( $app_id );
		}
	}

	public function insert_appointment( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return;
		}

		// Send confirmation if we forced it
		if ( 'confirmed' == $app->status || 'paid' == $app->status ) {
			$this->confirmation->send( $app_id );
		}
	}

	public function log( $message ) {
		$appointments = appointments();
		$options = appointments_get_options();
		if ( isset( $options["log_emails"] ) && 'yes' == $options["log_emails"] ) {
			$appointments->log( $message );
		}
	}
}



/**
 * Send a confirmation email for this appointment
 *
 * @param $app_id
 *
 * @return bool
 */
function appointments_send_confirmation( $app_id ) {
	$appointments = appointments();
	return $appointments->notifications->confirmation->send( $app_id );
}

/**
 * Send a removal notification for an appointment
 *
 * @param $app_id
 *
 * @return bool
 */
function appointments_send_removal_notification( $app_id ) {
	$appointments = appointments();
	return $appointments->notifications->removal->send( $app_id );
}


/**
 * Send a cancel notification for a given appointment
 *
 * @param $app_id
 *
 * @return bool
 */
function appointments_send_cancel_notification( $app_id ) {
	$appointments = appointments();
	return $appointments->notifications->cancel->send( $app_id );
}

/**
 * Send a notification for a given appointment
 *
 * @param $app_id
 *
 * @return bool
 */
function appointments_send_notification( $app_id ) {
	$appointments = appointments();
	return $appointments->notifications->notification->send( $app_id );
}