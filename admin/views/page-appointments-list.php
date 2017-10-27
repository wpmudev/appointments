<form method="post" >
	<h2 class="screen-reader-text"><?php _e( 'Appointments list', 'appointments' ); ?>></h2>
	<table class="wp-list-table widefat fixed stripped appointments">
		<thead>
			<tr>
				<?php foreach ( $columns as $key => $title ): ?>
					<?php if ( $key === 'cb' ): ?>
						<td id="cb" class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'appointments' ); ?></label>
							<input id="cb-select-all-1" type="checkbox">
						</td>
					<?php else: ?>
						<?php
							$class = '';
							$is_sortable = false;
							if ( array_key_exists( $key , $sortables ) ) {
								$is_sortable = true;
								$order = strtolower( $sortables[ $key ]['order'] );
								if ( $sortables[ $key ]['sorting'] ) {
									$class .= 'sorted ' . $order;
								}
								else {
									$class .= 'sortable ' . $order;
								}

								$sort_url = add_query_arg( array(
									'orderby' => $sortables[ $key ]['field'],
									'order' => $order == 'desc' ? 'asc' : 'desc'
								) );
							}
						?>
						<th scope="col" class="manage-column column-<?php echo esc_attr($key); ?> app-column-<?php echo esc_attr($key); ?> <?php echo $class; ?>" id="<?php echo esc_attr($key); ?>">
							<?php if ( $is_sortable ): ?>
								<a href="<?php echo esc_url( $sort_url ); ?>">
									<span><?php echo $title; ?></span>
									<span class="sorting-indicator"></span>
								</a>
							<?php else: ?>
								<?php echo $title; ?>
							<?php endif; ?>
						</th>
					<?php endif; ?>

				<?php endforeach; ?>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<?php foreach ( $columns as $key => $title ): ?>
					<?php $primary = ''; ?>
					<?php if ( $key === 'cb' ): ?>
						<td id="cb" class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-2"><?php _e( 'Select All', 'appointments' ); ?></label>
							<input id="cb-select-all-2" type="checkbox">
						</td>
					<?php else: ?>
						<th scope="col" class="manage-column column-<?php echo esc_attr($key); ?> app-column-<?php echo esc_attr($key); ?> <?php echo $primary; ?>"><?php echo $title; ?></th>
					<?php endif; ?>
				<?php endforeach; ?>
			</tr>
		</tfoot>

		<tbody id="the-list">
			<?php if ( $apps ): ?>
				<?php $i = 0; ?>
				<?php /** @var Appointments_Appointment $app */ ?>
				<?php foreach($apps as $key => $app): ?>
					<?php $i++; ?>
					<tr id="app-<?php echo $app->ID; ?>" class="app-tr <?php echo $i % 2 ? 'alternate' : ''; ?>">
						<?php foreach ( $columns as $key => $value ): ?>
							<?php if ( $key === 'cb' ): ?>
								<th id="cb" class="column-delete check-column app-check-column" scope="row">
									<label class="screen-reader-text" for="cb-select-<?php echo $app->ID; ?>"><?php printf( __( 'Select Appointment %d on %s', 'appointments' ), $app->ID, $app->get_formatted_start_date() ); ?></label>
									<input type="checkbox" name="app[]" id="cb-select-<?php echo $app->ID; ?>" value="<?php echo esc_attr($app->ID);?>" />
								</th>
							<?php elseif ( array_key_exists( $key, $default_columns ) ): ?>
								<td class="column-<?php echo $key; ?>">
									<?php if ( 'app_ID' === $key ): ?>
										<span class="span_app_ID"><?php	echo $app->ID;?></span>
									<?php elseif ('user' === $key ): ?>
										<?php echo stripslashes( $app->get_client_name() ); ?>
									<?php elseif ('date' === $key ): ?>
										<?php echo $app->get_formatted_start_date(); ?>
										<div class="row-actions">
											<a href="javascript:void(0)" class="app-inline-edit" data-app-id="<?php echo $app->ID; ?>">
												<?php echo 'reserved' == $app->status ? __('See Details (Cannot be edited)', 'appointments') : __('See Details and Edit', 'appointments') ?>
											</a>
											<img class="waiting" style="display:none;" src="<?php echo admin_url('images/spinner.gif')?>" alt="">
										</div>
									<?php elseif ('service' === $key ): ?>
										<?php echo $app->get_service_name(); ?>
									<?php elseif ('worker' === $key ): ?>
										<?php echo appointments_get_worker_name( $app->worker ); ?>
									<?php elseif ('status' === $key ): ?>
										<?php echo appointments_get_status_name( $app->status ); ?>
									<?php elseif ('created' === $key ): ?>
										<?php echo mysql2date( 'Y/m/d', $app->created ); ?><br/>
										<?php echo mysql2date( 'H:i:s', $app->created ); ?>
									<?php endif; ?>
								</td>
							<?php endif; ?>

							<?php do_action( 'appointments_my_appointments_list_row', $app, $key, $value ); ?>

						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr class="no-items">
					<td class="colspanchange" colspan="<?php echo count( $columns ); ?>">
						<?php _e('No appointments have been found.','appointments'); ?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php if ( 'removed' === $type ): ?>
		<div class="tablenav bottom">
			<div class="alignleft actions">

					<p>
						<input type="submit" id="delete_removed" class="button-secondary" value="<?php _e('Permanently Delete Selected Records', 'appointments') ?>" title="<?php _e('Clicking this button deletes logs saved on the server') ?>" />
						<input type="hidden" name="delete_removed" value="delete_removed" />
					</p>
			</div>
		</div>
	<?php endif; ?>
