<?php

class Appointments_Addon {

	public $headers = array();

	private static $default_headers = array(
		'PluginName' => 'Plugin Name',
		'Description' => 'Description',
		'PluginURI' => 'Plugin URI',
		'Version' => 'Version',
		'AddonType' => 'AddonType',
		'Author' => 'Author'
	);

	public $addon_file;

	public $error = false;

	public function __construct( $addon_file ) {
		if ( ! is_readable( $addon_file ) ) {
			$this->error = true;
			return;
		}

		$this->addon_file = $addon_file;

		$this->headers = get_file_data( $this->addon_file, self::$default_headers );
	}

	public function __get( $name ) {
		if ( isset( $this->headers[ $name ] ) ) {
			return $this->headers[ $name ];
		}

		return '';
	}
}