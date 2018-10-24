<?php

/**
 * Appointments GDPR
 *
 * @since 2.3.0
 */

class Appointments_GDPR {
	/**
	 * GDPR wp_cron event name
	 *
	 * @since 2.3.0
	 */
	private $gdpr_delete = 'appointments_gdpr_wp_cron';

	/**
	 * GDPR admin notice when number of days was changed
	 *
	 * @since 2.3.0
	 */
	private $gdpr_admin_notice_names = array(
		'user_erase' => 'appointments_gdpr_nod_was_changed',
		'auto_erase' => 'appointments_gdpr_auto_erase_was_changed',
	);

	public function __construct() {
		global $wp_version;
		$is_less_496 = version_compare( $wp_version, '4.9.6', '<' );
		if ( $is_less_496 ) {
			return;
		}
		/**
		 * GDPR - delete
		 */
		if ( ! wp_next_scheduled( $this->gdpr_delete ) ) {
			wp_schedule_event( time(), 'hourly', $this->gdpr_delete );
		}
		add_action( $this->gdpr_delete, array( $this, 'gdpr_check_and_delete' ) );
		/**
		 * Add information to privacy policy page (only during creation).
		 */
		add_action( 'admin_init', array( $this, 'add_policy' ) );
		/**
		 * Adding the Personal Data Exporter
		 */
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_plugin_exporter' ), 10 );
		/**
		 * Adding the Personal Data Eraser
		 */
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_plugin_eraser' ), 10 );
		/**
		 * check changes
		 */
		add_filter( 'app-options-before_save', array( $this, 'check_options_changes' ) );
		/**
		 * show notice
		 */
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
		/**
		 * save notice status
		 */
		add_action( 'wp_ajax_gdpr_number_of_days_user_erease', array( $this, 'delete_notice_when_we_changed_user_delete_number_of_days' ) );
		add_action( 'wp_ajax_gdpr_number_of_days_auto_erease', array( $this, 'delete_notice_when_we_changed_auto_delete_number_of_days' ) );
	}

	/**
	 * Delete user option after clic.
	 *
	 * @since 2.3.0
	 */
	public function delete_notice_when_we_changed_auto_delete_number_of_days() {
		if (
			isset( $_POST['nonce'] )
			&& wp_verify_nonce( $_POST['nonce'], $this->gdpr_admin_notice_names['auto_erase'] )
			&& isset( $_POST['user_id'] )
		) {
			$user_id = filter_input( INPUT_POST, 'user_id', FILTER_VALIDATE_INT );
			delete_user_option( $user_id, $this->gdpr_admin_notice_names['auto_erase'] );
		}
		wp_send_json_success();
	}

	/**
	 * Delete user option after clic.
	 *
	 * @since 2.3.0
	 */
	public function delete_notice_when_we_changed_user_delete_number_of_days() {
		if (
			isset( $_POST['nonce'] )
			&& wp_verify_nonce( $_POST['nonce'], $this->gdpr_admin_notice_names['user_erase'] )
			&& isset( $_POST['user_id'] )
		) {
			$user_id = filter_input( INPUT_POST, 'user_id', FILTER_VALIDATE_INT );
			delete_user_option( $user_id, $this->gdpr_admin_notice_names['user_erase'] );
		}
		wp_send_json_success();
	}

	/**
	 * Check options changes to show admin notice
	 *
	 * @since 2.3.0
	 */
	public function check_options_changes( $options ) {
		/**
		 * User can erase after
		 */
		$current = appointments_get_option( 'gdpr_number_of_days_user_erease' );
		/**
		 * check changes in gdpr_number_of_days_user_erease
		 */
		$change = $current !== $options['gdpr_number_of_days_user_erease'];
		if ( $change ) {
			$user_id = get_current_user_id();
			update_user_option( $user_id, $this->gdpr_admin_notice_names['user_erase'], 'show' );
		}
		/**
		 * Auto erase after
		 */
		/**
		 * User can erase after
		 */
		$current = appointments_get_option( 'gdpr_number_of_days' );
		/**
		 * check changes in gdpr_number_of_days_user_erease
		 */
		$change = $current !== $options['gdpr_number_of_days'];
		if ( $change ) {
			$user_id = get_current_user_id();
			update_user_option( $user_id, $this->gdpr_admin_notice_names['auto_erase'], 'show' );
		}
		/**
		 * return
		 */
		return $options;
	}

