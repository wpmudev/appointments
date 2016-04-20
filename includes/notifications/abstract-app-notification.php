<?php

abstract class Appointments_Notification {
	
	/** @var  Appointments_Notifications_Manager */
	protected $manager;

	public function __construct( $manager ) {
		$this->manager = $manager;
	}

	abstract function send( $app_id );

	/**
	 * Replace placeholders with real values for email subject and content
	 *
	 * @param string $text Text to apply the replacements
	 * @param array $args Arguments
	 * @param string $notification_type
	 * @param mixed $object Object where the information comes from (normally an Appointment)
	 *
	 * @return string
	 */
	protected function replace_placeholders( $text, $args, $notification_type, $object ) {
		$defaults = array(
			'user' => '',
			'service' => '',
			'worker' => '',
			'datetime' => '',
			'price' => '',
			'deposit' => '',
			'phone' => '',
			'note' => '',
			'address' => '',
			'email' => '',
			'city' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! empty( $args['price'] ) && ! empty( $args['deposit'] ) ) {
			$args['balance'] = (float) $args['price'] - (float) $args['deposit'];
		} else {
			$args['balance'] = ! empty( $args['price'] ) ? $args['price'] : 0.0;
		}

		$replacement = array(
			'/\bSITE_NAME\b/U'        => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			'/\bCLIENT\b/U'           => $args['user'],
			'/\bSERVICE_PROVIDER\b/U' => $args['worker'],
			'/\bSERVICE\b/U'          => preg_replace( '/\$(\d)/', '\\\$$1', $args['service'] ),
			'/\bDATE_TIME\b/U'        => mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $args['datetime'] ),
			'/\bPRICE\b/U'            => $args['price'],
			'/\bDEPOSIT\b/U'          => $args['deposit'],
			'/\bBALANCE\b/U'          => $args['balance'],
			'/\bPHONE\b/U'            => $args['phone'],
			'/\bNOTE\b/U'             => $args['note'],
			'/\bADDRESS\b/U'          => $args['address'],
			'/\bEMAIL\b/U'            => $args['email'],
			'/\bCITY\b/U'             => $args['city'],
		);

		$replacement = apply_filters( 'appointments_notification_replacements', $replacement, $notification_type, $text, $object );

		foreach ( $replacement as $macro => $repl ) {
			$text = preg_replace( $macro, $repl, $text );
		}

		return $text;
	}
}