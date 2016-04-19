<?php

class Appointments_Google_Calendar_Logger extends Google_Logger_Abstract {

	public function __construct( $client ) {
		parent::__construct( $client );

		$this->dateFormat = 'Y-m-d H:i:s';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function write( $message ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[Appointments+] ' . $message );
		}
	}

}