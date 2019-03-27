<?php


/**
 * Services dropdown list shortcode.
 */
class App_Shortcode_Services extends App_Shortcode {

	/**
	 * Sort BY
	 *
	 * @since 2.4.3
	 */
	private $sort_by = 'ID';

	public function __construct() {
		$this->name = __( 'Services', 'appointments' );
	}

	public function get_defaults() {
		$_workers = appointments_get_workers();
		$workers = array(
			array( 'text' => __( 'Any provider', 'appointments' ), 'value' => 0 ),
		);
		foreach ( $_workers as $worker ) {
			/** @var Appointments_Worker $worker */
			$workers[] = array( 'text' => $worker->get_name(), 'value' => $worker->ID );
		}

		return array(
			'select' => array(
				'type' => 'text',
				'name' => __( 'Title', 'appointments' ),
				'value' => __( 'Please select a service:', 'appointments' ),
				'help' => __( 'Text above the select menu. Default: "Please select a service".', 'appointments' ),
			),
			'show' => array(
				'type' => 'text',
				'name' => __( 'Show Button Text', 'appointments' ),
				'value' => __( 'Show available times', 'appointments' ),
				'help' => __( 'Button text to show the results for the selected. Default: "Show available times".', 'appointments' ),
				'example' => __( 'Show available times', 'appointments' ),
			),
			'description' => array(
				'type' => 'select',
				'name' => __( 'Description', 'appointments' ),
				'value' => 'excerpt',
				'help' => __( 'Selects which part of the description page will be displayed under the dropdown menu when a service is selected . Selectable values are "none", "excerpt", "content". Default: "excerpt".', 'appointments' ),
				'options' => array(
					array( 'text' => 'Excerpt', 'value' => 'excerpt' ),
					array( 'text' => 'None', 'value' => '' ),
					array( 'text' => 'Content', 'value' => 'content' ),
				),
			),
			'thumb_size' => array(
				'type' => 'text',
				'name' => __( 'Thumbnail Size', 'appointments' ),
				'value' => '96,96',
				'help' => __( 'Inserts the post thumbnail if page has a featured image. Selectable values are "none", "thumbnail", "medium", "full" or a 2 numbers separated by comma representing width and height in pixels, e.g. 32,32. Default: "96,96".', 'appointments' ),
			),
			'thumb_class' => array(
				'type' => 'text',
				'name' => __( 'Thumbnail Class', 'appointments' ),
				'value' => 'alignleft',
				'help' => __( 'CSS class that will be applied to the thumbnail. Default: "alignleft".', 'appointments' ),
			),
			'autorefresh' => array(
				'type' => 'checkbox',
				'name' => __( 'Autorefresh', 'appointments' ),
				'value' => 0,
				'help' => __( 'If checked, Show button will not be displayed and page will be automatically refreshed as client changes selection. Note: Client cannot browse through the selections and thus check descriptions on the fly (without the page is refreshed). Default: disabled.', 'appointments' ),
			),
			'order_by' => array(
				'type' => 'select',
				'name' => __( 'Order By', 'appointments' ),
				'value' => 'ID',
				'options' => array(
					array( 'text' => 'ID', 'value' => 'ID' ),
					array( 'text' => 'ID DESC', 'value' => 'ID ASC' ),
					array( 'text' => 'name', 'value' => 'name' ),
					array( 'text' => 'name DESC', 'value' => 'name DESC' ),
					array( 'text' => 'duration', 'value' => 'duration' ),
					array( 'text' => 'duration DESC', 'value' => 'duration DESC' ),
				),
				'help' => __( 'Sort order of the services. Possible values: ID, name, duration, price. Optionally DESC (descending) can be used, e.g. "name DESC" will reverse the order. Default: "ID".', 'appointments' ),
			),
			'worker' => array(
				'type' => 'select',
				'name' => __( 'Provider', 'appointments' ),
				'value' => 0,
				'options' => $workers,
				'help' => __( 'In some cases, you may want to display services which are given only by a certain provider. In that case enter provider ID here. Default: "0" (all defined services). Note: Multiple selections are not allowed.', 'appointments' ),
			),
			'ajax' => array(
				'type' => 'checkbox',
				'name' => __( 'AJAX', 'appointments' ),
				'value' => 0,
				'help' => __( 'If checked, Services thumbnails and descriptions will be loaded by AJAX. Recommended for sites with many services.', 'appointments' ),
			),
			'_noscript' => array( 'value' => 0 ),

		);
	}

