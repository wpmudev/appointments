<?php
/**
 * Manage all notifications sent to admin/users/workers
 */
class Appointments_Notifications {

	public function __construct() {
		$options = appointments_get_options();
		
		$log_emails = isset( $options["log_emails"] ) && 'yes' == $options["log_emails"];

		add_action( 'wpmudev_appointments_update_appointment_status', array( $this, 'on_change_status' ), 10, 3 );
		add_action( 'wpmudev_appointments_insert_appointment', array( $this, 'on_insert_appointment' ) );
	}

	public function on_change_status( $app_id, $new_status, $old_status ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return;
		}

		if ( ( 'confirmed' == $new_status || 'paid' == $new_status ) && $new_status != $old_status ) {
			$this->send_confirmation( $app_id );
		}

		if ( 'removed' === $new_status && $new_status != $old_status ) {
			appointments_send_removal_notification( $app_id );
		}
	}

	public function on_insert_appointment( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return;
		}

		// Send confirmation if we forced it
		if ( 'confirmed' == $app->status || 'paid' == $app->status ) {
			$this->send_confirmation( $app_id );
		}
	}

	function send_confirmation( $app_id ) {
		$appointments = appointments();

		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		$options = appointments_get_options();

		$send_confirmation = isset( $options["send_confirmation"] ) && 'yes' == $options["send_confirmation"];
		if ( ! $send_confirmation ) {
			return false;
		}

		$sent_to = array();
		$customer_email = $app->get_customer_email();
		$result = $this->customer_confirmation( $app_id, $customer_email );

		if ( $result ) {
			$sent_to[] = $customer_email;
			$admin_email = $appointments->get_admin_email();
			if ( ! in_array( $admin_email, $sent_to ) && $this->admin_confirmation( $app_id, $admin_email ) ) {
				$sent_to[] = $admin_email;
			}

			$worker_email = $appointments->get_worker_email( $app->worker );
			if ( ! in_array( $worker_email, $sent_to ) ) {
				$this->admin_confirmation( $app_id, $worker_email );
			}

			return true;

		}

		return false;
	}

	/**
	 * Sends a confirmation email
	 *
	 * @param int $app_id Appointment ID
	 * @param string $email Email to send to
	 *
	 * @return bool True if the email has been sent
	 */
	public function customer_confirmation( $app_id, $email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		if ( ! is_email( $email ) ) {
			return false;
		}

		$template = $this->get_customer_confirmation_template( $app_id, $email );
		if ( ! $template ) {
			return false;
		}

		$attachments = apply_filters( 'app_confirmation_email_attachments', '', $app_id );

		$mail_result = wp_mail(
			$email,
			$template['subject'],
			$template['body'],
			$appointments->message_headers(),
			$attachments
		);

		if ( ! $mail_result ) {
			return false;
		}

		// Log only if it is set so
		if ( isset( $options["log_emails"] ) && 'yes' == $options["log_emails"] ) {
			$appointments->log( sprintf( __('Confirmation message sent to %s for appointment ID:%s','appointments'), $r->email, $app_id ) );
		}

		do_action( 'app_confirmation_sent', $template['body'], $r, $app_id, $email );

		return true;
	}

	/**
	 * Send a confirmation email to admin
	 *
	 * @param $app_id
	 * @param $admin_email
	 *
	 * @return bool|void
	 */
	public function admin_confirmation( $app_id, $admin_email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$disable = apply_filters( 'app_confirmation_disable_admin', false, $r, $app_id );
		if ( $disable ) {
			return true;
		}

		if ( ! is_email( $admin_email ) ) {
			return false;
		}

		$template = $this->get_admin_confirmation_template( $app_id );
		
		if ( ! $template ) {
			return false;
		}

		$result =  wp_mail(
			$admin_email,
			$template['subject'],
			$template['body'],
			$appointments->message_headers()
		);

		if ( $result ) {
			do_action( 'appointments_confirmation_admin_sent', $admin_email, $app_id, $template['body'], $template['subject'] );
		}

		return $result;
	}


	private function get_admin_confirmation_template( $app_id ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$customer_email = $r->get_customer_email();
		if ( ! $customer_email ) {
			return false;
		}

		$provider_add_text  = sprintf( __('A new appointment has been made on %s. Below please find a copy of what has been sent to your client:', 'appointments'), get_option( 'blogname' ) );
		$provider_add_text .= "\n\n\n";

		$subject = $appointments->_replace(
			__('New Appointment','appointments'),
			$r->name,
			$appointments->get_service_name( $r->service),
			appointments_get_worker_name( $r->worker),
			$r->start,
			$r->price,
			$appointments->get_deposit($r->price),
			$r->phone,
			$r->note,
			$r->address,
			$customer_email,
			$r->city
		);

		$body = $this->get_customer_confirmation_template( $app_id, $customer_email );

		return array(
			'subject' => $subject,
			'body' => $provider_add_text . $body['body']
		);
	}

	private function get_customer_confirmation_template( $app_id, $email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$options = appointments_get_options();
		$body = $options['confirmation_message'];

		$body = $appointments->_replace(
			$body,
			$r->name,
			$appointments->get_service_name( $r->service ),
			appointments_get_worker_name( $r->worker ),
			$r->start,
			$r->price,
			$appointments->get_deposit( $r->price ),
			$r->phone,
			$r->note,
			$r->address,
			$email,
			$r->city
		);

		$body = $appointments->add_cancel_link( $body, $app_id );
		$body = apply_filters( 'app_confirmation_message', $body, $r, $app_id );

		$subject = $options["confirmation_subject"];
		$subject = $appointments->_replace(
			$subject,
			$r->name,
			$appointments->get_service_name( $r->service ),
			appointments_get_worker_name( $r->worker),
			$r->start,
			$r->price,
			$appointments->get_deposit($r->price),
			$r->phone,
			$r->note,
			$r->address,
			$email,
			$r->city
		);

		return array(
			'subject' => $subject,
			'body' => $body
		);
	}
}



/**
 * Send a confirmation email fro this appointment
 *
 * @param $app_id
 */
function appointments_send_confirmation( $app_id ) {
	global $appointments;
	$appointments->notifications->send_confirmation( $app_id );
}

/**
 * Send an email when an appointment has been removed
 *
 * @param $app_id
 */
function appointments_send_removal_notification( $app_id ) {
	global $appointments;
	$appointments->send_removal_notification( $app_id );
}

