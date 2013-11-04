<?php

if (!defined('APP_LOCATIONS_LOCATION_DEFAULT_MODEL_INSTANCE')) define('APP_LOCATIONS_LOCATION_DEFAULT_MODEL_INSTANCE', 'App_Locations_DefaultLocation', true);

abstract class App_Locations_Location {

	const KEY_ID = 'id';
	const KEY_ADDRESS = 'address';

	protected $_data = array();


	abstract public function to_location ($data);
	abstract public function to_storage ();
	abstract public function get_display_markup ($rich_content=true);
	abstract public function get_admin_label ();

	public static function get_location ($data) {
		$class = apply_filters('app-locations-location-model_instance_class', APP_LOCATIONS_LOCATION_DEFAULT_MODEL_INSTANCE);
		$class = $class && class_exists($class) ? $class : APP_LOCATIONS_LOCATION_DEFAULT_MODEL_INSTANCE;
		$instance = new $class;
		$instance->to_location($data);
		return $instance;
	}

	public function get ($field) {
		if (!empty($this->_data[$field])) return $this->_data[$field];
		return false;
	}

	public function get_var ($key, $fallback=false) {
		return !empty($this->_data[$key])
			? $this->_data[$key]
			: $fallback
		;
	}
	
	public function set_var ($key, $value) {
		$this->_data[$key] = $value;
	}

	// Not using uniqid or hashes as we need to place 
	// the reference to this in forcefully INT type
	protected function _create_id () {
		$id = sprintf('%d', rand(0,99));
		$appx = '';
		$count = 0;
		while ($count < 3) {
			$appx .= sprintf('%04d', rand(0,99));
			if (intval("{$id}{$appx}") + 5 >= PHP_INT_MAX) break;
			$id .= $appx;
			$count++;
		}
		return intval($id);
	}
}

class App_Locations_DefaultLocation extends App_Locations_Location {

	public function to_location ($data) {
		$data = wp_parse_args($data, array(
			self::KEY_ID => $this->_create_id(),
			self::KEY_ADDRESS => '',
		));
		$this->_data = $data;
	}

	public function to_storage () {
		return $this->_data;
	}

	public function get_display_markup ($rich_content=true) {
		return $this->get_address();
	}

	public function get_admin_label () {
		return $this->get_address();
	}

	public function get_id () { return $this->get_var(self::KEY_ID); }
	public function get_address () { return $this->get_var(self::KEY_ADDRESS); }
}

class App_Locations_Model {

	const STORAGE_AREA = 'app_locations_data';

	private $_data = array();
	private static $_instance;

	private function __construct () {
		$this->reload();
	}

	public static function get_instance () {
		if (self::$_instance) return self::$_instance;
		self::$_instance = new App_Locations_Model;
		add_action('app-locations-location-model-item_updated', array(self::$_instance, 'update'));
		return self::$_instance;
	}

	public function reload () {
		$data = get_option(self::STORAGE_AREA, array());
		$this->populate_from_storage($data);
	}

	public function populate_from_storage ($data) {
		$this->_data = array();
		foreach ($data as $location) {
			$this->_data[] = App_Locations_Location::get_location($location);
		}
	}

	public function update () {
		$data = array();
		foreach ($this->_data as $location) {
			$data[] = $location->to_storage();
		}
		update_option(self::STORAGE_AREA, $data);
		$this->reload();
	}

	public function find_by ($field, $value) {
		foreach ($this->_data as $location) {
			if ($location->get($field) == $value) return $location;
		}
		return false;
	}

	public function index_by ($field, $value) {
		foreach ($this->_data as $idx => $location) {
			if ($location->get($field) == $value) return $idx;
		}
		return false;
	}

	public function get_all () {
		return $this->_data;
	}
}

class App_Locations_MappedLocation extends App_Locations_Location {

	const KEY_GOOGLE_MAP_ID = 'map_id';
	const KEY_LAT = 'lat';
	const KEY_LNG = 'lng';

	private $_maps_model;
	private $_maps_codec;

	public function __construct () {
		$this->_maps_model = new AgmMapModel;
		if (!is_admin()) $this->_maps_codec = new AgmMarkerReplacer;
	}

	public function to_location ($data) {
		$data = wp_parse_args($data, array(
			self::KEY_ID => $this->_create_id(),
			self::KEY_ADDRESS => '',
			self::KEY_GOOGLE_MAP_ID => false,
			self::KEY_LAT => false,
			self::KEY_LNG => false,
		));
		$this->_data = $data;
	}

	public function to_storage () {
		return $this->_data;
	}

	public function to_map () {
		$map_id = $this->get_var(self::KEY_GOOGLE_MAP_ID);
		
		if (!$map_id) $map_id = $this->_create_map();
		if (!$map_id) return false; // Map creation failed

		$map = $this->_maps_model->get_map($map_id);
		return $map;
	}

	public function get_display_markup ($rich_content=true) {
		return $rich_content
			? $this->get_map()
			: $this->get_address()
		;
	}

	public function get_admin_label () {
		return $this->get_address();
	}

	public function get_id () { return $this->get_var(self::KEY_ID); }
	public function get_address () { return $this->get_var(self::KEY_ADDRESS); }

	public function get_map () {
		$map = $this->to_map();
		if (!$map) {
			$this->set_var(self::KEY_GOOGLE_MAP_ID, false);
			do_action('app-locations-location-model-item_updated');
			return $this->get_map(); // Map got deleted somehow, let's double up
		} else {
			global $appointments;
			$data = $appointments->options;
			$overrides = !empty($data['google_maps']['overrides']) ? $data['google_maps']['overrides'] : array();
			return $this->_maps_codec->create_tag($map, $overrides);
		}
	}

	private function _create_map () {
		$model = new AgmMapModel;
		$map_id = $this->_maps_model->autocreate_map(null, null, null, $this->get_address());
		if (!$map_id) return false;

		$map = $this->_maps_model->get_map($map_id);
		$latitude = $longitude = false;
		$position = !empty($map['markers'][0]['position']) ? $map['markers'][0]['position'] : false;
		if (!empty($position)) {
			$latitude = $position[0];
			$longitude = $position[1];
		}

		$this->set_var(self::KEY_GOOGLE_MAP_ID, $map_id);
		if ($latitude) $this->set_var(self::KEY_LAT, $latitude);
		if ($longitude) $this->set_var(self::KEY_LNG, $longitude);
		do_action('app-locations-location-model-item_updated');
		return $map_id;
	}
}