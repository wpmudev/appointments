<?php

class Appointments_Notifications_Removal extends Appointments_Notification {

	/**
	 * Send a notification when an Appointment is removed
	 *
	 * @param integer $app_id Appointment ID
	 *
	 * @return bool True if notificaiton was sent
	 */
	public function send( $app_id ) {
		$appointments = appointments();

		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		$options = appointments_get_options();

		$send_removal = isset( $options["send_removal_notification"] ) && 'yes' != $options["send_removal_notification"];
		if ( ! $send_removal ) {
			return false;
		}

		$sent_to = array();
		$customer_email = $app->get_customer_email();
		$result = $this->customer( $app->ID, $customer_email );

		if ( $result ) {
			$sent_to[] = $customer_email;
			$admin_email = $appointments->get_admin_email();
			if ( ! in_array( $admin_email, $sent_to ) && $this->admin( $app_id, $admin_email ) ) {
				$sent_to[] = $admin_email;
			}

			$worker_email = $appointments->get_worker_email( $app->worker );
			if ( ! in_array( $worker_email, $sent_to ) ) {
				$this->admin( $app_id, $worker_email );
			}

			return true;

		}

		return $result;
	}


	/**
	 * Send a removal email to admin
	 *
	 * @param $app_id
	 * @param $admin_email
	 *
	 * @return bool|void
	 */
	public function admin( $app_id, $admin_email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$disable = apply_filters( 'app_removal_notification_disable_admin', false, $r, $app_id );
		if ( $disable ) {
			return true;
		}

		if ( ! is_email( $admin_email ) ) {
			return false;
		}

		$template = $this->get_admin_template( $app_id );

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
			do_action( 'appointments_removal_admin_sent', $admin_email, $app_id, $template['body'], $template['subject'] );
		}

		return $result;
	}

	/**
	 * Sends a removal email to the customer
	 *
	 * @param int $app_id Appointment ID
	 * @param string $email Email to send to
	 *
	 * @return bool True if the email has been sent
	 */
	public function customer( $app_id, $email ) {
		$appointments = appointments();

		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		if ( ! is_email( $email ) ) {
			$this->manager->log( sprintf( __( 'Unable to notify the client about the appointment ID:%s removal, stopping.', 'appointments' ), $app_id ) );
			return false;
		}

		$template = $this->get_customer_template( $app_id, $email );
		if ( ! $template ) {
			return false;
		}

		$mail_result = wp_mail(
			$email,
			$template['subject'],
			$template['body'],
			$appointments->message_headers()
		);

		if ( ! $mail_result ) {
			return false;
		}

		$this->manager->log( sprintf( __('Removal message sent to %s for appointment ID:%s','appointments'), $app->email, $app_id ) );

		do_action( 'app_removal_sent', $template['body'], $app, $app_id, $email );

		return true;

	}

	private function get_admin_template( $app_id ) {
		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$customer_email = $r->get_customer_email();
		if ( ! $customer_email ) {
			return false;
		}

		$provider_add_text  = sprintf(__('An appointment removal notification for ID %s has been sent to your client:', 'appointments'), $app_id);
		$provider_add_text .= "\n\n\n";

		$subject = __('Removal notification', 'appointments');

		$body = $this->get_customer_template( $app_id, $customer_email );

		return array(
			'subject' => $subject,
			'body' => $provider_add_text . $body['body']
		);
	}

	public function get_customer_template( $app_id, $email ) {
		$appointments = appointments();

		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		$options = appointments_get_options();

		$body = ! empty( $options['removal_notification_message'] ) ? $options['removal_notification_message'] : $body = $this->get_default_customer_body();

		$body = $appointments->_replace(
			$body,
			$app->name,
			$appointments->get_service_name( $app->service ),
			appointments_get_worker_name( $app->worker ),
			$app->start,
			$app->price,
			$appointments->get_deposit( $app->price ),
			$app->phone,
			$app->note,
			$app->address,
			$email,
			$app->city
		);
		$body = apply_filters( 'app_removal_notification_message', $body, $app, $app_id );

		$subject = ! empty( $options['removal_notification_subject'] ) ? $options['removal_notification_subject'] : __( 'Appointment has been removed', 'appointments' );

		$subject = $appointments->_replace($subject,
			$app->name,
			$appointments->get_service_name($app->service),
			appointments_get_worker_name($app->worker),
			$app->start,
			$app->price,
			$appointments->get_deposit($app->price),
			$app->phone,
			$app->note,
			$app->address,
			$email,
			$app->city
		);

		return array(
			'subject' => $subject,
			'body' => $body
		);

	}

	public function get_default_customer_body() {
		return "Dear CLIENT,

We would like to inform you that your appointment with SITE_NAME on DATE_TIME has been removed.

Here are your appointment details:
Requested service: SERVICE
Date and time: DATE_TIME

Kind regards,
SITE_NAME
";
	}
}