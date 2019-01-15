<?php

class Appointments_Admin_Settings_Page {

	public $page_id = '';

	public function __construct() {
		$this->page_id = add_submenu_page(
			'appointments',
			__( 'Appointments Settings', 'appointments' ),
			__( 'Settings', 'appointments' ),
			App_Roles::get_capability( 'manage_options', App_Roles::CTX_PAGE_SETTINGS ),
			'app_settings',
			array( &$this, 'render' )
		);
		add_action( 'load-' . $this->page_id, array( $this, 'on_load' ) );
	}

	/**
	 * Get the screen tabs
	 *
	 * @return array
	 */
	public function get_tabs() {
		$tabs = array(
			'main'          => __( 'General', 'appointments' ),
			'working_hours' => __( 'Working Hours', 'appointments' ),
			'exceptions'    => __( 'Exceptions', 'appointments' ),
			'services'      => __( 'Services', 'appointments' ),
			'workers'       => __( 'Service Providers', 'appointments' ),
			'addons'        => __( 'Add-ons', 'appointments' ),
			'log'           => __( 'Logs', 'appointments' ),
		);
		return apply_filters( 'appointments_tabs', $tabs );
	}

	public function get_sections() {
		$sections = array(
			'main' => array(
				'time' => __( 'Time', 'appointments' ),
				'accesibility' => __( 'Accessibility', 'appointments' ),
				'display' => __( 'Display', 'appointments' ),
				'payments' => __( 'Payments', 'appointments' ),
				'notifications' => __( 'Notifications', 'appointments' ),
				'advanced' => __( 'Advanced', 'appointments' ),
				'gdpr' => __( 'GDPR', 'appointments' ),
			),
			'services' => array(
				'services' => __( 'Services', 'appointments' ),
				'new-service' => __( 'Add new Service', 'appointments' ),
				'edit-service' => false,
			),
			'workers' => array(
				'workers' => __( 'Service Providers', 'appointments' ),
				'new-worker' => __( 'Add new Service Provider', 'appointments' ),
				'edit-worker' => false,
			),
		);
		return apply_filters( 'appointments_settings_sections', $sections );
	}

	/**
	 * Get the sections HTML for the current tab
	 *
	 * @param $tab
	 *
	 * @return array
	 */
	public function tab_sections_markup( $tab ) {
		$sections = $this->get_sections();
		if ( isset( $sections[ $tab ] ) ) {
			$content = '<ul class="subsubsub">';
			$links = array();
			foreach ( $sections[ $tab ] as $section_stub => $label ) {
				if ( empty( $label ) ) {
					continue;
				}
				$links[] = '<li><a href="#section-' . esc_attr( $section_stub ) . '" data-section="section-' . esc_attr( $section_stub ) . '" class="'.esc_attr( $tab.'-'.$section_stub ).'">' . esc_html( $label ) . '</a></li>';
			}
			$content .= implode( ' | ', $links );
			$content .= '</ul>';
			wp_enqueue_script( 'app-settings', appointments_plugin_url() . 'admin/js/admin-settings.js', array( 'jquery', 'jquery-ui-slider' ), appointments_get_db_version(), true );
			$appointments = appointments();
			$classes = $appointments->get_classes();
			$presets = array();
			foreach ( $classes as $class => $name ) {
				$presets[ $class ] = array();
				for ( $k = 1; $k <= 3; $k ++ ) {
					$presets[ $class ][ $k ] = $appointments->get_preset( $class, $k );
				}
			}
			wp_localize_script( 'app-settings', 'app_i10n', array(
				'classes' => $classes,
				'presets' => $presets,
				'messages' => array(
					'select_service_provider' => __( 'Please, select at least one service provided', 'appointments' ),
					'workers' => array(
						'delete_confirmation' => __( 'Are you sure to delete this Service Provider?', 'appointments' ),
					),
					'service' => array(
						'delete_confirmation' => __( 'Are you sure to delete this Service?', 'appointments' ),
					),
					'services' => array(
						'delete_confirmation' => __( 'Are you sure to delete selected Services?', 'appointments' ),
					),
					'bulk_actions' => array(
						'no_items' => __( 'Please select some services first.', 'appointments' ),
						'no_action' => __( 'Please select some action first.', 'appointments' ),
					),
				),
			));
			return $content;
		}
		return '';
	}

