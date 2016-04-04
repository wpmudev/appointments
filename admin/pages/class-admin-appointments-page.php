<?php

class Appointments_Admin_Appointments_Page {

	public $page_id = '';

	/**
	 * Filters
	 */
	private $type;
	private $service_id = '';
	private $worker_id = '';
	private $s = '';

	private $status_count = array();

	public function __construct() {
		$this->page_id = add_menu_page('Appointments', __('Appointments','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_APPOINTMENTS),  'appointments', array(&$this,'appointment_list'),'dashicons-clock');
		add_action( "admin_print_scripts-" . $this->page_id, array( &$this, 'admin_scripts' ) );
		add_action( 'load-' . $this->page_id, array( $this, 'init' ) );
		add_action( 'load-' . $this->page_id, array( $this, 'screen_options' ) );
	}

	public function admin_scripts() {
		global $appointments;
		$appointments->admin->admin_scripts();
	}

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'init_js' ), 50 );


		$args = array();

		// Search parameter
		if ( isset( $_GET['s'] ) ) {
			$s           = stripslashes( $_GET['s'] );
			$args['s']   = $s;
			$this->s = $s;
		}

		// Service ID filter
		if ( isset( $_GET['app_service_id'] ) && absint( $_GET['app_service_id'] ) ) {
			$service_id      = absint( $_GET['app_service_id'] );
			$args['service'] = $service_id;
			$this->service_id = $service_id;
		}

		// Worker ID filter
		if ( isset( $_GET['app_provider_id'] ) && absint( $_GET['app_provider_id'] ) ) {
			$worker_id = absint( $_GET['app_provider_id'] );
			if ( appointments_is_worker( $worker_id ) ) {
				$this->worker_id = $worker_id;
				$args['worker'] = $worker_id;
			}

		}

		// Set the status type
		$allowed_types = array_keys( $this->get_types() );
		if ( isset( $_GET['type'] ) && in_array( $_GET['type'], $allowed_types ) ) {
			$this->type = $_GET['type'];
		}
		else {
			$this->type = current( $allowed_types );
		}

