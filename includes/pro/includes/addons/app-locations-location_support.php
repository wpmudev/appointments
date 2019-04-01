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

	private function __construct() {}

	public static function serve() {
		$me = new App_Locations_LocationsWorker;
		$me->_add_hooks();
		return $me;
	}

	private function _add_hooks() {
		add_action( 'plugins_loaded', array( $this, 'initialize' ) );

		// Set up admin interface
		add_filter( 'appointments_tabs', array( $this, 'settings_tab_add' ) );
		add_action( 'appointments-settings-tab-locations', array( $this, 'settings_tab_create' ) );
		add_filter( 'appointments_tabs', array( $this, 'add_settings_tab' ) );
		add_filter( 'appointments_settings_sections', array( $this, 'add_settings_sections' ) );
		add_filter( 'appointments_save_settings', array( $this, 'save_settings' ) );
		add_action( 'app-admin-admin_scripts', array( $this, 'include_scripts' ) );

		// Appointments list
		add_filter( 'app-appointments_list-edit-services', array( $this, 'show_appointment_location' ), 10, 2 );
		add_filter( 'app-appointment-inline_edit-save_data', array( $this, 'save_appointment_location' ) );

		add_filter( 'appointments_notification_replacements', array( $this, 'add_notifications_replacements' ), 10, 4 );

		add_filter( 'appointments_gcal_event_location', array( $this, 'set_gcal_location' ), 10, 2 );

		add_filter( 'appointments_default_options', array( $this, 'default_options' ) );
	}

	public function default_options( $options ) {
		$options['locations_settings'] = array();
		$options['locations_settings']['all_appointments'] = '';
		$options['locations_settings']['my_appointments'] = '';
		return $options;
	}


	/**
	 * Set Google Calendar Location
	 *
	 * @param $location
	 * @param $app
	 */
	public function set_gcal_location( $location, $app ) {
		if ( isset( $options['gcal_location'] ) && '' != trim( $options['gcal_location'] ) ) {
			// Leave the current value if there's a location set in GCal options
			return $location;
		}

		$app_location = appointments_get_location( $app->location );
		if ( $app_location ) {
			return $app_location->address;
		}

		return $location;
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

		$app_location = $object->location;
		if ( empty( $app_location ) ) {
			return $replacement;
		}

		$location = $this->_locations->find_by( 'id', $object->location );
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

	public function save_appointment_location( $data ) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return $data;
		}
		$location_id      = ! empty( $_POST['location'] ) ? $_POST['location'] : false;
		$data['location'] = $location_id;

		return $data;
	}

	public function show_appointment_location( $deprecated, $appointment ) {
		$editable = '';
		$out = '';
		$all = $this->_locations->get_all();
		$editable .= '<span class="title">' . __( 'Location', 'appointments' ) . '</span>';
		$editable .= '<select name="location"><option value=""></option>';
		foreach ( $all as $loc ) {
			/** @var AppLocation $loc */
			$sel = selected( $loc->get_id(), $appointment->location, false );
			$editable .= '<option value="' . esc_attr( $loc->get_id() ) . '" ' . $sel . '>' . $loc->get_admin_label() . '</option>';
		}
		$editable .= '</select>';

		echo $out . "<label>{$editable}</label>";
	}

	public function include_scripts() {
		global $appointments;
		wp_enqueue_script(
			'app-locations',
			$appointments->plugin_url . '/assets/js/appointments-locations.min.js',
			array( 'jquery' ),
			$appointments->version
		);
		wp_localize_script('app-locations', '_app_locations_data', apply_filters('app-locations-location_model_template', array(
			'model' => array(
				'fields' => array(
					'address' => __( 'Address', 'appointments' ),
				),
				'labels' => array(
					'add_location' => __( 'Add', 'appointments' ),
					'save_location' => __( 'Save', 'appointments' ),
					'new_location' => __( 'Create a New Location', 'appointments' ),
					'edit_location' => __( 'Edit Location', 'appointments' ),
					'cancel_editing' => _x( 'Cancel', 'Drop current action', 'appointments' ),
				),
			),
		)));
	}

	public function add_settings_sections( $sections ) {
		$sections['locations'] = array(
			'locations' => __( 'Edit Locations', 'appointments' ),
			'settings' => __( 'Settings', 'appointments' ),
		);

		return $sections;
	}

	public function add_settings_tab( $tabs ) {
		$tabs['locations'] = __( 'Locations', 'appointments' );
		return $tabs;
	}

	public function settings_tab_add( $tabs ) {
		$ret = array();
		foreach ( $tabs as $key => $label ) {
			if ( $key == self::INJECT_TAB_BEFORE ) {
				$ret[ self::SETTINGS_TAB ] = __( 'Locations', 'appointments' );
			}
			$ret[ $key ] = $label;
		}
		return $ret;
	}

	public function locations_settings_section() {
		include_once( appointments_plugin_dir() . 'includes/pro/includes/addons/lib/class-app-locations-list-table.php' );
		$table = new Appointments_Locations_List_Table();

		$locations = $this->_locations->get_all();

		$table->items = $locations;
		$table->prepare_items();
		?>

		<div id="col-container">
			<div id="col-right">
				<div class="col-wrap">
					<?php $table->display(); ?>
				</div><!-- col-right -->
			</div><!-- col-wrap -->
			<div id="col-left">
				<div class="col-wrap">
					<div class="form-wrap">
						<form action="" method="post" id="add-location">
							<?php if ( isset( $_GET['error'] ) ) :  ?>
								<div class="error">
									<p><?php _e( 'Address cannot be empty', 'appointments' ); ?></p>
								</div>
							<?php endif; ?>

							<h2><?php _e( 'Add new Location', 'appointments' ); ?></h2>

							<div class="form-field form-required">
								<label for="location-add"><?php _e( 'Address', 'appointments' ); ?></label>
								<input type="text" id="location-add" name="location" value="">
							</div>

							<?php _appointments_settings_submit_block( 'add_locations', __( 'Add Location', 'appointments' ) ); ?>
						</form>

						<form action="" method="post" id="edit-location" class="hidden">
							<h2><?php _e( 'Edit Location', 'appointments' ); ?></h2>

							<div class="form-field form-required">
								<label for="location-edit"><?php _e( 'Address', 'appointments' ); ?></label>
								<input type="text" id="location-edit" name="location" value="">
							</div>
							<input type="hidden" id="location-id" name="location_id" value="">

							<?php _appointments_settings_submit_block( 'edit_locations', __( 'Edit Location', 'appointments' ) ); ?>
						</form>

						<script>
							jQuery(document).ready( function( $ ) {
								var editForm = $('#edit-location');
								var addForm = $('#add-location');
								$('.edit-location').click( function(e) {
									e.preventDefault();
									addForm.hide();
									editForm.show();
									editForm.find( '#location-id' ).val( $(this).data('location-id' ) );
									editForm.find( '#location-edit' ).val( $(this).data('location-name' ) );
								});

								$('.delete-location').click( function( e ) {
									return confirm( '<?php _e( 'Are you sure that you want to delete this location?', 'appointments' ); ?>');
								});
							});
						</script>

					</div>
				</div><!-- col-right -->
			</div><!-- col-wrap -->
		</div><!-- col-container -->
		<?php
		do_action( 'appointments_locations_settings_section_locations' );
	}

	public function settings_settings_section() {
		?>
		<form method="post" action="" >
			<table class="form-table">
				<tr>
					<th scope="row"><label for="locations_settings-my-appointments"><?php _e( 'Show my appointments location', 'appointments' )?></label></th>
					<td>
						<select id="locations_settings-my-appointments" name="locations_settings[my_appointments]" autocomplete="off">
							<option value=""></option>
							<option value="after_service" <?php selected( $this->_data['locations_settings']['my_appointments'], 'after_service' ); ?> ><?php _e( 'Automatic, after service', 'appointments' ); ?></option>
							<option value="after_worker" <?php selected( $this->_data['locations_settings']['my_appointments'], 'after_worker' ); ?> ><?php _e( 'Automatic, after provider', 'appointments' ); ?></option>
							<option value="after_date" <?php selected( $this->_data['locations_settings']['my_appointments'], 'after_date' ); ?> ><?php _e( 'Automatic, after date/time', 'appointments' ); ?></option>
							<option value="after_status" <?php selected( $this->_data['locations_settings']['my_appointments'], 'after_status' ); ?> ><?php _e( 'Automatic, after status', 'appointments' ); ?></option>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="locations_settings-all-appointments"><?php _e( 'Show all appointments location', 'appointments' )?></label></th>
					<td>
						<select id="locations_settings-all-appointments" name="locations_settings[all_appointments]" autocomplete="off">
							<option value=""></option>
							<option value="after_service" <?php selected( $this->_data['locations_settings']['all_appointments'], 'after_service' ); ?> ><?php _e( 'Automatic, after service', 'appointments' ); ?></option>
							<option value="after_provider" <?php selected( $this->_data['locations_settings']['all_appointments'], 'after_provider' ); ?> ><?php _e( 'Automatic, after provider', 'appointments' ); ?></option>
							<option value="after_client" <?php selected( $this->_data['locations_settings']['all_appointments'], 'after_client' ); ?> ><?php _e( 'Automatic, after client', 'appointments' ); ?></option>
							<option value="after_date" <?php selected( $this->_data['locations_settings']['all_appointments'], 'after_date' ); ?> ><?php _e( 'Automatic, after date/time', 'appointments' ); ?></option>
							<option value="after_status" <?php selected( $this->_data['locations_settings']['all_appointments'], 'after_status' ); ?> ><?php _e( 'Automatic, after status', 'appointments' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
			<?php do_action( 'appointments_locations_settings_section_settings' ); ?>
			<?php _appointments_settings_submit_block( 'locations' ); ?>
		</form>
		<?php
	}

	public function settings_tab_create( $sections ) {

		?>
			<?php do_action( 'app-locations-settings-before_locations_list' ); ?>

			<?php foreach ( $sections as $section => $name ) :  ?>
				<div class="app-settings-section" id="app-settings-section-<?php echo $section; ?>">
					<?php
						$function_name = array( $this, str_replace( '-', '_', $section ) . '_settings_section' );
						call_user_func_array( $function_name, array() );
						do_action( "appointments_locations_after_settings_section_{$section}" );
					?>
				</div>
			<?php endforeach; ?>

			<?php do_action( 'app-locations-settings-after_locations_list' ); ?>
		<?php
	}

	public function save_settings( $action ) {

		if ( ! App_Roles::current_user_can( 'manage_options', App_Roles::CTX_PAGE_SETTINGS ) ) {
			return;
		}

		if ( 'save_add_locations' === $action ) {
			if ( empty( $_REQUEST['location'] ) ) {
				wp_redirect( add_query_arg( 'error', 'true' ) );
				exit;
			}

			appointments_insert_location( array( 'address' => stripslashes_deep( $_REQUEST['location'] ) ) );

		}

		if ( 'save_edit_locations' === $action ) {
			if ( empty( $_REQUEST['location'] ) ) {
				wp_redirect( add_query_arg( 'error', 'true' ) );
				exit;
			}
			$location_id = $_REQUEST['location_id'];
			if ( appointments_get_location( $location_id ) ) {
				appointments_update_location( $location_id, array( 'address' => stripslashes_deep( $_REQUEST['location'] ) ) );
			}
		}

		if ( 'save_delete_locations' === $action ) {
			$location_id = $_REQUEST['location_id'];
			if ( appointments_get_location( $location_id ) ) {
				appointments_delete_location( $location_id );
			}

			$redirect = remove_query_arg( array( 'action_app', 'location_id', 'app_nonce' ) );
			wp_redirect( add_query_arg( 'updated', 1, $redirect ) );
			exit;
		}

		if ( $action === 'save_locations' ) {
			$options = get_option( 'appointments_options', array() );
			$settings = stripslashes_deep( $_POST['locations_settings'] );
			$options['locations_settings'] = ! empty( $settings ) ? $settings : array();
			$options = apply_filters( 'app-locations-before_save', $options );
			appointments_update_options( $options );
		}

	}

	public function initialize() {
		$this->_data = appointments_get_options();

		if ( ! class_exists( 'App_Locations_Model' ) ) { require_once( dirname( __FILE__ ) . '/lib/app_locations.php' ); }
		$this->_locations = App_Locations_Model::get_instance();

		do_action( 'app-locations-initialized' );

		if ( ! empty( $this->_data['locations_settings']['my_appointments'] ) ) {
			$injection_point = $this->_data['locations_settings']['my_appointments'];
			add_filter( 'app_my_appointments_column_name', array( $this, 'my_appointments_headers' ), 1 );
			add_filter( 'app-shortcode-my_appointments-' . $injection_point, array( $this, 'my_appointments_address' ), 1, 2 );
		}
		if ( ! empty( $this->_data['locations_settings']['all_appointments'] ) ) {
			$injection_point = $this->_data['locations_settings']['all_appointments'];
			add_filter( 'app_all_appointments_column_name', array( $this, 'all_appointments_headers' ), 1 );
			add_filter( 'app-shortcode-all_appointments-' . $injection_point, array( $this, 'all_appointments_address' ), 1, 2 );
		}

		if ( empty( $this->_data['locations_settings']['all_appointments'] ) ) {
			$this->_data['locations_settings']['all_appointments'] = '';
		}
		if ( empty( $this->_data['locations_settings']['my_appointments'] ) ) {
			$this->_data['locations_settings']['my_appointments'] = '';
		}

		// Add macro expansion filtering
		add_filter( 'app-codec-macros', array( $this, 'add_to_macro_list' ) );
		add_filter( 'app-codec-macro_default-replace_location', array( $this, 'expand_location_macro' ), 10, 3 );
		add_filter( 'app-codec-macro_default-replace_location_address', array( $this, 'expand_location_address_macro' ), 10, 2 );

		// GCal expansion filters
		add_filter( 'app-gcal-set_summary', array( $this, 'expand_location_macro' ), 10, 2 );
		add_filter( 'app-gcal-set_summary', array( $this, 'expand_location_address_macro' ), 10, 2 );
		add_filter( 'app-gcal-set_description', array( $this, 'expand_location_macro' ), 10, 2 );
		add_filter( 'app-gcal-set_description', array( $this, 'expand_location_address_macro' ), 10, 2 );
	}

	public function add_to_macro_list( $macros ) {
		$macros[] = 'LOCATION';
		$macros[] = 'LOCATION_ADDRESS';
		return $macros;
	}

	public function expand_location_address_macro( $content, $app ) {
		if ( empty( $app->location ) ) { return $content; }

		$location = $this->_locations->find_by( 'id', $app->location );
		if ( empty( $location ) ) { return $content; }

		$address = $location->get_address();
		return preg_replace( '/(?:^|\b)LOCATION_ADDRESS(?:\b|$)/', $address, $content );
	}

	public function expand_location_macro( $content, $app, $filter = false ) {
		if ( empty( $app->location ) ) { return $content; }

		$location = $this->_locations->find_by( 'id', $app->location );
		if ( empty( $location ) ) { return $content; }

		$address = $location->get_display_markup(
			(App_Macro_Codec::FILTER_BODY == $filter)
		);
		return preg_replace( '/(?:^|\b)LOCATION(?:\b|$)/', $address, $content );
	}

	public function my_appointments_headers( $headers ) {
		$where = preg_replace( '/^after_/', '', $this->_data['locations_settings']['my_appointments'] );
		if ( ! $where ) { return $headers; }
		$rx = '(' .
			preg_quote( '<th class="my-appointments-' . $where . '">', '/' ) .
			'.*?' .
			preg_quote( '</th>', '/' ) .
		')';
		$location = '<th class="my-appointments-location">' . __( 'Location', 'appointments' ) . '</th>';
		return preg_replace( "/{$rx}/", '\1' . $location, $headers );
	}

	public function my_appointments_address( $out, $appointment ) {
		if ( empty( $appointment->location ) ) {
			return $out . '<td>&nbsp;</td>';
		}
		$out .= '<td>';
		$location = $this->_locations->find_by( 'id', $appointment->location );
		if ( $location ) {
			$out .= $location->get_display_markup( false );
		}
		$out .= '</td>';
		return $out;
	}

	public function all_appointments_headers( $headers ) {
		$where = preg_replace( '/^after_/', '', $this->_data['locations_settings']['all_appointments'] );
		if ( ! $where ) { return $headers; }
		$rx = '(' .
			preg_quote( '<th class="all-appointments-' . $where . '">', '/' ) .
			'.*?' .
			preg_quote( '</th>', '/' ) .
		')';
		$location = '<th class="all-appointments-location">' . __( 'Location', 'appointments' ) . '</th>';
		return preg_replace( "/{$rx}/", '\1' . $location, $headers );
	}

	public function all_appointments_address( $out, $appointment ) {
		if ( empty( $appointment->location ) ) { return $out . '<td>&nbsp;</td>'; }
		$out .= '<td>';
		$location = $this->_locations->find_by( 'id', $appointment->location );
		if ( $location ) {
			$out .= $location->get_display_markup( false );
		}
		$out .= '</td>';
		return $out;
	}
}

// Serve the main entry point
App_Locations_LocationsWorker::serve();