	/**
	 * Return the sections for a tab
	 *
	 * @param $tab
	 *
	 * @return array
	 */
	public function get_tab_sections( $tab ) {
		$sections = $this->get_sections();
		if ( isset( $sections[ $tab ] ) ) {
			return $sections[ $tab ];
		}
		return array();
	}

	/**
	 * Return the current tab slug
	 *
	 * @return string
	 */
	public function get_current_tab() {
		$tabs = $this->get_tabs();
		if ( empty( $_GET['tab'] ) ) {
			return key( $tabs );
		}
		if ( ! array_key_exists( $_GET['tab'], $tabs ) ) {
			return key( $tabs );
		}
		return $_GET['tab'];
	}

	private function _get_tab_link( $tab ) {
		$url = add_query_arg( 'tab', $tab );
		$url = remove_query_arg( array( 'updated', 'added', 'paged' ), $url );
		return $url;
	}

	/**
	 *	Render the Settings page
	 */
	public function render() {
		$appointments = appointments();
		$appointments->get_lsw();
		$tabs = $this->get_tabs();
		$tab = $this->get_current_tab();
		?>
		<div class="wrap appointments-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) :  ?>
				<div class="updated"><p><?php _e( 'Settings updated', 'appointments' ); ?></p></div>
			<?php endif; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $stub => $title ) :  ?>
					<a href="<?php echo esc_url( $this->_get_tab_link( $stub ) ); ?>" class="nav-tab <?php echo $stub == $tab ? 'nav-tab-active' : ''; ?>" id="app_tab_<?php echo $stub; ?>">
						<?php echo esc_html( $title ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<?php $this->admin_settings_tab( $tab ); ?>

		</div>
		<?php
	}

	public function admin_settings_tab( $tab ) {

		$sections_markup = $this->tab_sections_markup( $tab );
		$sections = $this->get_tab_sections( $tab );

		$callback_tabs = array(
			'addons' => array( 'App_AddonHandler', 'create_addon_settings' ),
		);

		echo $sections_markup;
		echo '<br class="clear">';

		$file = _appointments_get_settings_tab_view_file_path( $tab );

		/**
		 * Load table class
		 */
		switch ( $tab ) {
			case 'services':
			break;
			case 'workers':
			break;
		}

		echo '<div class="appointments-settings-tab-' . $tab . '">';
		if ( $file ) {
			require_once( $file );
		} else {
			do_action( 'app-settings-tabs', $tab, $sections );
			do_action( "appointments-settings-tab-{$tab}", $sections );
		}
		echo '</div>';
	}

	/**
	 * Save the settings
	 */
	public function on_load() {
		/**
		 * set screen options
		 *
		 * @since 2.4.0
		 */
		$tab = $this->get_current_tab();
		switch ( $tab ) {
			case 'services':
				require_once dirname( dirname( __FILE__ ) ).'/class-app-list-table-services.php';
				global $appointments_services_list;
				$option = 'per_page';
				$args = array(
					'label' => __( 'Number of services per page', 'appointments' ),
					'default' => get_user_option( 'app_services_per_page', 20 ),
					'option' => 'app_services_per_page',
				);
				add_screen_option( $option, $args );
				$appointments_services_list = new Appointments_WP_List_Table_Services;
			break;
			case 'workers':
				require_once dirname( dirname( __FILE__ ) ).'/class-app-list-table-workers.php';
				global $appointments_workers_list;
				$option = 'per_page';
				$args = array(
					'label' => __( 'Number of service providers per page', 'appointments' ),
					'default' => get_user_option( 'app_workers_per_page', 20 ),
					'option' => 'app_workers_per_page',
				);
				add_screen_option( $option, $args );
				$appointments_workers_list = new Appointments_WP_List_Table_Workers;
			break;
		}

		// Hidden feature to import/export settings
		if ( current_user_can( 'manage_options' ) && isset( $_GET['app-export-settings'] ) ) {
			$this->export_settings();
		}
		/**
		 * get current action
		 */
		$action = '';
		if ( isset( $_REQUEST['action'] ) ) {
			$action = $_REQUEST['action'];
		}
		if ( '-1' == $action && isset( $_REQUEST['action2'] ) ) {
			$action = $_REQUEST['action2'];
		}
		/**
		 * handle bulk action addon
		 */
		if ( $action && isset( $_REQUEST['addon'] ) ) {
			$this->_save_addons( $action );
			wp_safe_redirect( remove_query_arg( array( 'addon', '_wpnonce', 'action' ) ), 303 );
			exit;
		}

		$action = isset( $_REQUEST['action_app'] ) ? $_REQUEST['action_app'] : false;
		if ( ! $action ) {
			return;
		}

		check_admin_referer( 'update_app_settings', 'app_nonce' );

		$redirect_to = false;
		switch ( $action ) {
			case 'save_main': {
				$this->_save_general_settings();
				break;
			}
			case 'save_working_hours': {
				$this->_save_working_hours();
				break;
			}
			case 'save_exceptions': {
				$this->_save_exceptions();
				break;
			}
			case 'add_new_service': {
				$redirect_to = $this->_add_service();
				break;
			}
			case 'update_service': {
				$redirect_to = $this->_update_service();
				break;
			}
			case 'add_new_worker': {
				$redirect_to = $this->_add_worker();
				break;
			}
			case 'save_workers': {
				$this->_save_workers();
				break;
			}
			case 'update_worker': {
				$redirect_to = $this->_update_worker();
				break;
			}
			case 'save_log': {
				$this->_delete_logs();
			}
			}

			do_action( 'appointments_save_settings', $action );
			$redirect_to = $redirect_to ? $redirect_to : add_query_arg( 'updated', 1 );
			// Redirecting when saving options
			wp_safe_redirect( $redirect_to, 303 );
			die;
	}


