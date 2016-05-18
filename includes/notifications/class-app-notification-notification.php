<?php

class Appointments_Notifications_Notification extends Appointments_Notification {

	public function send( $app_id ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$options = appointments_get_options();

		$admin_email = apply_filters( 'app_notification_email', $appointments->get_admin_email(), $r );

		$template = $this->get_admin_template( $app_id, $admin_email );

		$mail_result = wp_mail(
			$admin_email,
			$template['subject'],
			$template['body'],
			$appointments->message_headers()
		);

		$sent_to = array();
		if ( $mail_result ) {
			$sent_to[] = $admin_email;
			$this->manager->log( sprintf( __('Notification message sent to %s for appointment ID:%s','appointments'), $admin_email, $app_id ) );
			do_action( 'app_notification_sent', $template['body'], $r, $app_id );
		}

		// Also notify service provider if he is allowed to confirm it
		// Note that message itself is different from that of the admin
		// Don't send repeated email to admin if he is the provider
		$worker_email = $appointments->get_worker_email( $r->worker );
		if ( ! in_array( $worker_email, $sent_to ) && isset( $options['allow_worker_confirm'] ) && 'yes' == $options['allow_worker_confirm'] ) {

			$worker_template = $this->get_worker_template( $app_id, $worker_email );

			$mail_result = wp_mail(
				$worker_email,
				$worker_template['subject'],
				$worker_template['body'],
				$appointments->message_headers()
			);

			if ( $mail_result ) {
				$this->manager->log( sprintf( __('Notification message sent to %s for appointment ID:%s','appointments'), $worker_email, $app_id ) );
				do_action( 'appointments_worker_notification_sent', $worker_template['body'], $r, $app_id );
			}
		}
		return true;
	}

	private function get_worker_template( $app_id, $email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		if ( ! is_email( $email ) ) {
			return false;
		}

		$admin_template = $this->get_admin_template( $app_id, $email );

		/* Translators: First %s is for appointment ID and the second one is for date and time of the appointment */
		$body    = sprintf( __('The new appointment has an ID %s and you can edit it clicking this link: %s','appointments'), $app_id, admin_url("admin.php?page=appointments&type=pending") );
		$body    = apply_filters( 'app-messages-worker-notification', $body, $r, $app_id );
		$subject = apply_filters( 'app-messages-worker-notification-subject', $admin_template['subject'], $r, $app_id );

		return array(
			'body' => $body,
			'subject' => $subject
		);

	}

	private function get_admin_template( $app_id, $email ) {
		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		if ( ! is_email( $email ) ) {
			return false;
		}

		$subject = __('An appointment requires your confirmation', 'appointments');
		$body = sprintf( __('The new appointment has an ID %s and you can edit it clicking this link: %s','appointments'), $app_id, admin_url("admin.php?page=appointments&type=pending") );

		$body = apply_filters( 'app-messages-notification-body', $body, $r, $app_id );
		$body = apply_filters( 'app_notification_message', $body, $r, $app_id );

		$subject = apply_filters( 'app-messages-notification-subject', $subject, $r, $app_id );

		return array(
			'body' => $body,
			'subject' => $subject
		);

	}
}