<?php

class Appointments_Notifications_Reminder extends Appointments_Notification {

	public function send( $app_id ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$customer_email = $r->get_customer_email();

		if ( ! is_email( $customer_email ) ) {
			$this->manager->log( sprintf( __( 'Unable to send client reminder about the appointment ID:%s.', 'appointments' ), $app_id ) );

			return false;
		}

		$this->customer( $app_id, $customer_email );
		$this->manager->log( sprintf( __( 'Reminder message sent to %s for appointment ID:%s', 'appointments' ), $customer_email, $app_id ) );

		$worker_email = $appointments->get_worker_email( $r->worker );

		if ( ! is_email( $worker_email ) ) {
			$this->manager->log( sprintf( __( 'Unable to send worker reminder about the appointment ID:%s.', 'appointments' ), $app_id ) );

			return true;
		}

		$this->worker( $app_id, $worker_email );

		return true;
	}

	private function customer( $app_id, $email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		if ( ! is_email( $email ) ) {
			return false;
		}

		$template = $this->get_customer_template( $app_id, $email );
		if ( ! $template ) {
			return false;
		}

		$attachments = apply_filters( 'app_reminder_email_attachments', '' );

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

		return true;

	}

	private function worker( $app_id, $email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		if ( ! is_email( $email ) ) {
			return false;
		}

		$template = $this->get_worker_template( $app_id, $email );
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

		return true;

	}

	private function get_customer_template( $app_id, $email ) {
		$appointments = appointments();

		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$options = appointments_get_options();

		$subject = $options["reminder_subject"];

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

		$subject = $this->replace_placeholders(	$subject, $args, 'reminder-subject', $r );

		$body = $options["reminder_message"];
		$body = $this->replace_placeholders( $body, $args, 'reminder-body', $r );

		$body = $appointments->add_cancel_link( $body, $app_id );
		$body = apply_filters( 'app_reminder_message', $body, $r, $r->ID );

		return array(
			'subject' => $subject,
			'body'    => $body
		);
	}

	private function get_worker_template( $app_id, $email ) {
		$r = appointments_get_appointment( $app_id );
		if ( ! $r ) {
			return false;
		}

		$provider_add_text = __( 'You are receiving this reminder message for your appointment as a provider. The below is a copy of what may have been sent to your client:', 'appointments' );
		$provider_add_text .= "\n\n\n";

		$customer_template = $this->get_customer_template( $app_id, $email );

		$subject = $customer_template['subject'];
		$body    = $provider_add_text . $customer_template['body'];

		return array(
			'subject' => $subject,
			'body'    => $body
		);
	}
}