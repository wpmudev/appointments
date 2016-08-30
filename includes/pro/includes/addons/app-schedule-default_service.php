<?php
/*
Plugin Name: Default Service
Description: Allows you to select the default service for your appointments, rather than always using the first defined one.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Schedule
Author: WPMU DEV
*/

class App_Schedule_DefaultService {
	private $_data;

	/** @var  Appointments $_core */
	private $_core;

	private function __construct () {}

	public static function serve () {
		$me = new App_Schedule_DefaultService;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));
		add_filter('app-services-first_service_id', array($this, 'apply_selection'));

		add_action('appointments_settings_tab-main-section-advanced', array($this, 'show_settings'));
		add_filter('app-options-before_save', array($this, 'save_settings'));

		add_filter( 'appointments_services_shortcode_selected_service', array( $this, 'services_shortcode_selected_service' ), 10, 3 );
	}

	public function services_shortcode_selected_service( $selected, $args, $services ) {
		if ( ! $selected ) {
			return $this->_get_replacement();
		}

		if ( ! isset( $_REQUEST['app_service_id'] ) && $args['worker'] && appointments_get_worker( $args['worker'] ) ) {
			$replacement = $this->_get_replacement();
			$services_ids = wp_list_pluck( $services, 'ID' );
			if ( in_array( $replacement, $services_ids ) ) {
				return $replacement;
			}
		}

		return $selected;
	}

	public function initialize () {
		global $appointments;
		$this->_core = $appointments;
		$this->_data = $appointments->options;
	}

	public function apply_selection ($service_id) {
		$replacement = $this->_get_replacement();
		return !empty($replacement)
			? $replacement
			: $service_id
		;
	}

	public function save_settings ($options) {
		$options['default_service'] = !empty($_POST['default_service']) && is_numeric($_POST['default_service'])
			? (int)$_POST['default_service']
			: false
		;
		return $options;
	}

	public function show_settings () {
		$services = appointments_get_services();
		$replacement = $this->_get_replacement();
		?>
		<h3><?php _e( 'Default Service Settings', 'appointments' ); ?></h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row" ><label for="default_service"><?php _e('Default Service', 'appointments')?></label></th>
				<td colspan="row">
					<select name="default_service" id="default_service">
						<option value=""><?php _e('Default', 'appointments'); ?></option>
					<?php foreach ($services as $service) { ?>
						<option value="<?php echo esc_attr($service->ID); ?>" <?php selected($service->ID, $replacement); ?> >
							<?php echo $service->name; ?>
						</option>
					<?php } ?>
					</select>
					<span class="description"><?php _e('This is the service that will be used as the default one.', 'appointments') ?></span>
				</td>
			</tr>
		</table>
		<?php
	}

	private function _get_replacement () {
		return !empty($this->_data['default_service']) && is_numeric($this->_data['default_service'])
			? (int)$this->_data['default_service']
			: false
		;
	}
}
App_Schedule_DefaultService::serve();