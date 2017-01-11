<?php
/*
Plugin Name: Service Locations
Description: Allows you to bind locations to your services.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Locations
Requires: Locations
Author: WPMU DEV
*/

// Add the Service Location attribute to the Appointments_Service Object

class App_Locations_ServiceLocations {

	const STORAGE_PREFIX = 'app-service_location-';

	private $_data;
	/**
	 * @var App_Locations_Model
	 */
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
		add_action( 'appointments_add_new_service_form', array( $this, 'add_new_service_selection' ) );

		// Add settings
		add_action('appointments_locations_settings_section_settings', array($this, 'show_settings'));
		add_filter('app-locations-before_save', array($this, 'save_settings'));

		add_action('admin_notices', array($this, 'show_nags'));

		// Record appointment location
		add_action('wpmudev_appointments_insert_appointment', array($this, 'record_appointment_location'), 5);

		// Since 1.8.2
		add_filter( 'appointments_get_service_attribute_location', array( $this, 'get_service_attribute' ), 10, 2 );
		add_action('appointments_insert_service', array($this, 'save_service_location'));
		add_action('appointments_delete_service', array($this, 'delete_service_location_relationship'));
		add_action('wpmudev_appointments_update_service', array($this, 'save_service_location'));

		add_filter( 'appointments_default_options', array( $this, 'default_options' ) );

