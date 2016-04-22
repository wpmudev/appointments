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

	}

	/**
	 * Get the screen tabs
	 *
	 * @return array
	 */
	public function get_tabs() {
		$tabs = array(
			'main'          => __( 'General', 'appointments' ),
			'gcal'          => __( 'Google Calendar', 'appointments' ),
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
	 * Get the sections for the current tab
	 *
	 * @param $tab
	 *
	 * @return array
	 */
	public function tab_sections( $tab ) {
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
			return $content;
		}

		return '';
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

		$sections = $this->tab_sections( $tab );

		$callback_tabs = array(
			'addons' => array('App_AddonHandler', 'create_addon_settings'),
		);

		echo $sections;
		echo '<br class="clear">';

		$file = appointments_plugin_dir() . 'admin/views/page-settings-tab-' . $tab . '.php';

		if ( file_exists( $file ) ) {
			require_once( $file );
		}
		else {
			do_action( 'app-settings-tabs', $tab );
		}
	}

}