	public function get_usage_info() {
		return __( 'Creates a dropdown menu of available services.', 'appointments' );
	}

	public function process_shortcode( $args = array(), $content = '' ) {
		global $appointments;

		$args = wp_parse_args( $args, $this->_defaults_to_args() );
		extract( $args );

		if ( ! trim( $args['order_by'] ) ) {
			$args['order_by'] = 'ID';
		}

		if ( $args['worker'] && appointments_get_worker( $args['worker'] ) ) {
			$services = appointments_get_worker_services( $args['worker'] );

			// Re-sort worker services
			if ( ! empty( $services ) && ! empty( $args['order_by'] ) && 'ID' !== $args['order_by'] ) {
				$services = $this->_reorder_services( $services, $args['order_by'] );
			}
		} else {
			$services = appointments_get_services( array( 'orderby' => $args['order_by'] ) );
		}

		$services = apply_filters( 'app_services', $services );

		// If there are no workers do nothing
		if ( ! $services || empty( $services ) ) {
			return ''; }

		$selected_service = 0;
		if ( isset( $_REQUEST['app_service_id'] ) && appointments_get_service( $_REQUEST['app_service_id'] ) ) {
			$selected_service = absint( $_REQUEST['app_service_id'] );
		} elseif ( $args['worker'] && appointments_get_worker( $args['worker'] ) ) {
			$selected_service = $services[0]->ID;
		}

		$selected_service = apply_filters( 'appointments_services_shortcode_selected_service', $selected_service, $args, $services );

		ob_start();
		?>
		<div class="app_services">
			<div class="app_services_dropdown">
				<div class="app_services_dropdown_title" id="app_services_dropdown_title">
					<?php echo $args['select']; ?>
				</div>
				<div class="app_services_dropdown_select">
					<select id="app_select_services" name="app_select_services" class="app_select_services">
<?php
foreach ( $services as $service ) {
	if ( 0 === $selected_service ) {
		$selected_service = $service->ID;
	}
?>
					<option value="<?php echo $service->ID; ?>" <?php selected( $service->ID, $selected_service ); ?>><?php echo stripslashes( $service->name ); ?></option>
				<?php } ?>
					</select>
					<input type="button" class="app_services_button" value="<?php echo esc_attr( $args['show'] ); ?>">
				</div>
			</div>

			<div class="app_service_excerpts">
				<?php if ( $args['autorefresh'] ) :  // Only display the selected service ?>
					<?php
						$service = appointments_get_service( $selected_service );
					if ( $service ) {
						$page = apply_filters( 'app_service_page', $service->page, $service->ID );
						?>
							<div class="app_service_excerpt" id="app_service_excerpt_<?php echo $service->ID; ?>">
								<?php
								$service_description = '';
								switch ( $args['description'] ) {
									case 'none': {
										break;
									}
									case 'content': {
										$service_description = $appointments->get_content( $page, $args['thumb_size'], $args['thumb_class'], $service->ID );
										break;
									}
									default: {
										$service_description = $appointments->get_excerpt( $page, $args['thumb_size'], $args['thumb_class'], $service->ID );
										break;
									}
								}
								echo apply_filters( 'app-services-service_description', $service_description, $service, $args['description'] );
								?>
								</div>
							<?php
					}

					?>
				<?php else : ?>
					<?php foreach ( $services as $service ) :  ?>
						<?php $page = apply_filters( 'app_service_page', $service->page, $service->ID ); ?>
						<div <?php echo $service->ID != $selected_service ? 'style="display:none"' : ''; ?> class="app_service_excerpt" id="app_service_excerpt_<?php echo $service->ID; ?>">
							<?php
							$service_description = '';
							switch ( $args['description'] ) {
								case 'none': {
									break;
								}
								case 'content': {
									$service_description = $appointments->get_content( $page, $args['thumb_size'], $args['thumb_class'], $service->ID, absint( $args['ajax'] ) );
									break;
								}
								default: {
									$service_description = $appointments->get_excerpt( $page, $args['thumb_size'], $args['thumb_class'], $service->ID, absint( $args['ajax'] ) );
									break;
								}
							}
							echo apply_filters( 'app-services-service_description', $service_description, $service, $args['description'] );
							?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

			</div>
		</div>
		<?php

		$s = ob_get_clean();

		$wcalendar = isset( $_GET['wcalendar'] ) && (int) $_GET['wcalendar']
			? (int) $_GET['wcalendar']
			: false
		;

		// First remove these parameters and add them again to make wcalendar appear before js variable
		$href = add_query_arg( array( 'wcalendar' => false, 'app_provider_id' => false, 'app_service_id' => false ) );
		$href = add_query_arg( array( 'wcalendar' => $wcalendar, 'app_service_id' => '__selected_service__' ), $href );
		if ( isset( $_GET['app_provider_id'] ) ) {
			$href = add_query_arg( 'app_provider_id', $_GET['app_provider_id'], $href );
		}

		$href = apply_filters( 'app_service_href', $href );
		$href = $this->_js_esc_url( $href ) . '#app_services_dropdown_title';

		if ( ! $args['_noscript'] ) {
			wp_enqueue_script( 'app-shortcode-services', appointments_plugin_url() . 'includes/shortcodes/js/app-services.js', array( 'jquery' ) );

			$ajax_url = admin_url( 'admin-ajax.php' );
			if ( ! is_ssl() && force_ssl_admin() ) {
				$ajax_url = admin_url( 'admin-ajax.php', 'http' );
			}

			$i10n = array(
				'size' => $args['thumb_size'],
				'worker' => $args['worker'],
				'ajaxurl' => $ajax_url,
				'thumbclass' => $args['thumb_class'],
				'autorefresh' => $args['autorefresh'],
				'ajax' => $args['ajax'],
				'first_service_id' => appointments_get_services_min_id(),
				'reload_url' => $href,
			);
			wp_localize_script( 'app-shortcode-services', 'appointmentsStrings', $i10n );
		}

		return $s;
	}