		add_filter( 'app-shortcodes-register', array( $this, 'register_shortcodes' ) );
	}

	public function default_options( $options ) {
		$options['service_locations'] = array();
		$options['service_locations']['insert'] = 'manual';
		return $options;
	}

	/**
	 * Return Service Location given a service_id as an attribute of Appointments_Service
	 *
	 * @param mixed $value Current value for 'location' attribute
	 * @param int $service_id Service ID
	 *
	 * @return Appointments_Location|bool
	 */
	function get_service_attribute( $value, $service_id ) {
		$location_id = get_option( 'app-service_location-' . $service_id, false );
		return appointments_get_location( $location_id );
	}

	/**
	 * Save a service location relationship or update it
	 *
	 * @param $service_id
	 */
	function save_service_location( $service_id ) {
		$service = appointments_get_service( $service_id );
		if ( ! $service ) {
			return;
		}

		$location = false;
		if ( isset( $_POST['service_location'][ $service_id ] ) ) {
			$location = $_POST['service_location'][ $service_id ];
		}
		elseif ( isset( $_POST['service_location'] ) ) {
			$location = $_POST['service_location'];
		}
		else {
			return;
		}

		$old_location = $service->location;
		$old_location_id = false;
		if ( is_a( $old_location, 'Appointments_Location' ) ) {
			$old_location_id = $old_location->id;
		}

		$key = self::STORAGE_PREFIX . $service_id;

		if ( ! $location ) {
			$location = 0;
		}

		$this->_update_appointment_locations( $service_id, $old_location_id, $location );

		if ( $location === false ) {
			delete_option( $key );
		}
		else {
			update_option($key, $location);
		}

	}

	/**
	 * Display the location service selector in Services list page
	 *
	 * @param $out
	 * @param $service_id
	 *
	 * @return string
	 */
	public function add_service_selection ($out, $service_id) {
		if ( ! class_exists( 'App_Locations_Model' ) || ! $this->_locations ) {
			return $out;
		}

		$locations = appointments_get_locations();
		$service = appointments_get_service( $service_id );
		if ( ! $service ) {
			return $out;
		}

		$service_location = $service->location;
		$service_location_id = false;
		if ( is_a( $service_location, 'Appointments_Location' ) ) {
			$service_location_id = $service_location->id;
		}

		ob_start();
		?>
		<label for="service_location-<?php echo $service_id; ?>"><?php _e( 'Location', 'appointments' ); ?></label>
		<select name="service_location[<?php echo $service_id; ?>]" id="service_location-<?php echo $service_id; ?>">
			<option value=""></option>
			<?php foreach ( $locations as $location ): ?>
				<option value="<?php echo $location->id; ?>" <?php selected( $location->id, $service_location_id ); ?>><?php echo esc_html( $location->address ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		
		return $out . ob_get_clean();
	}

	/**
	 * Display the location service selector fields in new Service form
	 */
	public function add_new_service_selection() {
		if ( ! class_exists( 'App_Locations_Model' ) || ! $this->_locations ) {
			return;
		}

		$locations = appointments_get_locations();

		?>
		<tr>
			<th scope="row">
				<label for="service_location"><?php _e( 'Location', 'appointments' ); ?></label>
			</th>
			<td>
				<select name="service_location" id="service_location">
					<option value=""></option>
					<?php foreach ( $locations as $location ): ?>
						<option value="<?php echo $location->id; ?>"><?php echo esc_html( $location->address ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Delete a service
	 * @param $service_id
	 */
	public function delete_service_location_relationship( $service_id ) {
		$key = self::STORAGE_PREFIX . $service_id;
		delete_option( $key );
	}


	function show_nags () {
		if (!class_exists('App_Locations_Location') || !$this->_locations) {
			echo '<div class="error"><p>' .
				__("You'll need Locations add-on activated for Service Locations integration add-on to work", 'appointments') .
			'</p></div>';
		}
	}

	public function initialize () {
		if ( ! class_exists( 'App_Locations_Model' ) ) {
			return false;
		}
		$this->_data = appointments_get_options();;
		$options = appointments_get_options();;
		$this->_locations = App_Locations_Model::get_instance();

		if ( 'manual' == $options['service_locations']['insert'] ) {
			add_shortcode('app_service_location', array($this, 'process_shortcode'));
		} else {
			add_shortcode('app_service_location', '__return_false');
			add_filter('app-services-service_description', array($this, 'inject_location_markup'), 10, 3);
		}
	}

	public function register_shortcodes( $shortcodes ) {
		include_once( 'lib/app_service_locations_shortcode.php' );
		$shortcodes['app_service_locations'] = 'App_Shortcode_ServiceLocationsShortcode';
		$shortcodes['app_required_service_locations'] = 'App_Shortcode_RequiredServiceLocationsShortcode';
		return $shortcodes;
	}

	public function record_appointment_location ($appointment_id) {
		$appointment = appointments_get_appointment( $appointment_id );
		if ( empty( $appointment->service ) ) {
			return false;
		}

		$location_id = self::service_to_location_id( $appointment->service );
		if ( ! $location_id ) {
			return false;
		}

		appointments_update_appointment( $appointment_id, array( 'location' => $location_id ) );
	}

	public function show_settings () {
		$settings = appointments_get_options();
		?>
			<h3><?php _e('Service Locations Settings', 'appointments') ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="service_locations-insert"><?php _e('Show service location', 'appointments')?></label></th>
					<td>
						<select id="service_locations-insert" name="service_locations[insert]">
							<option value="manual" <?php selected($settings['service_locations']['insert'], 'manual'); ?> ><?php _e('I will add location info manually, using shortcode', 'appointments'); ?></option>
							<option value="before" <?php selected($settings['service_locations']['insert'], 'before'); ?> ><?php _e('Automatic, before service description', 'appointments'); ?></option>
							<option value="after" <?php selected($settings['service_locations']['insert'], 'after'); ?> ><?php _e('Automatic, after service description', 'appointments'); ?></option>
						</select>
						<p><small><?php _e('You can use the shortcode like this: <code>[app_service_location]</code>', 'appointments'); ?></small></p>
					</td>
				</tr>
			</table>
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
		$options = appointments_get_options();
		return ('before' == $options['service_locations']['insert'])
			? $out . $markup
			: $markup . $out 
		;
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
		if ( $old_location_id == $location_id ) {
			return;
		}

		$apps = appointments_get_appointments( array( 'location' => $old_location_id, 'service' => $service_id ) );
		foreach ( $apps as $app ) {
			appointments_update_appointment( $app->ID, array( 'location' => $location_id ) );
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