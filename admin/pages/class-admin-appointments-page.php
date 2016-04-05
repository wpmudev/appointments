<?php

class Appointments_Admin_Appointments_Page {

	public $page_id = '';

	public function __construct() {
		$this->page_id = add_menu_page('Appointments', __('Appointments','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_APPOINTMENTS),  'appointments', array(&$this,'appointment_list'),'dashicons-clock');
		add_action( "admin_print_scripts-" . $this->page_id, array( &$this, 'admin_scripts' ) );
	}

	public function admin_scripts() {
		global $appointments;
		$appointments->admin->admin_scripts();
	}

	/**
	 *	Creates the list for Appointments admin page
	 */
	function appointment_list() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : false;
		$app_id = isset( $_GET['app'] ) ? absint( $_GET['app'] ) : 0;
		if ( appointments_get_appointment( $app_id ) ) {
			$this->edit_appointment_view( $app_id );
		}
		else {
			App_Template::admin_appointments_list();
		}
	}

	function edit_appointment_view( $app_id ) {
		$app = appointments_get_appointment( $app_id );
		?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Edit Appointment', 'appointments' ); ?></h1>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-1">
						<div id="postbox-container-1">
							<div class="postbox ">
								<h2 class="hndle"><span><?php _e( 'Appointment Details', 'appointments' ); ?></span></h2>
								<div class="inside">
									<?php var_dump( $app ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}

}