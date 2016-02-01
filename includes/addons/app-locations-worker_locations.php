<?php
/*
Plugin Name: Worker Locations
Description: Allows you to bind locations to your Service Providers.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Locations
Requires: Locations add-on
Author: WPMU DEV
*/

class App_Locations_WorkerLocations {

	const STORAGE_PREFIX = 'app-worker_location-';

	private $_data;
	private $_locations;

	private function __construct () {}

	public static function serve () {
		$me = new App_Locations_WorkerLocations;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		// Init and dispatch post-init actions
		add_action('app-locations-initialized', array($this, 'initialize'));
		
		// Augment worker settings pages
		add_filter('app-settings-workers-worker-name', array($this, 'add_worker_selection'), 10, 2);
		add_action('app-workers-worker-updated', array($this, 'save_worker_location'));

		// Add settings
		add_action('app-locations-settings-after_locations_list', array($this, 'show_settings'));
		add_filter('app-locations-before_save', array($this, 'save_settings'));

		add_action('admin_notices', array($this, 'show_nags'));

		// Record appointment location
		add_action('app_new_appointment', array($this, 'record_appointment_location'), 40);
	}

	function show_nags () {
		if (!class_exists('App_Locations_Location') || !$this->_locations) {
			echo '<div class="error"><p>' .
				__("You'll need Locations add-on activated for Worker Locations integration add-on to work", 'appointments') .
			'</p></div>';
		}
	}

	public function initialize () {
		if (!class_exists('App_Locations_Model')) return false;
		global $appointments;
		$this->_data = $appointments->options;
		if (empty($this->_data['worker_locations'])) $this->_data['worker_locations'] = array();
		$this->_locations = App_Locations_Model::get_instance();

		if (empty($this->_data['worker_locations']['insert']) || 'manual' == $this->_data['worker_locations']['insert']) {
			add_shortcode('app_worker_location', array($this, 'process_shortcode'));
		} else {
			add_shortcode('app_worker_location', '__return_false');
			add_filter('app-workers-worker_description', array($this, 'inject_location_markup'), 10, 3);
		}

		if (!class_exists('App_Shortcode_WorkerLocationsShortcode')) {
			require_once(dirname(__FILE__) . '/lib/app_worker_locations_shortcode.php');
			App_Shortcode_WorkerLocationsShortcode::serve();
			App_Shortcode_RequiredWorkerLocationsShortcode::serve();
		}

		if ( empty( $this->_data['worker_locations']['insert'] ) ) {
			$this->_data['worker_locations']['insert'] = '';
		}
	}

	public function record_appointment_location ($appointment_id) {
		global $wpdb, $appointments;
		$appointment = appointments_get_appointment($appointment_id);
		if (empty($appointment->worker)) return false;

		$location_id = self::worker_to_location_id($appointment->worker);
		if (!$location_id) return false;

		appointments_update_appointment( $appointment_id, array('location' => $location_id) );
	}

	public function show_settings () {
		?>
<div class="postbox">
	<h3 class='hndle'><span><?php _e('Worker Locations Settings', 'appointments') ?></span></h3>
	<div class="inside">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Show worker location', 'appointments')?></th>
				<td>
					<select name="worker_locations[insert]">
						<option value="manual" <?php selected($this->_data['worker_locations']['insert'], 'manual'); ?> ><?php _e('I will add location info manually, using shortcode', 'appointments'); ?></option>
						<option value="before" <?php selected($this->_data['worker_locations']['insert'], 'before'); ?> ><?php _e('Automatic, before worker description', 'appointments'); ?></option>
						<option value="after" <?php selected($this->_data['worker_locations']['insert'], 'after'); ?> ><?php _e('Automatic, after worker description', 'appointments'); ?></option>
					</select>
					<p><small><?php _e('You can use the shortcode like this: <code>[app_worker_location]</code>', 'appointments'); ?></small></p>
				</td>
			</tr>
		</table>
	</div>
</div>
		<?php
	}

