<?php
/*
Plugin Name: Locations
Description: Allows you to create locations for your appointments.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Locations
Author: WPMU DEV
*/

class App_Locations_LocationsWorker {

	const SETTINGS_TAB = 'locations';
	const INJECT_TAB_BEFORE = 'services';

	private $_data;

	/** @var  App_Locations_Model */
	private $_locations;

	private function __construct () {}

	public static function serve () {
		$me = new App_Locations_LocationsWorker;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));
		
		// Set up admin interface
		add_filter('appointments_tabs', array($this, 'settings_tab_add'));
		add_action('app-settings-tabs', array($this, 'settings_tab_create'));
		add_filter('admin_init', array($this, 'save_settings'));
		add_action('app-admin-admin_scripts', array($this, 'include_scripts'));
		add_action('app-admin-admin_styles', array($this, 'include_styles'));

		// Appointments list
		add_filter('app-appointments_list-edit-services', array($this, 'show_appointment_location'), 10, 2);
		add_filter('app-appointment-inline_edit-save_data', array($this, 'save_appointment_location'));

		add_filter( 'appointments_notification_replacements', array( $this, 'add_notifications_replacements' ), 10, 4 );
	}

	/**
	 * Add a replacement for LOCATION in every notification
	 *
	 * @param $replacement
	 * @param $notification_type
	 * @param $text
	 * @param $object
	 *
	 * @since 1.8
	 *
	 * @return mixed
	 */
	public function add_notifications_replacements( $replacement, $notification_type, $text, $object ) {
		$replacement['/(?:^|\b)LOCATION(?:\b|$)/'] = '';
		$replacement['/(?:^|\b)LOCATION_ADDRESS(?:\b|$)/'] = '';

		if ( empty( $object->location ) ) {
			return $replacement;
		}

		$location = $this->_locations->find_by('id', $object->location);
		if ( empty( $location ) ) {
			return $replacement;
		}

		$filter = App_Macro_Codec::FILTER_BODY == false;
		$name = $location->get_display_markup( $filter );
		$address = $location->get_address();

		$replacement['/(?:^|\b)LOCATION(?:\b|$)/'] = $name;
		$replacement['/(?:^|\b)LOCATION_ADDRESS(?:\b|$)/'] = $address;

		return $replacement;
	}

	public function save_appointment_location ($data) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return $data;
		}
		$location_id      = ! empty( $_POST['location'] ) ? $_POST['location'] : false;
		$data['location'] = $location_id;

		return $data;
	}

	public function show_appointment_location ($out, $appointment) {
		$editable = '';
		$all = $this->_locations->get_all();
		$editable .= '<span class="title">' . __('Location', 'appointments') . '</span>';
		$editable .= '<select name="location"><option value=""></option>';
		foreach ($all as $loc) {
			/** @var AppLocation $loc */
			$sel = selected($loc->get_id(), $appointment->location, false);
			$editable .= '<option value="' . esc_attr($loc->get_id()) . '" ' . $sel . '>' . $loc->get_admin_label() . '</option>';
		}
		$editable .= '</select>';

		return $out . "<label>{$editable}</label>";
	}

	public function include_scripts () {
		global $appointments;
		wp_enqueue_script("app-locations", $appointments->plugin_url . "/js/locations.js", array('jquery'), $appointments->version);
		wp_localize_script("app-locations", '_app_locations_data', apply_filters('app-locations-location_model_template', array(
			'model' => array(
				'fields' => array(
					'address' => __('Address', 'appointments'),
				),
				'labels' => array(
					'add_location' => __('Add', 'appointments'),
					'save_location' => __('Save', 'appointments'),
					'new_location' => __('Create a New Location', 'appointments'),
					'edit_location' => __('Edit Location', 'appointments'),
					'cancel_editing' => _x('Cancel', 'Drop current action', 'appointments'),
				),
			),
		)));
	}

	public function include_styles () {
		global $appointments;
		wp_enqueue_style("app-locations", $appointments->plugin_url . "/css/locations.css", false, $appointments->version);
	}

	public function settings_tab_add ($tabs) {
		$ret = array();
		foreach ($tabs as $key => $label) {
			if ($key == self::INJECT_TAB_BEFORE) {
				$ret[self::SETTINGS_TAB] = __('Locations', 'appointments');
			}
			$ret[$key] = $label;
		}
		return $ret;
	}

	public function settings_tab_create ($tab) {
		if (self::SETTINGS_TAB != $tab) return false;
		$locations = $this->_locations->get_all();
		?>
<div class="wrap">
<form method="post" action="" >
	<p><button type="button" class="button button-secondary" id="app-locations-add_location"><?php _e('Add location', 'appointments'); ?></button></p>
	<div id="poststuff" class="metabox-holder">
	<?php do_action('app-locations-settings-before_locations_list'); ?>

	<div class="postbox">
		<h3 class='hndle'><span><?php _e('Locations List', 'appointments') ?></span></h3>
		<div class="inside">
			<ul id="app-locations-list">
			<?php foreach ($locations as $location) { ?>
				<li id="app-location-<?php esc_attr_e($location->get_id()); ?>">
					<i class="icon16 icon-post"></i>
					<b><?php echo $location->get_admin_label(); ?></b>
					<input type="hidden" name="locations[]" value="<?php esc_attr_e(json_encode($location->to_storage())); ?>" />
					<button type="button" class="app-locations-edit button"><?php _e('Edit', 'appointments'); ?></button>
					<button type="button" class="app-locations-delete button"><?php _e('Delete', 'appointments'); ?></button>
				</li>
			<?php } ?>
			<input type="hidden" name="action_app" value="save_locations" />
			<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
			</ul>
		</div>
	</div>
	
	<p><input type="submit" class="button button-primary" id="app-locations-save_locations" value="<?php esc_attr_e(__('Save locations', 'appointments')); ?>" /></p>

	<div class="postbox">
		<h3 class='hndle'><span><?php _e('Locations Settings', 'appointments') ?></span></h3>
		<div class="inside">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Show my appointments location', 'appointments')?></th>
					<td>
						<select name="locations_settings[my_appointments]" autocomplete="off">
							<option value=""></option>
							<option value="after_service" <?php selected($this->_data['locations_settings']['my_appointments'], 'after_service'); ?> ><?php _e('Automatic, after service', 'appointments'); ?></option>
							<option value="after_worker" <?php selected($this->_data['locations_settings']['my_appointments'], 'after_worker'); ?> ><?php _e('Automatic, after provider', 'appointments'); ?></option>
							<option value="after_date" <?php selected($this->_data['locations_settings']['my_appointments'], 'after_date'); ?> ><?php _e('Automatic, after date/time', 'appointments'); ?></option>
							<option value="after_status" <?php selected($this->_data['locations_settings']['my_appointments'], 'after_status'); ?> ><?php _e('Automatic, after status', 'appointments'); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Show all appointments location', 'appointments')?></th>
					<td>
						<select name="locations_settings[all_appointments]" autocomplete="off">
							<option value=""></option>
							<option value="after_service" <?php selected($this->_data['locations_settings']['all_appointments'], 'after_service'); ?> ><?php _e('Automatic, after service', 'appointments'); ?></option>
							<option value="after_provider" <?php selected($this->_data['locations_settings']['all_appointments'], 'after_provider'); ?> ><?php _e('Automatic, after provider', 'appointments'); ?></option>
							<option value="after_client" <?php selected($this->_data['locations_settings']['all_appointments'], 'after_client'); ?> ><?php _e('Automatic, after client', 'appointments'); ?></option>
							<option value="after_date" <?php selected($this->_data['locations_settings']['all_appointments'], 'after_date'); ?> ><?php _e('Automatic, after date/time', 'appointments'); ?></option>
							<option value="after_status" <?php selected($this->_data['locations_settings']['all_appointments'], 'after_status'); ?> ><?php _e('Automatic, after status', 'appointments'); ?></option>
						</select>
					</td>
				</tr>
				<?php do_action('app-locations-settings-after_location_settings'); ?>
			</table>
			</div>
		</div>
	
	<?php do_action('app-locations-settings-after_locations_list'); ?>
	</div>
	<p><input type="submit" class="button button-primary" id="app-locations-save_locations" value="<?php esc_attr_e(__('Save settings', 'appointments')); ?>" /></p>
</form>
</div>
		<?php
	}

	public function save_settings ($options) {
		if (empty($_POST['action_app']) || 'save_locations' != $_POST['action_app']) return false;
		if (!App_Roles::current_user_can('manage_options', App_Roles::CTX_PAGE_SETTINGS)) return false;
		if (!wp_verify_nonce($_POST['app_nonce'],'update_app_settings')) return false;
		if (empty($_POST['locations'])) return false;
		
		$options = get_option('appointments_options', array());

		$raw = stripslashes_deep($_POST['locations']);
		$data = array();
		foreach ($raw as $json) {
			$item = @json_decode($json, true);
			if (empty($item)) continue;
			$data[] = $item;
		}
		$this->_locations->populate_from_storage($data);
		$this->_locations->update();

		$settings = stripslashes_deep($_POST['locations_settings']);
		$options['locations_settings'] = !empty($settings) ? $settings : array();

		$options = apply_filters('app-locations-before_save', $options);

		update_option('appointments_options', $options);

		wp_redirect(add_query_arg('saved', 1));
		die;
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;

		if (!class_exists('App_Locations_Model')) require_once(dirname(__FILE__) . '/lib/app_locations.php');
		$this->_locations = App_Locations_Model::get_instance();

		do_action('app-locations-initialized');

		if (!empty($this->_data['locations_settings']['my_appointments'])) {
			$injection_point = $this->_data['locations_settings']['my_appointments'];
			add_filter('app_my_appointments_column_name', array($this, 'my_appointments_headers'), 1);
			add_filter('app-shortcode-my_appointments-' . $injection_point, array($this, 'my_appointments_address'), 1, 2);
		}
		if (!empty($this->_data['locations_settings']['all_appointments'])) {
			$injection_point = $this->_data['locations_settings']['all_appointments'];
			add_filter('app_all_appointments_column_name', array($this, 'all_appointments_headers'), 1);
			add_filter('app-shortcode-all_appointments-' . $injection_point, array($this, 'all_appointments_address'), 1, 2);
		}

		if ( empty( $this->_data['locations_settings']['all_appointments'] ) ) {
			$this->_data['locations_settings']['all_appointments'] = '';
		}
		if ( empty( $this->_data['locations_settings']['my_appointments'] ) ) {
			$this->_data['locations_settings']['my_appointments'] = '';
		}

		// Add macro expansion filtering
		add_filter('app-codec-macros', array($this, 'add_to_macro_list'));
		add_filter('app-codec-macro_default-replace_location', array($this, 'expand_location_macro'), 10, 3);
		add_filter('app-codec-macro_default-replace_location_address', array($this, 'expand_location_address_macro'), 10, 2);

		// GCal expansion filters
		add_filter('app-gcal-set_summary', array($this, 'expand_location_macro'), 10, 2);
		add_filter('app-gcal-set_summary', array($this, 'expand_location_address_macro'), 10, 2);
		add_filter('app-gcal-set_description', array($this, 'expand_location_macro'), 10, 2);
		add_filter('app-gcal-set_description', array($this, 'expand_location_address_macro'), 10, 2);
	}

	public function add_to_macro_list ($macros) {
		$macros[] = 'LOCATION';
		$macros[] = 'LOCATION_ADDRESS';
		return $macros;
	}

	public function expand_location_address_macro ($content, $app) {
		if (empty($app->location)) return $content;
		
		$location = $this->_locations->find_by('id', $app->location);
		if (empty($location)) return $content;

		$address = $location->get_address();
		return preg_replace('/(?:^|\b)LOCATION_ADDRESS(?:\b|$)/', $address, $content);
	}

	public function expand_location_macro ($content, $app, $filter=false) {
		if (empty($app->location)) return $content;
		
		$location = $this->_locations->find_by('id', $app->location);
		if (empty($location)) return $content;

		$address = $location->get_display_markup(
			(App_Macro_Codec::FILTER_BODY == $filter)
		);
		return preg_replace('/(?:^|\b)LOCATION(?:\b|$)/', $address, $content);
	}

	public function my_appointments_headers ($headers) {
		$where = preg_replace('/^after_/', '', $this->_data['locations_settings']['my_appointments']);
		if (!$where) return $headers;
		$rx = '(' .
			preg_quote('<th class="my-appointments-' . $where . '">', '/') .
			'.*?' .
			preg_quote('</th>', '/') .
		')';
		$location = '<th class="my-appointments-location">' . __('Location', 'appointments') . '</th>';
		return preg_replace("/{$rx}/", '\1' . $location, $headers);
	}

	public function my_appointments_address ($out, $appointment) {
		if (empty($appointment->location)) return $out . '<td>&nbsp;</td>';
		$out .= '<td>';
		$location = $this->_locations->find_by('id', $appointment->location);
		if ($location) {
			$out .= $location->get_display_markup(false);
		}
		$out .= '</td>';
		return $out;
	}

	public function all_appointments_headers ($headers) {
		$where = preg_replace('/^after_/', '', $this->_data['locations_settings']['all_appointments']);
		if (!$where) return $headers;
		$rx = '(' .
			preg_quote('<th class="all-appointments-' . $where . '">', '/') .
			'.*?' .
			preg_quote('</th>', '/') .
		')';
		$location = '<th class="all-appointments-location">' . __('Location', 'appointments') . '</th>';
		return preg_replace("/{$rx}/", '\1' . $location, $headers);
	}

	public function all_appointments_address ($out, $appointment) {
		if (empty($appointment->location)) return $out . '<td>&nbsp;</td>';
		$out .= '<td>';
		$location = $this->_locations->find_by('id', $appointment->location);
		if ($location) {
			$out .= $location->get_display_markup(false);
		}
		$out .= '</td>';
		return $out;
	}

}

// Serve the main entry point
App_Locations_LocationsWorker::serve();