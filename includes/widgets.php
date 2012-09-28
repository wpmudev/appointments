<?php
/**
 * Widgets for Appointments 1.0
 */	
class Appointments_Widget_Helper extends WP_Widget {
	var $default_instance = array();

	function parse_instance( $instance ) {
		return wp_parse_args( $instance, $this->default_instance );
	}

	function widget( $args, $instance ) {
		$instance = $this->parse_instance( $instance );

		extract($args);
		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;

		$this->content( $instance );

		echo $after_widget;
	}

	function title_field( $title ) {
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'appointments' ); ?></label>
			<input type="text" class="widefat" name="<?php echo $this->get_field_name('title')?>" value="<?php echo $title?>" />
			
		</p>
<?php
	}
}

class Appointments_Widget_Services extends Appointments_Widget_Helper {

	var $default_instance = array(
		'title' => '',
		'number' => 5
	);

	function Appointments_Widget_Services() {
		$widget_ops = array( 'description' => __( 'List of services and links to their description pages', 'appointments') );
		$this->WP_Widget( 'appointments_services', __( 'Appointments+ Services', 'appointments' ), $widget_ops );
	}

	function content( $instance ) {

		extract( $instance );

		global $wpdb;
		$results = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "app_services" . " WHERE page >0 LIMIT ".$number." ");
		
		if ( $results ) {
			echo '<ul>';
			foreach ( $results as $result ) {
				echo '<li>';
				
				echo '<a href="'.get_permalink($result->page).'" >'. stripslashes( $result->name ) . '</a>';
				
				echo '</li>';
			}
			echo '</ul>';
		}
	}

	function form( $instance ) {
		$instance = $this->parse_instance( $instance );
		$this->title_field( $instance['title'] );
?>

		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e( 'Number of services to show:', 'appointments' ); ?></label>
			
			<input type="text" size="2" name="<?php echo $this->get_field_name('number')?>" value="<?php echo $instance['number']?>" />
			
		</p>
<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = $this->parse_instance( $new_instance );
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		return $instance;
	}
}

class Appointments_Widget_Service_Providers extends Appointments_Widget_Helper {

	var $default_instance = array(
		'title' => '',
		'number' => 5
	);

	function Appointments_Widget_Service_Providers() {
		$widget_ops = array( 'description' => __( 'List of service providers and links to their description pages', 'appointments') );
		$this->WP_Widget( 'appointments_service_providers', __( 'Appointments+ Service Providers', 'appointments' ), $widget_ops );
	}

	function content( $instance ) {

		extract( $instance );

		global $wpdb;
		$results = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "app_workers" . " WHERE page >0 LIMIT ".$number." ");
		
		if ( $results ) {
			echo '<ul>';
			foreach ( $results as $result ) {
				$worker_data = get_userdata( $result->ID );
				echo '<li>';
				
				echo '<a href="'.get_permalink($result->page).'" >'. $worker_data->user_login . '</a>';
				
				echo '</li>';
			}
			echo '</ul>';
		}
	}

	function form( $instance ) {
		$instance = $this->parse_instance( $instance );
		$this->title_field( $instance['title'] );
?>

		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e( 'Number of service providers to show:', 'appointments' ); ?></label>
			
			<input type="text" size="2" name="<?php echo $this->get_field_name('number')?>" value="<?php echo $instance['number']?>" />
			
		</p>
<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = $this->parse_instance( $new_instance );
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		return $instance;
	}
}

class Appointments_Widget_Monthly_Calendar extends Appointments_Widget_Helper {

	var $default_instance = array(
		'title' 	=> '',
		'add' 		=> 0,
		'page_id'	=> 0
	);

	function Appointments_Widget_Monthly_Calendar() {
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ) );
		add_action( 'wp_print_styles', array( &$this, 'wp_print_styles' ) );
		add_action( 'wp_footer', array( &$this, 'wp_footer' ) );
		$widget_ops = array( 'description' => __( 'A montly calendar that redirects user to the selected appointment page when a free day is clicked', 'appointments') );
		$this->WP_Widget( 'appointments_monthly_calendar', __( 'Appointments+ Monthly Calendar', 'appointments' ), $widget_ops );
	}
	
	function wp_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
	}
	
	function wp_print_styles() {
		global $appointments;
		if ( !current_theme_supports( 'appointments_style' ) ) {
			wp_enqueue_style( "appointments", $appointments->plugin_url. "/css/front.css", array(), $appointments->version );
			add_action( 'wp_head', array( &$this, 'wp_head' ) );
		}
	}
	
	function wp_head() {
		global $appointments;
		$appointments->wp_head();
	}
	
	function wp_footer( ) {
		$settings = $this->get_settings();
		
		if ( isset( $settings[$this->number] ) )
			$instance = $settings[$this->number];
		else 
			$instance = null;
			
		if ( is_array( $instance ) ) {
			extract( $instance );
			
			$href = get_permalink( $instance["page_id"] );
			
			$script  = '';
			$script .= '<script type="text/javascript">';
			$script .= "jQuery(document).ready(function($) {";
			$script .= '$("table.app_monthly_calendar_widget td.free").click(function(){';
			$script .= 'var wcalendar = $(this).find(".appointments_select_time").val();';
			$script .= 'window.location.href="'.$href.'?wcalendar="+wcalendar;';
			$script .= '});';
			$script .= "});</script>";
			
			echo $script;
		}
	}

	function content( $instance ) {

		extract( $instance );

		add_action( 'app_calendar_widget_before_content', $add );
		
		echo do_shortcode('[app_monthly_schedule class="app_monthly_calendar_widget" widget="1" title="<h3>START</h3>" logged=" " notlogged=" " add="'.$add.'"]');
		
		add_action( 'app_calendar_widget_after_content', $add );
	}

	function form( $instance ) {
		$instance = $this->parse_instance( $instance );
		$this->title_field( $instance['title'] );
?>
		<p>
			<label for="<?php echo $this->get_field_id('page_id'); ?>"><?php _e( 'Appointment page:', 'appointments' ); ?></label>
			<?php
			wp_dropdown_pages( array( 'selected' => $instance['page_id'], 'name' => $this->get_field_name('page_id') ) );
			?>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('add'); ?>"><?php _e( 'Months to add to current month:', 'appointments' ); ?></label>
			
			<input type="text" size="2" name="<?php echo $this->get_field_name('add')?>" value="<?php echo $instance['add']?>" />
			
		</p>
<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = $this->parse_instance( $new_instance );
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['add'] = (int) $new_instance['add'];
		$instance['page_id'] = (int) $new_instance['page_id'];
		return $instance;
	}
}