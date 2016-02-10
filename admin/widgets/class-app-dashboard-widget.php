<?php

class Appointments_Admin_Dashboard_Widget {

	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
	}

	public function register() {
		if ( App_Roles::current_user_can( 'manage_options', App_Roles::CTX_DASHBOARD ) ) {
			wp_add_dashboard_widget( 'appointments', __( 'Appointments', 'appointments' ), array( $this, 'render' ) );
		}
	}

	public function render() {
		$app_counts = appointments_count_appointments();

		$num_active = $app_counts['paid'] + $app_counts['confirmed'];
		$num_pending = $app_counts['pending'];
		?>
		<div class="main">
			<ul>
				<li class="post-count">
					<span class="dashicons dashicons-clock"></span>
					<a href="<?php echo esc_url( self_admin_url( 'admin.php?page=appointments' ) ); ?>">
						<?php printf( _n( '%d Active Appointment', '%d Active Appointments', $num_active ), $num_active ); ?>
					</a>
				</li>
				<li class="post-count">
					<span class="dashicons dashicons-backup"></span>
					<a href="<?php echo esc_url( self_admin_url( 'admin.php?page=appointments' ) ); ?>">
						<?php printf( _n( '%d Pending Appointment', '%d Pending Appointments', $num_pending ), $num_pending ); ?>
					</a>
				</li>
			</ul>
		</div>
		<?php
	}
}