	public function save_settings ($options) {
		if (empty($_POST['worker_locations'])) return $options;

		$data = stripslashes_deep($_POST['worker_locations']);
		$options['worker_locations']['insert'] = !empty($data['insert']) ? $data['insert'] : false;

		return $options;
	}

	public function process_shortcode ($args=array(), $content='') {
		$worker_id = !empty($args['worker_id']) ? $args['worker_id'] : false;
		if (!$worker_id) {
			$post_id = get_queried_object_id();
			$worker_id = $this->_map_description_post_to_worker_id($post_id);
		}

		if (!$worker_id) return $content;
		return $this->_get_worker_location_markup($worker_id, $content);
	}

	public function inject_location_markup ($markup, $worker, $description) {
		if (!$worker || empty($worker->ID)) return $markup;
		$out = $this->_get_worker_location_markup($worker->ID, '', ('content' == $description));
		return ('before' == $this->_data['worker_locations']['insert'])
			? $out . $markup
			: $markup . $out 
		;
	}

	public function add_worker_selection ($out, $worker_id) {
		if (!class_exists('App_Locations_Model') || !$this->_locations) return $out;
		$locations = $this->_locations->get_all();
		$markup = '';

		$markup .= '<label>' . __('Location:', 'appointments') . '</label>&nbsp;';
		$markup .= '<select name="worker_location[' . $worker_id . ']"><option value=""></option>';
		foreach ($locations as $location) {
			$checked = $location->get_id() == self::worker_to_location_id($worker_id) ? 'selected="selected"' : '';
			$markup .= '<option value="' . $location->get_id() . '" ' . $checked . '>' . esc_html($location->get_admin_label()) . '</option>';
		}
		$markup .= '</select>';
		return $out . $markup;
	}

	public function save_worker_location ($worker_id) {
		if (!$worker_id) return false;
		$key = self::STORAGE_PREFIX . $worker_id;

		$old_location_id = self::worker_to_location_id($worker_id);
		$location_id = !empty($_POST['worker_location'][$worker_id]) ? $_POST['worker_location'][$worker_id] : false;

		if ($old_location_id != $location_id) $this->_update_appointment_locations($worker_id, $old_location_id, $location_id);

		return update_option($key, $location_id);
	}

	public static function worker_to_location_id ($worker_id) {
		if (!$worker_id) return false;
		$key = self::STORAGE_PREFIX . $worker_id;

		return get_option($key, false);
	}
	
	private function _worker_to_location ($worker_id) {
		if (!$this->_locations) return false;
		$location_id = self::worker_to_location_id($worker_id);
		return $this->_locations->find_by('id', $location_id);
	}

	private function _update_appointment_locations ($worker_id, $old_location_id, $location_id) {
		global $wpdb, $appointments;

		if ($old_location_id == $location_id) return false;

		$res = $wpdb->update(
			$appointments->app_table, 
			array('location' => $location_id),
			array(
				'location' => $old_location_id,
				'worker' => $worker_id,
			), '%s', '%s'
		);

		if ( $res ) {
			appointments_clear_appointment_cache();
		}
	}

	private function _get_worker_location_markup ($worker_id, $fallback='', $rich_content=true) {
		$location = $this->_worker_to_location($worker_id);
		if (!$location) return $fallback;
		return $this->_get_location_markup($location, $rich_content);
	}

	private function _get_location_markup ($location, $rich_content=true) {
		$lid = $location->get_id();
		return '<div class="app-worker_description-location" id="app-worker_description-location-' . esc_attr($lid) . '">' .
			apply_filters('app-locations-location_output', $location->get_display_markup($rich_content), $lid, $location) .
		'</div>';
	}

	private function _map_description_post_to_worker_id ($post_id) {
		global $appointments, $wpdb;
		$workers = appointments_get_workers( array( 'page' => $post_id ) );
		if ( ! empty( $workers ) )
			return $workers[0]->ID;

		return false;
	}
}
App_Locations_WorkerLocations::serve();