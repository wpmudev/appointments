<?php
/**
 * Service providers shortcode list/dropdown.
 */
class App_Shortcode_ServiceProviders extends App_Shortcode {
	public function __construct () {
		$this->name = __( 'Providers', 'appointments' );
	}

	public function get_defaults() {
		$_services = appointments_get_services();
		$services = array(
			array( 'text' => __( 'Any service', 'appointments' ), 'value' => 0 )
		);
		foreach ( $_services as $service ) {
			/** @var Appointments_Service $service */
			$services[] = array( 'text' => $service->name, 'value' => $service->ID );
		}

		return array(
			'select' => array(
				'type' => 'text',
				'name' => __( 'Title', 'appointments' ),
				'value' => __('Please choose a service provider:', 'appointments'),
				'help' => __('Text above the select menu. Default: "Please select a service"', 'appointments'),
			),
			'empty_option' => array(
				'type' => 'text',
				'name' => __( 'Empty option text', 'appointments' ),
				'value' => __('No preference', 'appointments'),
				'help' => __('Empty option label for the selection', 'appointments'),
			),
			'show' => array(
				'type' => 'text',
				'name' => __( 'Show button Text', 'appointments' ),
				'value' => __('Show available times', 'appointments'),
				'help' => __('Button text to show the results for the selected. Default: "Show available times"', 'appointments'),
			),
			'description' => array(
				'type' => 'select',
				'name' => __( 'Description', 'appointments' ),
				'value' => 'excerpt',
				'help' => __('Selects which part of the bio page will be displayed under the dropdown menu when a service provider is selected . Selectable values are "none", "excerpt", "content". Default: "excerpt"', 'appointments'),
				'options' => array(
					array( 'text' => 'Excerpt', 'value' => 'excerpt' ),
					array( 'text' => 'None', 'value' => ''),
					array( 'text' => 'Content', 'value' => 'content' ),
				)
			),
			'thumb_size' => array(
				'type' => 'text',
				'name' => __( 'Thumbnail Size', 'appointments' ),
				'value' => '96,96',
				'help' => __('Inserts the post thumbnail if page has a featured image. Selectable values are "none", "thumbnail", "medium", "full" or a 2 numbers separated by comma representing width and height in pixels, e.g. 32,32. Default: "96,96"', 'appointments'),
			),
			'thumb_class' => array(
				'type' => 'text',
				'name' => __( 'Thumbnail Class', 'appointments' ),
				'value' => 'alignleft',
				'help' => __('CSS class that will be applied to the thumbnail. Default: "alignleft"', 'appointments'),
			),
			'autorefresh' => array(
				'type' => 'checkbox',
				'name' => __( 'Autorefresh', 'appointments' ),
				'value' => 0,
				'help' => __('If checked, Show button will not be displayed and page will be automatically refreshed as client changes selection. Note: Client cannot browse through the selections and thus check descriptions on the fly (without the page is refreshed). Default: disabled', 'appointments'),
			),
			'order_by' => array(
				'type' => 'select',
				'name' => __( 'Order By', 'appointments' ),
				'value' => 'ID',
				'options' => array(
					array( 'text' => 'ID', 'value' => 'ID' ),
					array( 'text' => 'ID DESC', 'value' => 'ID ASC' ),
					array( 'text' => 'name', 'value' => 'name'),
					array( 'text' => 'name DESC', 'value' => 'name DESC'),
				),
				'help' => __('Sort order of the service providers. Possible values: ID, name. Optionally DESC (descending) can be used, e.g. "name DESC" will reverse the order. Default: "ID"', 'appointments'),
			),
			'service' => array(
				'type' => 'select',
				'name' => __( 'Service', 'appointments' ),
				'value' => 0,
				'options' => $services,
				'help' => __('In some cases, you may want to force to display providers who give only a certain service. In that case enter service ID here.', 'appointments'),
			),
			'require_service' => array(
				'type' => 'checkbox',
				'name' => __( 'Require Service', 'appointments' ),
				'value' => 0,
				'help' => __('Checking this argument means a timetable will not be rendered unless a service has been previously selected.', 'appointments'),
			),
			'_noscript' => array('value' => 0),

		);
	}

	public function get_usage_info () {
		return __('Creates a dropdown menu of available service providers.', 'appointments');
	}

