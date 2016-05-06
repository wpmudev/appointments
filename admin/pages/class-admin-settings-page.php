<?php

class Appointments_Admin_Settings_Page {

	public $page_id = '';


	public function __construct() {
		$this->page_id = add_submenu_page(
			'appointments',
			__( 'Appointments Settings', 'appointments' ),
			__( 'Settings', 'appointments' ),
			App_Roles::get_capability( 'manage_options', App_Roles::CTX_PAGE_SETTINGS ),
			"app_settings",
			array( &$this, 'render' )
		);
		add_action( 'load-' . $this->page_id, array( $this, 'on_load' ) );
	}

	public function on_load() {
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		if ( ! $action ) {
			return;
		}

		if ( 'activate' === $action && isset( $_REQUEST['addon'] ) ) {
			// Activate addon/s
			if ( ! is_array( $_REQUEST['addon'] ) ) {
				check_admin_referer( 'activate-addon' );
				Appointments_Addon::activate_addon( $_REQUEST['addon'] );
			}
			else {
				check_admin_referer( 'bulk-addons' );
				foreach ( $_REQUEST['addon'] as $slug ) {
					Appointments_Addon::activate_addon( $slug );
				}
			}

			wp_redirect( remove_query_arg( array( 'addon', '_wpnonce', 'action' ) ) );
			exit;
		}

		if ( 'deactivate' === $action && isset( $_REQUEST['addon'] ) ) {
			// Activate addon/s
			if ( ! is_array( $_REQUEST['addon'] ) ) {
				check_admin_referer( 'deactivate-addon' );
				Appointments_Addon::deactivate_addon( $_REQUEST['addon'] );
			}
			else {
				check_admin_referer( 'bulk-addons' );
				foreach ( $_REQUEST['addon'] as $slug ) {
					Appointments_Addon::deactivate_addon( $slug );
				}
			}
			wp_redirect( remove_query_arg( array( 'addon', '_wpnonce', 'action' ) ) );
			exit;
		}
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
				'accesibility' => __( 'Accesibility', 'appointments' ),
				'display' => __( 'Display', 'appointments' ),
				'payments' => __( 'Payments', 'appointments' ),
				'notifications' => __( 'Notifications', 'appointments' ),
				'advanced' => __( 'Advanced', 'appointments' )
			)
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
				$links[] = '<li><a href="#section-' . esc_attr( $section_stub ) . '" data-section="section-' . esc_attr( $section_stub ) . '">' . esc_html( $label ) . '</a></li>';
			}
			$content .= implode( ' | ', $links );
			$content .= '</ul>';

			wp_enqueue_script( 'app-settings-sections', appointments_plugin_url() . 'admin/js/admin-settings-sections.js', array( 'jquery' ), appointments_get_db_version(), true );
			wp_enqueue_script( 'app-settings', appointments_plugin_url() . 'admin/js/admin-settings.js', array( 'jquery' ), appointments_get_db_version(), true );

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
				'presets' => $presets
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


	/**
	 *	Render the Settings page
	 */
	function render() {
		$appointments = appointments();

		$appointments->get_lsw();

		$tabs = $this->get_tabs();
		$tab = $this->get_current_tab();

		?>
		<div class="wrap appointments-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $stub => $title ): ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $stub ) ); ?>" class="nav-tab <?php echo $stub == $tab ? 'nav-tab-active' : ''; ?>" id="app_tab_<?php echo $stub; ?>">
						<?php echo esc_html( $title ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<?php $this->admin_settings_tab($tab); ?>

		</div>
		<?php
	}

	public function admin_settings_tab( $tab ) {

		$sections_markup = $this->tab_sections_markup( $tab );
		$sections = $this->get_tab_sections( $tab );

		$callback_tabs = array(
			'addons' => array('App_AddonHandler', 'create_addon_settings'),
		);

		echo $sections_markup;
		echo '<br class="clear">';

		$file = _appointments_get_settings_tab_view_file_path( $tab );

		if ( $file ) {
			require_once( $file );
		}
		else {
			do_action( 'app-settings-tabs', $tab );
		}
	}

}