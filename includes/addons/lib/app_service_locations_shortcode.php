<?php

class App_Shortcode_ServiceLocationsShortcode extends App_Shortcode {

	protected $_requested_location_id;

	public static function serve () {
		$me = new self;
		$me->register('app_service_locations');
	}

	protected function __construct () {
		$this->_defaults = array(
			'select' => array(
				'value' => __('Please select a service location:', 'appointments'),
				'help' => __('Text above the select menu. Default: "Please select a service location"', 'appointments'),
				'example' => __('Please select a service location:', 'appointments'),
			),
			'show' => array(
				'value' => __('Show available services', 'appointments'),
				'help' => __('Button text to show the results for the selected. Default: "Show available services"', 'appointments'),
				'example' => __('Show available services', 'appointments'),
			),
			'autorefresh' => array(
				'value' => 0,
				'help' => __('If set as 1, Show button will not be displayed and page will be automatically refreshed as client changes selection. Note: Client cannot browse through the selections and thus check descriptions on the fly (without the page is refreshed). Default: "0" (disabled)', 'appointments'),
				'example' => '1',
			),
			'order_by' => array(
				'value' => 'ID',
				'help' => __('Sort order, by service. Possible values: ID, name, duration, price. Optionally DESC (descending) can be used, e.g. "name DESC" will reverse the order. Default: "ID"', 'appointments'),
				'example' => 'ID',
			),
		);

		if (!empty($_REQUEST['app_service_location']) && is_numeric($_REQUEST['app_service_location'])) {
			$this->_requested_location_id = (int)$_REQUEST['app_service_location'];
		}

		if (!is_admin() && !empty($this->_requested_location_id)) {
			add_filter('app_services', array($this, 'filter_services'));
		}
	}

	public function filter_services ($services) {
		$result = array();
		foreach ($services as $srv) {
			if (empty($srv->ID)) continue;
			$location_id = App_Locations_ServiceLocations::service_to_location_id($srv->ID);
			if (!empty($location_id) && $this->_requested_location_id == $location_id) {
				$result[] = $srv;
			}
		}
		return $result;
	}

	public function process_shortcode ($args=array(), $content='') {
		global $appointments;
		$args = wp_parse_args($args, $this->_defaults_to_args());

		$services = $appointments->get_services($args['order_by']);
		$model = App_Locations_Model::get_instance();
		$locations = array();

		foreach ($services as $srv) {
			if (empty($srv->ID)) continue;
			$location_id = App_Locations_ServiceLocations::service_to_location_id($srv->ID);
			if (!empty($location_id)) $locations[$location_id] = $model->find_by('id', $location_id);
		}
		$locations = array_values(array_filter($locations));

		if (empty($locations)) return $content;
		$ret = '';

		$ret .= '<div class="app_service_locations">';
		$ret .= '<div class="app_service_locations_dropdown">';
		$ret .= '<div class="app_service_locations_dropdown_title">';
		$ret .= $args['select'];
		$ret .= '</div>';
		$ret .= '<div class="app_service_locations_dropdown_select">';
		$ret .= '<select name="app_service_location">';
		foreach ($locations as $location) {
			$ret .= '<option value="' . esc_attr($location->get_id()) . '" ' . selected($this->_requested_location_id, $location->get_id(), false) . '>' . esc_html($location->get_display_markup(false)) . '</option>';
		}
		$ret .= '</select>';
		if (empty($args['autorefresh'])) $ret .= '<input type="button" class="app_service_locations_button" value="'.esc_attr($args['show']).'">';
		$ret .= '</div>';
		$ret .= '</div>';

		$href = add_query_arg(
			'app_service_location', '::asl::',
			remove_query_arg(array(
				//'app_service_location',
				'app_provider_location',
				'app_provider_id',
				//'app_service_id'
			))
		);

		$script =<<<EO_SELECTION_JAVASCRIPT
function app_service_locations_redirect () {
	var selected = $(".app_service_locations_dropdown_select select").first().val();
	window.location = '{$href}'.replace(/::asl::/, selected);
}
$(".app_service_locations_button").click(app_service_locations_redirect);
EO_SELECTION_JAVASCRIPT;
		if (!empty($args['autorefresh'])) {
			$script .= '$(".app_service_locations_dropdown_select select").change(app_service_locations_redirect);';
		}
		$appointments->add2footer($script);
		
		return $ret;
	}

	public function get_usage_info () {
		return __('Creates a dropdown menu of available service locations.', 'appointments');
	}
}



class App_Shortcode_RequiredServiceLocationsShortcode extends App_Shortcode_ServiceLocationsShortcode {

	public static function serve () {
		$me = new self;
		$me->register('app_required_service_locations');
	}
	
	public function get_usage_info () {
		return __('Creates a dropdown menu of available service locations which will be converted to a service list once a location has been chosen.', 'appointments');
	}

	public function process_shortcode ($args=array(), $content='') {
		$instance = App_Shortcodes::get_shortcode_instance('app_services');
		if (!empty($this->_requested_location_id) && $instance && method_exists($instance, 'process_shortcode')) return $instance->process_shortcode($args, $content);
		else return parent::process_shortcode($args, $content);
	}
}