	public function process_shortcode ($args=array(), $content='') {
		extract(wp_parse_args($args, $this->_defaults_to_args()));

		if (!empty($require_service) && empty($service) && empty($_REQUEST['app_service_id'])) return $content;

		global $wpdb, $appointments;
		$appointments->get_lsw();

		if ( !trim( $order_by ) )
			$order_by = 'ID';


		if ( !$service ) {
			if ( 0 == $appointments->service )
				$workers = appointments_get_workers( array( 'orderby' => $order_by ) );
			else
				$workers = appointments_get_workers_by_service( $appointments->service, $order_by ); // Select only providers that can give this service
		}
		else
			$workers = appointments_get_workers_by_service( $service, $order_by );

		$workers = apply_filters( 'app_workers', $workers );

		// If there are no workers do nothing
		if ( !$workers || empty( $workers) )
			return;

		$script ='';
		$s = $e = '';

		$s .= '<div class="app_workers">';
		$s .= '<div class="app_workers_dropdown">';
		$s .= '<div class="app_workers_dropdown_title">';
		$s .= $select;
		$s .= '</div>';
		$s .= '<div class="app_workers_dropdown_select">';
		$s .= '<select name="app_select_workers" class="app_select_workers">';
		// Do not show "Anyone" if there is only ONE provider
		if ( 1 != count( $workers ) )
			$s .= '<option value="0">'. $empty_option . '</option>';

		foreach ( $workers as $worker ) {
			$worker_description = '';
			if ( $appointments->worker == $worker->ID || 1 == count( $workers ) ) {
				$d = '';
				$sel = ' selected="selected"';
			}
			else {
				$d = ' style="display:none"';
				$sel = '';
			}
			$s .= '<option value="'.$worker->ID.'"'.$sel.'>'. appointments_get_worker_name( $worker->ID )  . '</option>';
			// Include excerpts
			$e .= '<div '.$d.' class="app_worker_excerpt" id="app_worker_excerpt_'.$worker->ID.'" >';
			// Let addons modify worker bio page
			$page = apply_filters( 'app_worker_page', $worker->page, $worker->ID );
			switch ( $description ) {
				case 'none'		:		break;
				case 'excerpt'	:		$worker_description .= $appointments->get_excerpt( $page, $thumb_size, $thumb_class, $worker->ID ); break;
				case 'content'	:		$worker_description .= $appointments->get_content( $page, $thumb_size, $thumb_class, $worker->ID ); break;
				default			:		$worker_description .= $appointments->get_excerpt( $page, $thumb_size, $thumb_class, $worker->ID ); break;
			}
			$e .= apply_filters('app-workers-worker_description', $worker_description, $worker, $description) . '</div>';
		}

		$s .= '</select>';
		$s .= '<input type="button" class="app_workers_button" value="'.$show.'">';
		$s .= '</div>';
		$s .= '</div>';
		$s .= '<div class="app_worker_excerpts">';
		$s .= $e;
		$s .= '</div>';

		$s .= '</div>';
		if ( isset( $_GET['wcalendar'] ) && (int)$_GET['wcalendar'] )
			$wcalendar = (int)$_GET['wcalendar'];
		else
			$wcalendar = false;
		// First remove these parameters and add them again to make wcalendar appear before js variable
		$href = add_query_arg( array( "wcalendar"=>false, "app_provider_id" =>false ) );
		$href = apply_filters( 'app_worker_href', add_query_arg( array( "wcalendar"=>$wcalendar, "app_provider_id" => "__selected_worker__" ), $href ) );

		if ( $autorefresh ) {
			$script .= "$('.app_workers_button').hide();";
		}
		$script .= "$('.app_select_workers').change(function(){";
		$script .= "var selected_worker=$('.app_select_workers option:selected').val();";
		$script .= "if (typeof selected_worker=='undefined' || selected_worker==null){";
		$script .= "selected_worker=0;";
		$script .= "}";
		$script .= "$('.app_worker_excerpt').hide();";
		$script .= "$('#app_worker_excerpt_'+selected_worker).show();";
		if ( $autorefresh ) {
			$script .= "var redirection_url='" . $href . "'.replace(/__selected_worker__/, selected_worker) + (!!parseInt(selected_worker, 10) ? '#app_worker_excerpt_'+selected_worker : '');";
			$script .= "window.location.href=redirection_url;";
		}
		$script .= "});";

		$script .= "$('.app_workers_button').click(function(){";
		$script .= "var selected_worker=$('.app_select_workers option:selected').val();";
		$script .= "var redirection_url='" . $href . "'.replace(/__selected_worker__/, selected_worker) + (!!parseInt(selected_worker, 10) ? '#app_worker_excerpt_'+selected_worker : '');";
		$script .= "window.location.href=redirection_url;";
		$script .= "});";

		if (!$_noscript) $appointments->add2footer( $script );

		return $s;
	}
}