	public function _save_addons( $action ) {
		if ( 'activate' === $action && isset( $_REQUEST['addon'] ) ) {
			// Activate addon/s
			if ( ! is_array( $_REQUEST['addon'] ) ) {
				check_admin_referer( 'activate-addon' );
				Appointments_Addon::activate_addon( $_REQUEST['addon'] );
			} else {
				check_admin_referer( 'bulk-addons' );
				foreach ( $_REQUEST['addon'] as $slug ) {
					Appointments_Addon::activate_addon( $slug );
				}
			}
		}

		if ( 'deactivate' === $action && isset( $_REQUEST['addon'] ) ) {
			// Activate addon/s
			if ( ! is_array( $_REQUEST['addon'] ) ) {
				check_admin_referer( 'deactivate-addon' );
				Appointments_Addon::deactivate_addon( $_REQUEST['addon'] );
			} else {
				check_admin_referer( 'bulk-addons' );
				foreach ( $_REQUEST['addon'] as $slug ) {
					Appointments_Addon::deactivate_addon( $slug );
				}
			}
		}
	}

	private function _save_general_settings() {
		$options = appointments_get_options();
		$appointments = appointments();

		$options['min_time']					= $_POST['min_time'];
		$options['additional_min_time']		= trim( $_POST['additional_min_time'] );
		$options['admin_min_time']			= $_POST['admin_min_time'];
		$options['app_lower_limit']			= trim( $_POST['app_lower_limit'] );
		$options['app_limit']					= trim( $_POST['app_limit'] );
		$options['clear_time']				= trim( $_POST['clear_time'] );
		$options['spam_time']					= trim( $_POST['spam_time'] );

		/**
		 * yes/no options
		 */
		$options_names = array(
			'always_load_scripts',
			'allow_overwork',
			'allow_overwork_break',
			'allow_worker_confirm',
			'allow_worker_wh',
			'auto_confirm',
			'login_required',
			'payment_required',
			'send_confirmation',
			'send_notification',
			'send_reminder',
			'send_reminder_worker',
			'send_removal_notification',
			'show_legend',
			'log_emails',
			'gdpr_delete',
			'gdpr_checkbox_show',
		);
		foreach ( $options_names as $name ) {
			$options[ $name ] = isset( $_POST[ $name ] )? $_POST[ $name ]:'no';
		}

		$options['keep_options_on_uninstall']	= isset( $_POST['keep_options_on_uninstall'] );

		$assigned_to = isset( $_POST['dummy_assigned_to'] ) ? $_POST['dummy_assigned_to'] : 0;
		$worker = appointments_get_worker( $assigned_to );
		$is_dummy = is_a( $worker, 'Appointments_Worker' ) && $worker->is_dummy();
		$options['dummy_assigned_to']			= ! $is_dummy ? $assigned_to : 0;

		$options['accept_api_logins']			= isset( $_POST['accept_api_logins'] );
		$options['facebook-no_init']			= isset( $_POST['facebook-no_init'] );
		$options['facebook-app_id']			= trim( $_POST['facebook-app_id'] );
		$options['twitter-app_id']			= trim( $_POST['twitter-app_id'] );
		$options['twitter-app_secret']		= trim( $_POST['twitter-app_secret'] );

		$options['color_set']					= $_POST['color_set'];
		foreach ( $appointments->get_classes() as $class => $name ) {
			$options[ $class.'_color' ]			= $_POST[ $class.'_color' ];
		}
		$options['ask_name']					= isset( $_POST['ask_name'] );
		$options['ask_email']					= isset( $_POST['ask_email'] );
		$options['ask_phone']					= isset( $_POST['ask_phone'] );
		$options['ask_phone']					= isset( $_POST['ask_phone'] );
		$options['ask_address']				= isset( $_POST['ask_address'] );
		$options['ask_city']					= isset( $_POST['ask_city'] );
		$options['ask_note']					= isset( $_POST['ask_note'] );
		$options['additional_css']			= trim( stripslashes_deep( $_POST['additional_css'] ) );

		$options['percent_deposit']			= trim( str_replace( '%', '', $_POST['percent_deposit'] ) );
		$options['fixed_deposit']				= trim( str_replace( $options['currency'], '', $_POST['fixed_deposit'] ) );

		/*
		 * Membership plugin is replaced by Membership2. Old options are
		 * only saved when the depreacted Membership plugin is still active.
		 */
		if ( class_exists( 'M_Membership' ) ) {
			$options['members_no_payment']	= isset( $_POST['members_no_payment'] ); // not used??
			$options['members_discount']		= trim( str_replace( '%', '', $_POST['members_discount'] ) );
			$options['members']				= maybe_serialize( @$_POST['members'] );
		}

		$options['currency'] 					= $_POST['currency'];
		$options['mode'] 						= $_POST['mode'];
		$options['merchant_email'] 			= trim( $_POST['merchant_email'] );
		$options['return'] 					= $_POST['return'];
		$options['allow_free_autoconfirm'] 	= ! empty( $_POST['allow_free_autoconfirm'] );

		$options['confirmation_subject']		= stripslashes_deep( $_POST['confirmation_subject'] );
		$options['confirmation_message']		= stripslashes_deep( $_POST['confirmation_message'] );
		$options['reminder_time']				= str_replace( ' ', '', $_POST['reminder_time'] );
		$options['reminder_time_worker']		= str_replace( ' ', '', $_POST['reminder_time_worker'] );
		$options['reminder_subject']			= stripslashes_deep( $_POST['reminder_subject'] );
		$options['reminder_message']			= stripslashes_deep( $_POST['reminder_message'] );

		$options['removal_notification_subject'] = stripslashes_deep( $_POST['removal_notification_subject'] );
		$options['removal_notification_message'] = stripslashes_deep( $_POST['removal_notification_message'] );

		$options['disable_js_check_admin']	= isset( $_POST['disable_js_check_admin'] );
		$options['disable_js_check_frontend']	= isset( $_POST['disable_js_check_frontend'] );

		$options['allow_cancel'] 				= @$_POST['allow_cancel'];
		$options['cancel_page'] 				= @$_POST['cancel_page'];
		$options['thank_page'] 				= @$_POST['thank_page'];

		/**
		 * GDPR
		 */
		 $allowed_gdpr_tags = array(
			'a' => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
			),
		);