	/**
	 * Show admin notice - template
	 *
	 * @since 2.3.0
	 */
	private function notice_template() {
		$content = '<div id="%s" class="notice notice-success is-dismissible notice-app-gdpr" data-nonce="%s" data-user_id="%s">';
		$content .= wpautop( __( 'Appointments/GDPR setting "<strong>%s</strong>" was changed - please update your %s', 'appointments' ) );
		$content .= '</div>';
		return $content;
	}
	/**
	 * Show admin notice
	 *
	 * @since 2.3.0
	 */
	public function show_notices() {
		if ( ! function_exists( 'get_privacy_policy_url' ) ) {
			return;
		}
		$policy_page = get_privacy_policy_url();
		if ( empty( $policy_page ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! is_a( $screen, 'WP_Screen' ) ) {
			return;
		}
		if ( ! preg_match( '/appointments/', $screen->base ) ) {
			return;
		}
		$user_id = get_current_user_id();
		$template = $this->notice_template();
		/**
		 * Privacy Policy Page
		 */
			$link = sprintf(
				'<a href="%s">%s</a>',
				esc_attr( $policy_page ),
				esc_html__( 'Privacy Policy page', 'appointments' )
			);
			/**
		 * template
		 */
			/**
		 * User can erase after
		 */
			$show = get_user_option( $this->gdpr_admin_notice_names['user_erase'] );
			if ( 'show' === $show ) {
				printf(
					$template,
					esc_attr( 'gdpr_number_of_days_user_erease' ),
					wp_create_nonce( $this->gdpr_admin_notice_names['user_erase'] ),
					esc_attr( $user_id ),
					esc_attr__( 'User can erase after', 'appointments' ),
					$link
				);
			}
			/**
		 * Auto erase after
		 */
			$show = get_user_option( $this->gdpr_admin_notice_names['auto_erase'] );
			if ( 'show' === $show ) {
				printf(
					$template,
					esc_attr( 'gdpr_number_of_days_auto_erease' ),
					wp_create_nonce( $this->gdpr_admin_notice_names['auto_erase'] ),
					esc_attr( $user_id ),
					esc_attr__( 'Auto erase after', 'appointments' ),
					$link
				);
			}
	}

	/**
	 * Get number of days of auto-erase data.
	 *
	 * @since 2.3.0
	 */
	private function get_number_of_days() {
		$value = appointments_get_option( 'gdpr_delete' );
		if ( 'yes' !== $value ) {
			return 0;
		}
		return intval( appointments_get_option( 'gdpr_number_of_days' ) );
	}

	/**
	 * Get plugin friendly name
	 */
	private function get_plugin_friendly_name() {
		$name = _x( 'Appointments Plugin', 'Free plugin name in personal data exporter.', 'appointments' );
		$is_pro = apply_filters( 'appointments_is_pro', false );
		if ( $is_pro ) {
			$name = _x( 'Appointments+ Plugin', 'Pro plugin name in personal data exporter.', 'appointments' );
		}
		return $name;
	}

	/**
	 * Register plugin exporter.
	 *
	 * @since 2.3.0
	 */
	public function register_plugin_exporter( $exporters ) {
		$exporters['appointments'] = array(
			'exporter_friendly_name' => $this->get_plugin_friendly_name(),
			'callback' => array( $this, 'plugin_exporter' ),
		);
		return $exporters;
	}

	/**
	 * Export personal data.
	 *
	 * @since 2.3.0
	 */
	public function plugin_exporter( $email, $page = 1 ) {
		$appointments = appointments_get_appointments( array( 'email' => $email ) );
		$export_items = array();
		if ( count( $appointments ) ) {
			foreach ( $appointments as $appointment ) {
				$item = array(
					'group_id' => 'appointments',
					'group_label' => $this->get_plugin_friendly_name(),
					'item_id' => 'appointments-'.$appointment->ID,
					'data' => array(
						array(
							'name' => __( 'Name', 'appointments' ),
							'value' => $appointment->name,
						),
						array(
							'name' => __( 'Email', 'appointments' ),
							'value' => $appointment->email,
						),
						array(
							'name' => __( 'Phone', 'appointments' ),
							'value' => $appointment->phone,
						),
						array(
							'name' => __( 'Address', 'appointments' ),
							'value' => $appointment->address,
						),
						array(
							'name' => __( 'City', 'appointments' ),
							'value' => $appointment->city,
						),
						array(
							'name' => __( 'Note', 'appointments' ),
							'value' => $appointment->notes,
						),
						array(
							'name' => __( 'Create date', 'appointments' ),
							'value' => $appointment->created,
						),
						array(
							'name' => __( 'Start date', 'appointments' ),
							'value' => $appointment->start,
						),
						array(
							'name' => __( 'End date', 'appointments' ),
							'value' => $appointment->end,
						),
					),
				);
				/**
				 * Export single appointment row.
				 *
				 * @since 2.3.0
				 *
				 * @param array $item Export data for appointment.
				 * @param string $email Appointment email.
				 * @param object $appointment Single appointment data object.
				 */
				$export_items[] = apply_filters( 'appointments_gdpr_export', $item, $email, $appointment );
			}
		}
		$export = array(
			'data' => $export_items,
			'done' => true,
		);
		return $export;
	}

	/**
	 * Register plugin eraser.
	 *
	 * @since 2.3.0
	 */
	public function register_plugin_eraser( $erasers ) {
		$erasers['appointments'] = array(
			'eraser_friendly_name' => $this->get_plugin_friendly_name(),
			'callback'             => array( $this, 'plugin_eraser' ),
		);
		return $erasers;
	}

	/**
	 * Erase personal data.
	 *
	 * @since 2.3.0
	 */
	public function plugin_eraser( $email, $page = 1 ) {
		global $wpdb;
		$days = intval( appointments_get_option( 'gdpr_number_of_days_user_erease' ) );
		if ( 1 > $days ) {
			return;
		}
		$messages = array();
		$table = appointments_get_table( 'appointments' );
		/**
		 * count days
		 */
		$days = sprintf( '-%d day', $days );
		$date = date( 'Y-m-d H:i', strtotime( $days ) );
		/**
		 * delete
		 */
		$sql = $wpdb->prepare( "select ID from {$table} where email = %s and start < %s", $email, $date );
		$results = $wpdb->get_col( $sql );
		$count = 0;
		foreach ( $results as $id ) {
			$result = appointments_delete_appointment( $id );
			if ( $result ) {
				$count++;
			}
		}
		if ( 0 < $count ) {
			$messages[] = sprintf( _n( 'We deleted %d appointment.', 'We deleted %d appointments', $count, 'appointments' ), $count );
		} else {
			$messages[] = __( 'We do not deleted any appointments.', 'appointments' );
		}
		/**
		 * check how much left
		 */
		$sql = $wpdb->prepare( "select count(ID) from {$table} where email = %s", $email );
		$items_retained = $wpdb->get_var( $sql );
		if ( 0 < $items_retained ) {
			$messages[] = sprintf( _n( 'We do not deleted %d appointment.', 'We do not deleted %d appointments', $count, 'appointments' ), $count );
		}
		/**
		 * return
		 */
		return array(
			'items_removed' => $count,
			'items_retained' => $items_retained,
			'messages' => $messages,
			'done' => true,
		);
	}

	/**
	 * Try to delete older appointments.
	 *
	 * @since 2.3.0
	 */
	public function gdpr_check_and_delete() {
		global $wpdb;
		$days = $this->get_number_of_days();
		if ( 1 > $days ) {
			return;
		}
		$days = sprintf( '-%d day', $days );
		$table = appointments_get_table( 'appointments' );
		$date = date( 'Y-m-d H:i', strtotime( $days ) );
		$sql = $wpdb->prepare( "select ID from {$table} where start < %s", $date );
		$results = $wpdb->get_col( $sql );
		foreach ( $results as $id ) {
			appointments_delete_appointment( $id );
		}
	}

	/**
	 * Add Appointments Policy to "Privace Policy" page during creation.
	 *
	 * @since 2.3.0
	 */
	public function add_policy( $content ) {

		ob_start();
		?>
		<div class="wp-suggested-text">
			<h2><?php esc_html_e( 'What personal data do we collect and why?', 'appointments' ); ?></h2>
			<p class="privacy-policy-tutorial">
				<?php esc_html_e( 'When visitors book an appointment on your site we collect the data shown in the appointments form to allow future contact with a client. If you use the Additional Fields addon make sure to update your privacy policy to reflect any additional information you have requested from your clients.', 'appointments' ); ?>
			</p>
			<p>
				<strong class="privacy-policy-tutorial"><?php esc_html_e( 'Suggested text: ', 'appointments' ); ?></strong>
				<?php esc_html_e( 'We collect your Name, E-Mail and Phone Number for your Appointments. These data are not shared and are deleted upon request.', 'appointments' );//wpcs : xss ok ?>
			</p>
			<h2><?php esc_html_e( 'How long we retain your data', 'appointments' ); ?></h2>
			<p class="privacy-policy-tutorial">
				<?php esc_html_e( 'Until there is an Erasure request or depending on the Appointments+ GDPR settings that you have set.', 'appointments' ); ?>
			</p>
			<p>
				<strong class="privacy-policy-tutorial"><?php esc_html_e( 'Suggested text: ', 'appointments' ); ?></strong>
				<?php esc_html_e( 'Your data will be automatically erased after 20 days.', 'appointments' );//wpcs : xss ok ?>
			</p>
			<h2><?php esc_html_e( 'Third Parties', 'appointments' ); ?></h2>
			<p class="privacy-policy-tutorial">
				<?php esc_html_e( 'If you choose to you can use Google Calendar to sync your appointments.', 'appointments' ); ?>
			</p>
			<p>
				<strong class="privacy-policy-tutorial"><?php esc_html_e( 'Suggested text: ', 'appointments' ); ?></strong>
				<?php esc_html_e( 'We are using Google Calendar to sync our appointments. Your Name, E-Mail, Phone will be synced with our private Google Calendar account. ', 'appointments' ); ?>
			</p>
		</div>
		<?php
		$content = ob_get_clean();

		wp_add_privacy_policy_content(
			'Appointments',
			wp_kses_post( wpautop( $content, false ) )
		);
	}
}

