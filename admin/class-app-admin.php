<?php

class Appointments_Admin {
	/**
	 * @var Appointments_Admin_User_Profile
	 */
	public $user_profile;

	public function __construct() {
		$this->includes();

		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );

		// Add a column in users list to show if it's a service provider
		add_filter( 'manage_users_custom_column', array( $this, 'render_provider_user_column' ), 10, 3 );
		add_filter( 'manage_users_columns', array( $this, 'add_users_columns' ) );

		add_action( 'admin_menu', array( $this, 'admin_init' ) ); 						// Creates admin settings window
		add_action( 'admin_notices', array( $this, 'admin_notices' ) ); 				// Warns admin
		add_action( 'admin_print_scripts', array( $this, 'admin_scripts') );			// Load scripts
		add_action( 'admin_print_styles', array( $this, 'admin_css') );

		add_action( 'admin_notices', array( $this, 'admin_notices_new' ) );

		add_action( 'wp_ajax_appointments_dismiss_notice', array( $this, 'dismiss_notice' ) );

		new Appointments_Admin_Dashboard_Widget();
		$this->user_profile = new Appointments_Admin_User_Profile();

		include( APP_PLUGIN_DIR . '/admin/admin-helpers.php' );
	}

	private function includes() {
		include_once( appointments_plugin_dir() . 'admin/widgets/class-app-dashboard-widget.php' );
		include_once( appointments_plugin_dir() . 'admin/class-app-admin-user-profile.php' );
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( 'appointments_records_per_page' == $option ) {
			return $value;
		}

		return $status;
	}

	public function add_users_columns( $columns ) {
		$columns['provider'] = __( 'Appointments Provider', 'appointments' );
		return $columns;
	}

	public function render_provider_user_column( $content, $column, $user_id ) {
		if ( 'provider' === $column && appointments_is_worker( $user_id ) && App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_SHORTCODES) ) {
			$link = admin_url( 'admin.php?page=app_settings&tab=workers' );
			$content = '<a href="' . esc_url( $link ) . '"><span class="dashicons dashicons-businessman"></span> ' . __( 'Edit', 'appointments' ) . '</a>';
		}

		return $content;

	}

	public function admin_notices_new() {
		$notices = _appointments_get_admin_notices();
		$noticed = false;
		foreach ( $notices as $notice_slug => $notice ) {
			if ( ! current_user_can( $notice['cap'] ) ) {
				continue;
			}

			$user_dismissed_notices = _appointments_get_user_dismissed_notices( get_current_user_id() );

			if ( $notice_text = _appointments_get_admin_notice( $notice_slug ) ) {
				if ( in_array( $notice_slug, $user_dismissed_notices ) ) {
					continue;
				}

				$noticed = true;
				?>
				<div class="error app-notice">
					<p>
						<strong>Appointments +:</strong> <?php echo $notice_text; ?>
						<a class="app-dismiss" data-dismiss="<?php echo $notice_slug; ?>" href="#" title="<?php esc_attr_e( 'Dismiss notice', 'appointments' ); ?>"> <?php esc_html_e( 'Dismiss', 'appointments' ); ?><span class="dashicons dashicons-dismiss"></span></a>
					</p>
				</div>
				<?php
			}
		}
		if ( $noticed ) {
			?>
			<script>
				jQuery( document).ready( function( $ ) {
					function app_dismiss_notice( slug ) {
						$.ajax({
							url: ajaxurl,
							data: {
								notice: slug,
								_wpnonce: '<?php echo wp_create_nonce( 'app-dismiss-notice' ); ?>',
								action: 'appointments_dismiss_notice'
							}
						})
							.always( function( data ) {
								console.log(data);
							});

					}

					$('.app-dismiss').click( function( e ) {
						e.preventDefault();
						$(this).parent().parent().hide();
						app_dismiss_notice( $(this).data( 'dismiss' ) );
					});
				}( jQuery ) );
			</script>
			<?php
		}
	}

	public function dismiss_notice() {
		check_ajax_referer( 'app-dismiss-notice' );

		$user_id = get_current_user_id();
		if ( $user_id ) {
			$dismissed_notices = _appointments_get_user_dismissed_notices( $user_id );

			$notice_slug = $_REQUEST['notice'];
			if ( _appointments_get_admin_notice( $notice_slug ) && ! in_array( $notice_slug, $dismissed_notices ) ) {
				$dismissed_notices[] = $notice_slug;
				update_user_meta( $user_id, 'app_dismissed_notices', $dismissed_notices );
			}

		}
		die();
	}

	function admin_css() {
		global $appointments;
		wp_enqueue_style( "appointments-admin", $appointments->plugin_url . "/css/admin.css", false, $appointments->version );

		$screen = get_current_screen();
		$title = sanitize_title(__('Appointments', 'appointments'));

		$allow_profile = !empty($appointments->options['allow_worker_wh']) && 'yes' == $appointments->options['allow_worker_wh'];

		if (empty($screen->base) || (
				!preg_match('/(^|\b|_)appointments($|\b|_)/', $screen->base)
				&&
				!preg_match('/(^|\b|_)' . preg_quote($title, '/') . '($|\b|_)/', $screen->base) // Super-weird admin screen base being translatable!!!
				&&
				(!$allow_profile || !preg_match('/profile/', $screen->base) || !(defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE))
			)) return;

		wp_enqueue_style( 'jquery-colorpicker-css', $appointments->plugin_url . '/css/colorpicker.css', false, $appointments->version);
		wp_enqueue_style( "jquery-datepick", $appointments->plugin_url . "/css/jquery.datepick.css", false, $appointments->version );
		wp_enqueue_style( "jquery-multiselect", $appointments->plugin_url . "/css/jquery.multiselect.css", false, $appointments->version );
		wp_enqueue_style( "jquery-ui-smoothness", $appointments->plugin_url . "/css/smoothness/jquery-ui-1.8.16.custom.css", false, $appointments->version );
		do_action('app-admin-admin_styles');
	}

	// Enqeue js on admin pages
	function admin_scripts() {
		global $appointments;
		$screen = get_current_screen();
		$title = sanitize_title(__('Appointments', 'appointments'));

		$allow_profile = !empty($appointments->options['allow_worker_wh']) && 'yes' == $appointments->options['allow_worker_wh'];

		if ( empty( $screen->base ) ) {
			return false;
		}
		if (
			! preg_match( '/(^|\b|_)appointments($|\b|_)/', $screen->base )
			&& ! preg_match( '/(^|\b|_)' . preg_quote( $title, '/' ) . '($|\b|_)/', $screen->base ) // Super-weird admin screen base being translatable!!!
			&& ( ! $allow_profile || ! preg_match( '/profile/', $screen->base ) || ! ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE ) )
			&& 'user-edit' !== $screen->base
		) {
			return false;
		}

		wp_enqueue_script( 'jquery-colorpicker', $appointments->plugin_url . '/js/colorpicker.js', array('jquery'), $appointments->version);
		wp_enqueue_script( 'jquery-datepick', $appointments->plugin_url . '/js/jquery.datepick.min.js', array('jquery'), $appointments->version);
		wp_enqueue_script( 'jquery-multiselect', $appointments->plugin_url . '/js/jquery.multiselect.min.js', array('jquery-ui-core','jquery-ui-widget', 'jquery-ui-position'), $appointments->version);
		// Make a locale check to update locale_error flag
		$date_check = $appointments->to_us( date_i18n( $appointments->safe_date_format(), strtotime('today') ) );

		// Localize datepick only if not defined otherwise
		if (
			!(defined('APP_FLAG_SKIP_DATEPICKER_L10N') && APP_FLAG_SKIP_DATEPICKER_L10N)
			&&
			$file = $appointments->datepick_localfile()
		) {
			//if ( !$this->locale_error ) wp_enqueue_script( 'jquery-datepick-local', $this->plugin_url . $file, array('jquery'), $this->version);
			wp_enqueue_script( 'jquery-datepick-local', $appointments->plugin_url . $file, array('jquery'), $appointments->version);
		}
		if ( empty($appointments->options["disable_js_check_admin"]) )
			wp_enqueue_script( 'app-js-check', $appointments->plugin_url . '/js/js-check.js', array('jquery'), $appointments->version);

		wp_enqueue_script("appointments-admin", $appointments->plugin_url . "/js/admin.js", array('jquery'), $appointments->version);
		wp_localize_script("appointments-admin", "_app_admin_data", array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'strings' => array(
				'preparing_export' => __('Preparing for export, please hold on...', 'appointments'),
			),
		));
		do_action('app-admin-admin_scripts');
	}

	/**
	 *	Dismiss warning messages for the current user for the session
	 *	@since 1.1.7
	 */
	function dismiss() {
		global $current_user;
		if ( isset( $_REQUEST['app_dismiss'] ) ) {
			update_user_meta( $current_user->ID, 'app_dismiss', true );
			?><div class="updated fade"><p><?php _e('Notice dismissed.', 'appointments'); ?></p></div><?php
		}
		if ( isset( $_REQUEST['app_dismiss_google'] ) ) {
			update_user_meta( $current_user->ID, 'app_dismiss_google', true );
			?><div class="updated fade"><p><?php _e('Notice dismissed.', 'appointments'); ?></p></div><?php
		}
		if ( isset( $_REQUEST['app_dismiss_confirmation_lacking'] ) ) {
			update_user_meta( $current_user->ID, 'app_dismiss_confirmation_lacking', true );
			?><div class="updated fade"><p><?php _e('Notice dismissed.', 'appointments'); ?></p></div><?php
		}
	}

	/**
	 *	Warn admin if no services defined or duration is wrong
	 */
	function admin_notices() {
		global $appointments;
		$this->dismiss();

		global $current_user;
		$r = false;
		$results = appointments_get_services();
		if ( !$results ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> You must define at least once service.', 'appointments') .
			     '</p></div>';
			$r = true;
		}
		else {
			foreach ( $results as $result ) {
				if ( $result->duration < $appointments->get_min_time() ) {
					echo '<div class="error"><p>' .
					     __('<b>[Appointments+]</b> One of your services has a duration smaller than time base. Please visit Services tab and after making your corrections save new settings.', 'appointments') .
					     '</p></div>';
					$r = true;
					break;
				}
				if ( $result->duration % $appointments->get_min_time() != 0 ) {
					echo '<div class="error"><p>' .
					     __('<b>[Appointments+]</b> One of your services is not divisible by the time base. Please visit Services tab and after making your corrections save new settings.', 'appointments') .
					     '</p></div>';
					$r = true;
					break;
				}
				if ( $result->duration > 1440 ) {
					echo '<div class="error"><p>' .
					     __('<b>[Appointments+]</b> One of your services has a duration greater than 24 hours. Appointments+ does not support services exceeding 1440 minutes (24 hours). ', 'appointments') .
					     '</p></div>';
					$r = true;
					break;
				}
				$dismissed = false;
				$dismiss_id = get_user_meta( $current_user->ID, 'app_dismiss', true );
				if ( $dismiss_id )
					$dismissed = true;
				if ( appointments_get_workers() && !appointments_get_workers_by_service( $result->ID ) && !$dismissed ) {
					echo '<div class="error"><p>' .
					     __('<b>[Appointments+]</b> One of your services does not have a service provider assigned. Delete services you are not using.', 'appointments') .
					     '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
					     '</p></div>';
					$r = true;
					break;
				}
			}
		}
		if ( !$appointments->db_version || version_compare( $appointments->db_version, '1.2.2', '<' ) ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> Appointments+ database tables need to be updated. Please deactivate and reactivate the plugin (DO NOT DELETE the plugin). You will not lose any saved information.', 'appointments') .
			     '</p></div>';
			$r = true;
		}
		// Warn if Openid is not loaded
		$dismissed_g = false;
		$dismiss_id_g = get_user_meta( $current_user->ID, 'app_dismiss_google', true );
		if ( $dismiss_id_g )
			$dismissed_g = true;
		if ( @$appointments->options['accept_api_logins'] && !@$appointments->openid && !$dismissed_g ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> Either php curl is not installed or HTTPS wrappers are not enabled. Login with Google+ will not work.', 'appointments') .
			     '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss_google=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
			     '</p></div>';
			$r = true;
		}
		// Check for duplicate shortcodes for a visited page
		if ( isset( $_GET['post'] ) && $_GET['post'] && $appointments->has_duplicate_shortcode( $_GET['post'] ) ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> More than one instance of services, service providers, confirmation, Paypal or login shortcodes on the same page may cause problems.</p>', 'appointments' ).
			     '</div>';
		}

		// Check for missing confirmation shortcode
		$dismissed_c = false;
		$dismiss_id_c = get_user_meta( $current_user->ID, 'app_dismiss_confirmation_lacking', true );
		if ( $dismiss_id_c )
			$dismissed_c = true;
		if ( !$dismissed_c && isset( $_GET['post'] ) && $_GET['post'] && $appointments->confirmation_shortcode_missing( $_GET['post'] ) ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> Confirmation shortcode [app_confirmation] is always required to complete an appointment.', 'appointments') .
			     '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss_confirmation_lacking=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
			     '</p></div>';
			$r = true;
		}
		return $r;
	}



	function transactions () {
		App_Template::admin_transactions_list();
	}

	function shortcodes_page () {
		global $appointments;
		?>
		<div class="wrap">
			<div class="icon32" style="margin:10px 0 0 0"><img src="<?php echo $appointments->plugin_url . '/images/general.png'; ?>" /></div>
			<h2><?php echo __('Appointments+ Shortcodes','appointments'); ?></h2>
			<div class="metabox-holder columns-2">
				<?php if (file_exists(APP_PLUGIN_DIR . '/includes/support/app-shortcodes.php')) include(APP_PLUGIN_DIR . '/includes/support/app-shortcodes.php'); ?>
			</div>
		</div>
		<?php
	}

	function faq_page () {
		global $appointments;
		?>
		<div class="wrap">
			<div class="icon32" style="margin:10px 0 0 0"><img src="<?php echo $appointments->plugin_url . '/images/general.png'; ?>" /></div>
			<h2><?php echo __('Appointments+ FAQ','appointments'); ?></h2>
			<?php if (file_exists(APP_ADMIN_PLUGIN_DIR . '/app-faq.php')) include(APP_ADMIN_PLUGIN_DIR . '/app-faq.php'); ?>
		</div>
		<?php
	}


	/**
	 *	Admin pages init stuff, save settings
	 *
	 */
	function admin_init() {
		global $appointments;
		if ( !session_id() )
			@session_start();

		include_once( APP_PLUGIN_DIR . '/admin/pages/class-admin-appointments-page.php' );
		include_once( APP_PLUGIN_DIR . '/admin/pages/class-admin-settings-page.php' );
		$appointments_page = new Appointments_Admin_Appointments_Page();
		$appointments_pages[ $appointments_page->page_id ] = $appointments_page;
		$appointments_pages[] = new Appointments_Admin_Settings_Page();
		$appointments_pages[ $appointments_page->page_id ] = $appointments_page;
		
		add_submenu_page('appointments', __('Transactions','appointments'), __('Transactions','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_TRANSACTIONS), "app_transactions", array(&$this,'transactions'));
		add_submenu_page('appointments', __('Shortcodes','appointments'), __('Shortcodes','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_SHORTCODES), "app_shortcodes", array(&$this,'shortcodes_page'));
		add_submenu_page('appointments', __('FAQ','appointments'), __('FAQ','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_FAQ), "app_faq", array(&$this,'faq_page'));
		// Add datepicker to appointments page


		do_action('app-admin-admin_pages_added', $appointments_pages[0]->page_id );

		// Read Location, Service, Worker
		$appointments->get_lsw();
	}
	
}