		// COunt the statuses
		$this->status_count = appointments_count_appointments( $args );
	}


	public function get_types() {
		return apply_filters( 'appointments_list_types', array(
			'active' => __('Active appointments', 'appointments'),
			'pending' => __('Pending appointments', 'appointments'),
			'completed' => __('Completed appointments', 'appointments'),
			'reserved' => __('Reserved by GCal', 'appointments'),
			'removed' => __('Removed appointments', 'appointments')
		));
	}

	public function screen_options() {
		$options = appointments_get_options();
		if ( isset( $options['records_per_page'] ) && absint( $options['records_per_page'] ) ) {
			$default = absint( $options['records_per_page'] );
		}
		else {
			$default = 50;
		}

		$args = array(
			'label' => __( 'Appointments per page', 'appointments' ),
			'default' => $default,
			'option' => 'appointments_per_page'
		);

		add_screen_option( 'per_page', $args );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'app-admin-core', appointments_plugin_url() . 'admin/js/admin-core.js', array( 'jquery' ), true );
		wp_enqueue_script( 'app-admin-appointments', appointments_plugin_url() . 'admin/js/admin-appointments.js', array( 'jquery', 'app-admin-core' ), true );
	}

	/**
	 *	Creates the list for Appointments admin page
	 */
	function appointment_list() {
		include_once( appointments_plugin_dir() . 'admin/class-app-appointments-list-table.php' );

		$table = new Appointments_Admin_Appointments_List_Table(
			array(
				'singular' => __( 'Appointment', 'appointments' ),
				'plural' => __( 'Appointments', 'appointments' ),
				'screen' => $this->page_id
			)
		);

		$table->set_filter( 'service_id', $this->service_id );
		$table->set_filter( 'worker_id', $this->worker_id );
		$table->set_filter( 'type', $this->type );

		switch( $this->type ) {
			case 'active': {
				$table->set_filter( 'status', array( 'confirmed', 'paid' ) );
				break;
			}
			default: {
				$table->set_filter( 'status', $this->type );
			}
		}

		$table->prepare_items();

		?>
		<div class="wrap">
			<h1>
				<?php echo __('Appointments','appointments'); ?>
				<a href="javascript:void(0)" class="add-new-h2"><?php _e('Add New', 'appointments')?></a>
				<img class="add-new-waiting" style="display:none;" src="<?php echo admin_url('images/wpspin_light.gif')?>" alt="">
			</h1>

			<?php $this->status_filter(); ?>

			<form method="get" action="" class="search-form">
				<input type="hidden" name="page" value="appointments">
				<?php $table->search_box( __( 'Search Client', 'appointments' ), 'app' ); ?>
				<?php $table->display(); ?>

			</form>



		</div>

		<?php
	}



	public function status_filter() {
		?>
		<h2 class="screen-reader-text"><?php _e( 'Filter Appointments List', 'appointments' ); ?></h2>
		<ul class="subsubsub">
			<li><a href="<?php echo add_query_arg('type', 'active'); ?>" class="rbutton <?php if($this->type == 'active') echo 'current'; ?>"><?php  _e('Active', 'appointments'); ?></a> (<?php echo $this->status_count['paid'] + $this->status_count['confirmed']; ?>) | </li>
			<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($this->type == 'pending') echo 'current'; ?>"><?php  _e('Pending', 'appointments'); ?></a> (<?php echo $this->status_count['pending']; ?>) | </li>
			<li><a href="<?php echo add_query_arg('type', 'completed'); ?>" class="rbutton <?php if($this->type == 'completed') echo 'current'; ?>"><?php  _e('Completed', 'appointments'); ?></a> (<?php echo $this->status_count['completed']; ?>) | </li>
			<li><a href="<?php echo add_query_arg('type', 'reserved'); ?>" class="rbutton <?php if($this->type == 'reserved') echo 'current'; ?>"><?php  _e('Reserved by GCal', 'appointments'); ?></a> (<?php echo $this->status_count['reserved']; ?>) | </li>
			<li><a href="<?php echo add_query_arg('type', 'removed'); ?>" class="rbutton <?php if($this->type == 'removed') echo 'current'; ?>"><?php  _e('Removed', 'appointments'); ?></a> (<?php echo $this->status_count['removed']; ?>)</li>
			<li><a href="" class="dashicons dashicons-info" id="status-description-button" title="<?php _e('Click to toggle information about statuses', 'appointments')?>"></a></li>
		</ul>


		<div id="status-description">
			<br class="clear">
			<ul>
				<li><?php _e('<b>Completed:</b> Appointment became overdue after it is confirmed or paid', 'appointments') ?></li>
				<li><?php _e('<b>Removed:</b> Appointment was not paid for or was not confirmed manually in the allowed time', 'appointments') ?></li>
				<li><?php _e('<b>Reserved by GCal:</b> If you import appointments from Google Calender using Google Calendar API, that is, synchronize your calendar with Appointments+, events in your Google Calendar will be regarded as appointments and they will be shown here. These records cannot be edited here. Use your Google Calendar instead. They will be automatically updated in A+ too.', 'appointments') ?></li>
				<li><?php _e('If you require payment:', 'appointments') ?></li>
				<li><?php _e('<b>Active/Paid:</b> Paid and confirmed by Paypal', 'appointments') ?></li>
				<li><?php _e('<b>Pending:</b> Client applied for the appointment, but not yet paid.', 'appointments') ?></li>
			</ul>
			<ul>
				<li><?php _e('If you do not require payment:', 'appointments') ?></li>
				<li><?php _e('<b>Active/Confirmed:</b> Manually confirmed', 'appointments') ?></li>
				<li><?php _e('<b>Pending:</b> Client applied for the appointment, but it is not manually confirmed.', 'appointments') ?></li>
			</ul>
		</div>
		<?php
	}

	function init_js() {
		?>
		<script>
		 jQuery( document ).ready( function( $ ) {
			 $(document).appointments_admin();
		 }( jQuery ));
		</script>
		<style>
			#status-description {
				display:none;
			}
			#status-description-button {
				line-height: 1;
				padding: 0;
				margin-left:10px;
			}
		</style>
		<?php
	}



}