</form>

<div class="tablenav bottom">
	<div class="alignleft actions">
		<form action="<?php echo admin_url('admin-ajax.php?action=app_export'); ?>" method="post">
			<input type="hidden" name="action" value="app_export" />
			<input type="hidden" name="export_type" id="app-export_type" value="type" />
			<input type="submit" id="app-export-selected" class="app-export_trigger button-secondary" value="<?php esc_attr_e(__('Export selected Appointments','appointments')); ?>" />
			<input type="submit" id="app-export-type" class="app-export_trigger button-primary" value="<?php esc_attr_e(sprintf(__('Export %s Appointments','appointments'), App_Template::get_status_name($type))); ?>" data-type="<?php esc_attr_e($type); ?>" />
			<input type="submit" id="app-export-all" class="app-export_trigger button-secondary" value="<?php _e('Export all Appointments','appointments') ?>" title="<?php _e('If you click this button a CSV file containing ALL appointment records will be saved on your PC','appointments') ?>" />

			<?php do_action('app-export-export_form_end'); ?>
		</form>
	</div>
	<?php Appointments_Admin_Appointments_Page::pagination( $pagination_args, 'bottom' ); ?>
</div>


<?php
$services_price = array();
foreach( appointments_get_services() as $service_obj ) {
    $services_price[ $service_obj->ID ] = $service_obj->price;
}

$options = array(
    'servicesPrice' => $services_price,
    'lenght' => count( $apps ),
    'columns' => count( $columns ),
    'nonces' => array(
        'addNew' => wp_create_nonce( 'app-add-new' ),
		'editApp' => wp_create_nonce( 'app-edit-appointment' ),
    )
);

$options = wp_json_encode( $options );
?>



<script type="text/javascript">
    jQuery( document ).ready( function() {
        AppointmentsAdmin.appointmentsList( <?php echo $options; ?> );
    });
</script>

<style>
	a.info-button {
		line-height: 1;
		padding: 0;
		margin-left:10px;
	}
	.row-actions .waiting {
		width: 15px;
		height: 15px;
		vertical-align: top;
	}
	th.sortable a span, th.sorted a span {
		float: left;
		cursor: pointer;
	}
	.sorting-indicator {
		display: block;
		visibility: hidden;
		width: 10px;
		height: 4px;
		margin-top: 8px;
		margin-left: 7px;
	}
	.sorting-indicator:before {
		content: "\f142";
		font: 400 20px/1 dashicons;
		speak: none;
		display: inline-block;
		padding: 0;
		top: -4px;
		left: -8px;
		line-height: 10px;
		position: relative;
		vertical-align: top;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
		text-decoration: none !important;
		color: #444;
	}
	th.asc a:focus span.sorting-indicator, th.asc:hover span.sorting-indicator, th.desc a:focus span.sorting-indicator, th.desc:hover span.sorting-indicator, th.sorted .sorting-indicator {
		visibility: visible;
	}

	@media screen and (max-width: 782px) {
		.wp-list-table tr:not(.inline-edit-row):not(.no-items) td:not(.check-column) {
			display: table-cell;
		}

		.wp-list-table th.column-app_ID,
		.wp-list-table td.column-app_ID,
		.wp-list-table th.column-created,
		.wp-list-table td.column-created,
		.wp-list-table th.column-status,
		.wp-list-table td.column-status{
			display:none !important;
		}

		.wp-list-table th.column-user,
		.wp-list-table td.column-user{
			width:30px;
		}
	}

</style>