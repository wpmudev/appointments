<?php

class App_Google_AppointmentsGoogleConfig extends App_Google_Config {

	public function __construct ($extra_config=array()) {
		parent::__construct();
		if (!empty($extra_config) && is_array($extra_config)) {
			$merged_configuration = $extra_config + $this->configuration;
			if (isset($extra_config['classes']) && isset($this->configuration['classes'])) {
				$merged_configuration['classes'] = $extra_config['classes'] + $this->configuration['classes'];
			}
			$this->configuration = $merged_configuration;
		}
	}
}