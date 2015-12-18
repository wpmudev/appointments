<?php
/*
Plugin Name: Service Locations
Description: Allows you to bind locations to your services.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Locations
Requires: Locations add-on
Author: WPMU DEV
*/

class App_Locations_ServiceLocations {

	const STORAGE_PREFIX = 'app-service_location-';

	private $_data;
	private $_locations;

	private function __construct () {}

	public static function serve () {
		$me = new App_Locations_ServiceLocations;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		// Init and dispatch post-init actions
		add_action('app-locations-initialized', array($this, 'initialize'));
		
		// Augment service settings pages
		add_filter('app-settings-services-service-name', array($this, 'add_service_selection'), 10, 2);
		add_action('app-services-service-updated', array($this, 'save_service_location'));

		// Add settings
		add_action('app-locations-settings-after_locations_list', array($this, 'show_settings'));
		add_filter('app-locations-before_save', array($this, 'save_settings'));

		add_action('admin_notices', array($this, 'show_nags'));

		// Record appointment location
		add_action('app_new_appointment', array($this, 'record_appointment_location'), 20);
	}

	function show_nags () {
		if (!class_exists('App_Locations_Location') || !$this->_locations) {
			echo '<div class="error"><p>' .
				__("You'll need Locations add-on activated for Service Locations integration add-on to work", 'appointments') .
			'</p></div>';
		}
	}

	public function initialize () {
		if (!class_exists('App_Locations_Model')) return false;
		global $appointments;
		$this->_data = $appointments->options;
		if (empty($this->_data['service_locations'])) $this->_data['service_locations'] = array();
		$this->_locations = App_Locations_Model::get_instance();

		if (empty($this->_data['service_locations']['insert']) || 'manual' == $this->_data['service_locations']['insert']) {
			add_shortcode('app_service_location', array($this, 'process_shortcode'));
		} else {
			add_shortcode('app_service_location', '__return_false');
			add_filter('app-services-service_description', array($this, 'inject_location_markup'), 10, 3);
		}

		if (!class_exists('App_Shortcode_ServiceLocationsShortcode')) {
			require_once(dirname(__FILE__) . '/lib/app_service_locations_shortcode.php');
			App_Shortcode_ServiceLocationsShortcode::serve();
			App_Shortcode_RequiredServiceLocationsShortcode::serve();
		}
	}

	public function record_appointment_location ($appointment_id) {
		global $wpdb, $appointments;
		$appointment = $appointments->get_app($appointment_id);
		if (empty($appointment->service)) return false;

		$location_id = self::service_to_location_id($appointment->service);
		if (!$location_id) return false;

		appointments_update_appointment( $appointment_id, array( 'location' => $location_id ) );
	}

	public function show_settings () {
		?>
<div class="postbox">
	<h3 class='hndle'><span><?php _e('Service Locations Settings', 'appointments') ?></span></h3>
	<div class="inside">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Show service location', 'appointments')?></th>
				<td>
					<select name="service_locations[insert]">
						<option value="manual" <?php selected($this->_data['service_locations']['insert'], 'manual'); ?> ><?php _e('I will add location info manually, using shortcode', 'appointments'); ?></option>
						<option value="before" <?php selected($this->_data['service_locations']['insert'], 'before'); ?> ><?php _e('Automatic, before service description', 'appointments'); ?></option>
						<option value="after" <?php selected($this->_data['service_locations']['insert'], 'after'); ?> ><?php _e('Automatic, after service description', 'appointments'); ?></option>
					</select>
					<p><small><?php _e('You can use the shortcode like this: <code>[app_service_location]</code>', 'appointments'); ?></small></p>
				</td>
			</tr>
		</table>
	</div>
</div>
		<?php
	}

	public function save_settings ($options) {
		if (empty($_POST['service_locations'])) return $options;

		$data = stripslashes_deep($_POST['service_locations']);
		$options['service_locations']['insert'] = !empty($data['insert']) ? $data['insert'] : false;

		return $options;
	}

	public function process_shortcode ($args=array(), $content='') {
		$service_id = !empty($args['service_id']) ? $args['service_id'] : false;
		if (!$service_id) {
			$post_id = get_queried_object_id();
			$service_id = $this->_map_description_post_to_service_id($post_id);
		}

		if (!$service_id) return $content;
		return $this->_get_service_location_markup($service_id, $content);
	}

	public function inject_location_markup ($markup, $service, $description) {
		if (!$service || empty($service->ID)) return $markup;
		$out = $this->_get_service_location_markup($service->ID, '', ('content' == $description));
		return ('before' == $this->_data['service_locations']['insert'])
			? $out . $markup
			: $markup . $out 
		;
	}

	public function add_service_selection ($out, $service_id) {
		if (!class_exists('App_Locations_Model') || !$this->_locations) return $out;
		$locations = $this->_locations->get_all();
		$markup = '';

		$markup .= '<label>' . __('Location:', 'appointments') . '</label>&nbsp;';
		$markup .= '<select name="service_location[' . $service_id . ']"><option value=""></option>';
		foreach ($locations as $location) {
			$checked = $location->get_id() == self::service_to_location_id($service_id) ? 'selected="selected"' : '';
			$markup .= '<option value="' . $location->get_id() . '" ' . $checked . '>' . esc_html($location->get_admin_label()) . '</option>';
		}
		$markup .= '</select>';
		return $out . $markup;
	}

	public function save_service_location ($service_id) {
		if (!$service_id) return false;
		$key = self::STORAGE_PREFIX . $service_id;

		$old_location_id = self::service_to_location_id($service_id);
		$location_id = !empty($_POST['service_location'][$service_id]) ? $_POST['service_location'][$service_id] : false;

		if ($old_location_id != $location_id) $this->_update_appointment_locations($service_id, $old_location_id, $location_id);

		return update_option($key, $location_id);
	}

	public static function service_to_location_id ($service_id) {
		if (!$service_id) return false;
		$key = self::STORAGE_PREFIX . $service_id;

		return get_option($key, false);
	}
	
	private function _service_to_location ($service_id) {
		if (!$this->_locations) return false;
		$location_id = self::service_to_location_id($service_id);
		return $this->_locations->find_by('id', $location_id);
	}

	private function _update_appointment_locations ($service_id, $old_location_id, $location_id) {
		global $wpdb, $appointments;

		if ($old_location_id == $location_id) return false;

		$res = $wpdb->update(
			$appointments->app_table, 
			array('location' => $location_id),
			array(
				'location' => $old_location_id,
				'service' => $service_id,
			), '%s', '%s'
		);

		if ( $res ) {
			appointments_clear_appointment_cache();
		}
	}

	private function _get_service_location_markup ($service_id, $fallback='', $rich_content=true) {
		$location = $this->_service_to_location($service_id);
		if (!$location) return $fallback;
		return $this->_get_location_markup($location, $rich_content);
	}

	private function _get_location_markup ($location, $rich_content=true) {
		$lid = $location->get_id();
		return '<div class="app-service_description-location" id="app-service_description-location-' . esc_attr($lid) . '">' .
			apply_filters('app-locations-location_output', $location->get_display_markup($rich_content), $lid, $location) .
		'</div>';
	}

	private function _map_description_post_to_service_id ($post_id) {
		$services = appointments_get_services( array( 'page' => $post_id, 'fields' => 'ID' ) );
		if ( ! empty( $services ) )
			return $services[0];

		return '';
	}
}
App_Locations_ServiceLocations::serve();