		$options['gdpr_number_of_days'] = filter_input( INPUT_POST, 'gdpr_number_of_days', FILTER_VALIDATE_INT );
		$options['gdpr_number_of_days_user_erease'] = filter_input( INPUT_POST, 'gdpr_number_of_days_user_erease', FILTER_VALIDATE_INT );
		$options['gdpr_checkbox_text'] = wp_kses( $_POST['gdpr_checkbox_text'], $allowed_gdpr_tags );
		$options['gdpr_checkbox_alert'] = filter_input( INPUT_POST, 'gdpr_checkbox_alert', FILTER_SANITIZE_STRING );

		$options = apply_filters( 'app-options-before_save', $options );

		appointments_update_options( $options );

		// Flush cache
		appointments_clear_cache();

	}

	private function _save_working_hours() {
		// Save Working Hours
		$appointments = appointments();
		$location = (int) $_POST['location'];

		if ( 0 == $location ) {
			$worker = appointments_get_worker( $appointments->worker );
			$location = isset( $worker->worker_location ) ? $worker->worker_location : $location;
		}

		foreach ( array( 'closed', 'open' ) as $stat ) {
			appointments_update_worker_working_hours( $appointments->worker, $_POST[ $stat ], $stat, $location );
		}
	}

	private function _save_exceptions() {
		// Save Exceptions
		$location = (int) $_POST['location'];
		$worker_id = absint( $_POST['worker_id'] );
		check_admin_referer( 'app_settings_exceptions-' . $worker_id, 'app_exceptions_nonce' );

		foreach ( array( 'closed', 'open' ) as $stat ) {
			$exceptions = $this->_sort( $_POST[ $stat ]['exceptional_days'] );
			appointments_update_worker_exceptions( $worker_id, $stat, $exceptions, $location );
		}
	}

	private function _add_service() {
		$args = array(
			'name' => sanitize_text_field( $_POST['service_name'] ),
			'capacity' => absint( $_POST['service_capacity'] ),
			'duration' => absint( $_POST['service_duration'] ),
			'price' => sanitize_text_field( $_POST['service_price'] ),
			'page' => absint( $_POST['service_page'] ),
		);
		$app_id = appointments_insert_service( $args );
		if ( is_wp_error( $app_id ) ) {
			wp_die( $app_id->get_error_message() );
		}

		if ( ! $app_id ) {
			return false;
		}

		return admin_url( 'admin.php?page=app_settings&tab=services&added=true#section-services' );
	}

	/**
	 * Update service
	 *
	 * @since 2.3.0
	 */
	private function _update_service() {
		if ( ! isset( $_POST['app_nonce'] ) ) {
			return false;
		}
		if ( ! wp_verify_nonce( $_POST['app_nonce'], 'update_app_settings' ) ) {
			return false;
		}
		if ( ! isset( $_POST['id'] ) ) {
			return false;
		}
		$ID = filter_var( $_POST['id'], FILTER_VALIDATE_INT );
		$_service = appointments_get_service( $ID );
		if ( false === $_service ) {
			return false;
		}
		do_action( 'app-services-before_save' );
		/**
		 * update
		 */
		$args = array();
		/**
		 * values: integers
		 */
		$keys = array( 'duration', 'capacity', 'page' );
		foreach ( $keys as $k ) {
			$key = 'service_'.$k;
			$value = 0;
			if ( isset( $_POST[ $key ] ) ) {
				$value = filter_var( $_POST[ $key ], FILTER_VALIDATE_INT );
			}
			$args[ $k ] = $value;
		}
		/**
		 * values: strings
		 */
		$keys = array( 'name', 'price' );
		foreach ( $keys as $k ) {
			$key = 'service_'.$k;
			$value = '';
			if ( isset( $_POST[ $key ] ) ) {
				$value = filter_var( $_POST[ $key ], FILTER_SANITIZE_STRING );
			}
			$args[ $k ] = $value;
		}
		appointments_update_service( $ID, $args );
		do_action( 'app-services-service-updated', $ID );
		$url = add_query_arg(
			array(
				'page' => 'app_settings',
				'tab' => 'services',
				'updated' => true,
			),
			admin_url( 'admin.php' )
		);
		return $url.'#section-services';
	}

	private function _add_worker() {
		if ( empty( $_POST['services_provided'] ) ) {
			wp_die( __( 'Please, select at least one service provided', 'appointments' ) );
		}

		// Insert
		$args = array(
			'ID'				=> $_POST['user'],
			'price'				=> sanitize_text_field( $_POST['price'] ),
			'services_provided'	=> $_POST['services_provided'],
			'page'				=> absint( $_POST['worker_page'] ),
			'dummy'				=> isset( $_POST['dummy'] ),
		);
		$worker_id = appointments_insert_worker( $args );

		if ( ! $worker_id ) {
			return false;
		}

		return admin_url( 'admin.php?page=app_settings&tab=workers&added=true#section-workers' );
	}

	private function _save_workers() {
		// Save Workers
		if ( ! is_array( $_POST['workers'] ) ) {
			return;
		}

		foreach ( $_POST['workers'] as $worker_id => $worker ) {
			$new_worker_id = absint( $worker['user'] );
			$worker_id = absint( $worker_id );

			if ( appointments_is_worker( $worker_id ) ) {
				// Update
				if ( ( $new_worker_id != $worker_id ) && ! empty( $worker['services_provided'] ) ) {
					// We are trying to chage the user ID
					$count = appointments_get_worker( $new_worker_id );

					// If the new ID already exist, do nothing
					if ( ! $count ) {
						// Otherwise, change the ID
						$args = array(
							'ID' => $new_worker_id,
							'price' => $worker['price'],
							'services_provided' => $worker['services_provided'],
							'dummy' => isset( $worker['dummy'] ),
							'page' => $worker['page'],
						);
						appointments_update_worker( $worker_id, $args );
					}
				} elseif ( ( $new_worker_id == $worker_id ) && ! empty( $worker['services_provided'] ) ) {
					// Do not change user ID but update
					$args = array(
						'price' => $worker['price'],
						'services_provided' => $worker['services_provided'],
						'dummy' => isset( $worker['dummy'] ),
						'page' => $worker['page'],
					);
					appointments_update_worker( $worker_id, $args );
				} elseif ( empty( $worker['services_provided'] ) ) {
					appointments_delete_worker( $worker_id );
				}

				do_action( 'app-workers-worker-updated', $worker_id );
			}
		}
	}

	/**
	 * Update worker
	 *
	 * @since 2.3.0
	 */
	private function _update_worker() {
		if ( ! isset( $_POST['app_nonce'] ) ) {
			return false;
		}
		if ( ! wp_verify_nonce( $_POST['app_nonce'], 'update_app_settings' ) ) {
			return false;
		}
		if ( ! isset( $_POST['worker_user'] ) ) {
			return false;
		}
		$ID = filter_var( $_POST['worker_user'], FILTER_VALIDATE_INT );
		$_worker = appointments_get_worker( $ID );
		if ( false === $_worker ) {
			return false;
		}
		do_action( 'app-workers-before_save' );
		/**
		 * update
		 */
		$args = array();
		/**
		 * values: integers
		 */
		$keys = array( 'page' );
		foreach ( $keys as $k ) {
			$key = 'worker_'.$k;
			$value = 0;
			if ( isset( $_POST[ $key ] ) ) {
				$value = filter_var( $_POST[ $key ], FILTER_VALIDATE_INT );
			}
			$args[ $k ] = $value;
		}
		/**
		 * values: strings
		 */
		$keys = array( 'price', 'dummy' );
		foreach ( $keys as $k ) {
			$key = 'worker_'.$k;
			$value = '';
			if ( isset( $_POST[ $key ] ) ) {
				$value = filter_var( $_POST[ $key ], FILTER_SANITIZE_STRING );
			}
			$args[ $k ] = $value;
		}
		/**
		 * check dummy
		 */
		$args['dummy'] = 'on' === $args['dummy'];
		/**
		 * services_provided
		 */
		$args['services_provided'] = isset( $_POST['services_provided'] )? $_POST['services_provided']:array();
		appointments_update_worker( $ID, $args );
		do_action( 'app-workers-worker-updated', $ID );
		$url = add_query_arg(
			array(
				'page' => 'app_settings',
				'tab' => 'workers',
				'updated' => true,
			),
			admin_url( 'admin.php' )
		);
		return $url.'#section-workers';
	}

	private function _delete_logs() {
		$appointments = appointments();
		@unlink( $appointments->log_file );
	}


	/**
	 *	Sorts a comma delimited string
	 *	@since 1.2
	 */
	public function _sort( $input ) {
		if ( strpos( $input, ',' ) === false ) {
			return $input; }
		$temp = explode( ',', $input );
		sort( $temp );
		return implode( ',', $temp );
	}

	private function export_settings() {
		$options = maybe_serialize( appointments_get_options() );
		$services = appointments_get_services();
		$workers = appointments_get_workers();
	}
}
