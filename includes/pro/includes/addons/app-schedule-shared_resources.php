<?php
/*
Plugin Name: Shared Resources
Description: Allows your services to define shared real-life resources, such as rooms or vehicles. The services that share resources will only allow appointments up to minimum common capacity.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Schedule
Author: WPMU DEV
*/

class App_Schedule_SharedResources {

	private $_data;
	/** @var  Appointments */
	private $_core;

	private function __construct () {}

	public static function serve () {
		$me = new App_Schedule_SharedResources;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));

		add_filter('app-is_busy', array($this, 'check_shared_resources'), 10, 2);

		// Augment service settings pages
		add_filter('app-settings-services-service-name', array($this, 'add_service_selection'), 10, 2);
		add_action('app-services-service-updated', array($this, 'save_service_shared_resources'));
	}

	public function initialize () {
		global $appointments;
		$this->_core = $appointments;
		$this->_data = get_option('appointments_services_shared_resources', array());
	}

	public function add_service_selection ($out, $service_id) {
		$shared_ids = $this->_get_resource_sharing_services($service_id);
		$direct_ids = $this->_get_resource_sharing_services($service_id, true);
		$all = appointments_get_services();

		$out .= '<div class="app-shared_resources">';
		$out .= '<h4>' . __('Shares resources with', 'appointments') . '</h4>';
		foreach ($all as $service) {
			if (empty($service->ID) || $service->ID == $service_id) continue; // Don't include empty hits or current service
			$checked = in_array($service->ID, $shared_ids) ? 'checked="checked"' : '';
			$disabled = in_array($service->ID, $shared_ids) && !in_array($service->ID, $direct_ids) ? 'disabled="disabled"' : '';
			$out .= "<label for='app-shared_service-{$service_id}-{$service->ID}'>" .
				"<input type='checkbox' id='app-shared_service-{$service_id}-{$service->ID}' value='{$service->ID}' name='shared_resources[{$service_id}][]' {$checked} {$disabled} />" .
				'&nbsp;' .
				$service->name .
			'</label><br />';
		}
		$out .= '</div>';

		return strtr($out, "'", '"'); // We have to escape this, because of the way the JS injection works on the services page (wtf really o.0)
	}

	public function save_service_shared_resources ($service_id) {
		$shared = isset($_POST['shared_resources'][$service_id])
			? array_values(array_filter(array_map('intval', $_POST['shared_resources'][$service_id])))
			: array()
		;
		$all_resources = get_option('appointments_services_shared_resources', array());
		$all_resources[$service_id] = $shared;
		update_option('appointments_services_shared_resources', $all_resources);
	}

	public function check_shared_resources ($is_busy, $period) {
		$service_id = $this->_core->service;
		if (empty($service_id)) return $is_busy;

		$services = $this->_get_resource_sharing_services($service_id);
		if (empty($services) || 1 == count($services)) return $is_busy;

		$capacity = $this->_get_minimum_capacity($services);
		$booked = $this->_get_booked_appointments_for_period($services, $period);

		return $booked >= $capacity;
	}

	private function _get_resource_sharing_services ($service_id, $direct_descentant_only=false) {
		$shared = !empty($this->_data[$service_id])
			? $this->_data[$service_id]
			: array()
		;
		if (!$direct_descentant_only) foreach ($this->_data as $root => $srv) {
			if ($service_id == $root) continue;
			if (is_array($srv) && in_array($service_id, $srv)) {
				$shared[] = $root;
				$shared = array_merge($shared, $srv);
			}
		}
		if (!empty($shared) && !$direct_descentant_only) array_unshift($shared, $service_id);
		return array_map('intval', array_values(array_unique($shared)));
	}

	private function _get_minimum_capacity ($service_ids) {
		$capacities = array();
		foreach ($service_ids as $service_id) {
			$capacities[] = $this->_get_service_capacity($service_id);
		}
		return (int)min($capacities);
	}

	private function _get_service_capacity ($service_id) {
		// First, let's hack around the inflexible capacity getter :(
		$old_service = $this->_core->service;
		$this->_core->service = $service_id;
		// We can get the capacity now...
		$capacity = $this->_core->get_capacity();
		// Revert the changes
		$this->_core->service = $old_service;

		// Deal with capacities
		return (int)$capacity;
	}

	/**
	 * @param array $service_ids
	 * @param App_Period $period
	 *
	 * @return int
	 */
	private function _get_booked_appointments_for_period ($service_ids, $period) {
		$start    = date( 'Y-m-d H:i:s', $period->get_start() );
		$end      = date( 'Y-m-d H:i:s', $period->get_end() );

		$args = array(
			'service' => $service_ids,
			'date_query' => array(
				array(
					'field' => 'end',
					'compare' => '>',
					'value' => $start
				),
				array(
					'field' => 'start',
					'compare' => '<',
					'value' => $end
				),
				'condition' => 'AND'
			),
			'status' => array( 'paid', 'confirmed' ),
			'count' => true
		);

		return appointments_get_appointments( $args );
	}

}
App_Schedule_SharedResources::serve();