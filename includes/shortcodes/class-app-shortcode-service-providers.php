<?php
/**
 * Service providers shortcode list/dropdown.
 */
class App_Shortcode_ServiceProviders extends App_Shortcode {
	public function __construct () {
		$this->_defaults = array(
			'select' => array(
				'value' => __('Please choose a service provider:', 'appointments'),
				'help' => __('Text above the select menu. Default: "Please select a service"', 'appointments'),
				'example' => __('Please choose a service provider:', 'appointments'),
			),
			'empty_option' => array(
				'value' => __('No preference', 'appointments'),
				'help' => __('Empty option label for the selection', 'appointments'),
				'example' => __('Please, select', 'appointments'),
			),
			'show' => array(
				'value' => __('Show available times', 'appointments'),
				'help' => __('Button text to show the results for the selected. Default: "Show available times"', 'appointments'),
				'example' => __('Show available times', 'appointments'),
			),
			'description' => array(
				'value' => 'excerpt',
				'help' => __('Selects which part of the bio page will be displayed under the dropdown menu when a service provider is selected . Selectable values are "none", "excerpt", "content". Default: "excerpt"', 'appointments'),
				'allowed_values' => array('none', 'excerpt', 'content',),
				'example' => 'content',
			),
			'thumb_size' => array(
				'value' => '96,96',
				'help' => __('Inserts the post thumbnail if page has a featured image. Selectable values are "none", "thumbnail", "medium", "full" or a 2 numbers separated by comma representing width and height in pixels, e.g. 32,32. Default: "96,96"', 'appointments'),
				'example' => 'thumbnail',
			),
			'thumb_class' => array(
				'value' => 'alignleft',
				'help' => __('css class that will be applied to the thumbnail. Default: "alignleft"', 'appointments'),
				'example' => 'my-class',
			),
			'autorefresh' => array(
				'value' => 0,
				'help' => __('If set as 1, Show button will not be displayed and page will be automatically refreshed as client changes selection. Note: Client cannot browse through the selections and thus check descriptions on the fly (without the page is refreshed). Default: "0" (disabled)', 'appointments'),
				'example' => '1',
			),
			'order_by' => array(
				'value' => 'ID',
				'help' => __('Sort order of the service providers. Possible values: ID, name. Optionally DESC (descending) can be used, e.g. "name DESC" will reverse the order. Default: "ID"', 'appointments'),
				'example' => 'ID',
			),
			'service' => array(
				'value' => 0,
				'help' => __('In some cases, you may want to force to display providers who give only a certain service. In that case enter service ID here. Default: "0" (list is determined by services dropdown). Note: Multiple selections are not allowed.', 'appointments'),
				'example' => '12',
			),
			'require_service' => array(
				'value' => 0,
				'help' => __('Do not show service provider selection at all until the service has been previously selected.', 'appointments'),
				'example' => '1',
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
