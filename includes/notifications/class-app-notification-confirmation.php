<?php


class Appointments_Notifications_Confirmation extends Appointments_Notification {
	
	/**
	 * Send confirmation email to customer, admin and worker
	 *
	 * @param integer $app_id Appointment ID
	 *
	 * @return bool True if emails were sent
	 */
	public function send( $app_id ) {
		$appointments = appointments();

		$app = appointments_get_appointment( $app_id );
		if ( ! $app ) {
			return false;
		}

		$options = appointments_get_options();

		$send_confirmation = isset( $options["send_confirmation"] ) && 'yes' == $options["send_confirmation"];
		$send_confirmation = apply_filters( 'appointments_send_confirmation', $send_confirmation, $app_id );
		if ( ! $send_confirmation ) {
			return false;
		}

		$sent_to = array();
		$customer_email = $app->get_customer_email();
		$result = $this->customer( $app_id, $customer_email );

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
	 * Sends a confirmation email to the customer
	 *
	 * @param int $app_id Appointment ID
	 * @param string $email Email to send to
	 *
	 * @return bool True if the email has been sent
	 */
	public function customer( $app_id, $email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		if ( ! is_email( $email ) ) {
			$this->manager->log( sprintf( __( 'Unable to notify the client about the appointment ID:%s confirmation, stopping.', 'appointments' ), $app_id ) );
			return false;
		}

		$template = $this->get_customer_template( $app_id, $email );
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

		$this->manager->log( sprintf( __('Confirmation message sent to %s for appointment ID:%s','appointments'), $r->email, $app_id ) );

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
	public function admin( $app_id, $admin_email ) {
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
			do_action( 'appointments_confirmation_admin_sent', $admin_email, $app_id, $template['body'], $template['subject'] );
		}

		return $result;
	}

	private function get_admin_template( $app_id ) {
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

		$subject = __('New Appointment','appointments');

		$body = $this->get_customer_template( $app_id, $customer_email );

		return array(
			'subject' => $subject,
			'body' => $provider_add_text . $body['body']
		);
	}

	private function get_customer_template( $app_id, $email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$options = appointments_get_options();
		$body = $options['confirmation_message'];

		$args = array(
			'user'     => $r->name,
			'service'  => $appointments->get_service_name( $r->service ),
			'worker'   => appointments_get_worker_name( $r->worker ),
			'datetime' => $r->start,
			'price'    => $r->price,
			'deposit'  => $appointments->get_deposit( $r->price ),
			'phone'    => $r->phone,
			'note'     => $r->note,
			'address'  => $r->address,
			'email'    => $email,
			'city'     => $r->city
		);

		$body = $this->replace_placeholders( $body, $args, 'confirmation-body', $r );

		$body = $appointments->add_cancel_link( $body, $app_id );
		$body = apply_filters( 'app_confirmation_message', $body, $r, $app_id );

		$subject = $options["confirmation_subject"];
		$subject = $this->replace_placeholders( $subject, $args, 'confirmation-subject', $r );

		return array(
			'subject' => $subject,
			'body' => $body
		);
	}

}