	/**
	 * Escape the URL, but convert back search query entities (i.e. ampersands)
	 *
	 * @param string $raw Raw URL to parse
	 *
	 * @return string Usable URL
	 */
	private function _js_esc_url( $raw = '' ) {
		$url = esc_url( $raw );
		$parts = explode( '?', $url );

		if ( empty( $parts[1] ) ) { return $url; }
		if ( false === strpos( $parts[1], '#038;' ) && false === strpos( $parts[1], '&amp;' ) ) { return $url; }

		$parts[1] = preg_replace( '/&(#038|amp);/', '&', $parts[1] );

		return join( '?', $parts );
	}

	/**
	 * Sort the services when we can't do so via SQL
	 */
	private function _reorder_services( $services, $order ) {
		if ( empty( $services ) ) { return $services; }
		list($by,$direction) = explode( ' ', trim( $order ), 2 );
		$by = trim( $by ) ? trim( $by ) : 'ID';
		$by = in_array( $by, array( 'ID', 'name', 'capacity', 'duration', 'price', 'page' ) )
			? $by
			: 'ID'
		;
		$direction = trim( $direction ) ? strtoupper( trim( $direction ) ) : 'ASC';
		$direction = in_array( $direction, array( 'ASC', 'DESC' ) )
			? $direction
			: 'ASC'
			;
		$this->sort_by = $by;
		if ( 'ASC' === $direction ) {
			usort( $services, array( $this, 'sort_desc' ) );
		} else {
			usort( $services, array( $this, 'sort_asc' ) );
		}
		return $services;
	}

	/**
	 * Sort ASC (usort helper)
	 *
	 * @since 2.4.3
	 */
	private function sort_asc( $a, $b ) {
		return strnatcasecmp( $a->{$this->sort_by}, $b->{$this->sort_by} );
	}

	/**
	 * Sort DESC (usort helper)
	 *
	 * @since 2.4.3
	 */
	private function sort_desc( $a, $b ) {
		return strnatcasecmp( $b->{$this->sort_by}, $a->{$this->sort_by} );
	}
}
