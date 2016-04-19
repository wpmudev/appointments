<form method="get" action="" class="search-form">
	<input type="hidden" name="page" value="appointments">
	<input type="hidden" value="<?php echo $type?>" name="type" />

	<?php if ( $service_id ): ?>
		<input type="hidden" value="<?php echo $service_id; ?>" name="app_service_id" />
	<?php endif; ?>
	<?php if ( $worker_id ): ?>
		<input type="hidden" value="<?php echo $worker_id; ?>" name="app_provider_id" />
	<?php endif; ?>
	<?php if ( $orderby ): ?>
		<input type="hidden" value="<?php echo $orderby; ?>" name="orderby" />
	<?php endif; ?>
	<?php if ( $order ): ?>
		<input type="hidden" value="<?php echo $order; ?>" name="order" />
	<?php endif; ?>

	<p class="search-box">
		<label for="app-search-input" class="screen-reader-text"><?php _e('Search Client','appointments'); ?>:</label>
		<input type="text" id="app-search-input" value="<?php echo esc_attr($s); ?>" name="s" />
		<input type="submit" class="button" value="<?php _e('Search Client','appointments'); ?>" />
	</p>

	<br class='clear' />

	<div class="tablenav top">

		<div class="alignleft actions bulkactions">
			<label for="app_new_status" class="screen-reader-text"><?php _e('Bulk status change','appointments'); ?></label>
			<select name="app_new_status" id="app_new_status" style='float:none;'>
				<option value=""><?php _e('Bulk status change','appointments'); ?></option>
				<?php foreach ( appointments_get_statuses() as $value=>$name ): ?>
					<option value="<?php echo esc_attr($value); ?>" class="hide-if-no-js"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="submit" name="bulk_status" class="button app-change-status-btn" value="<?php _e('Change Status','appointments'); ?>" />
		</div>

		<div class="alignleft actions">

			<label for="app_service_id" class="screen-reader-text"><?php _e('Filter by service','appointments'); ?></label>
			<select name="app_service_id" id="app_service_id">
				<option value=""><?php _e('Filter by service','appointments'); ?></option>
				<?php foreach ( appointments_get_services() as $service ): ?>
					<option <?php selected( $service->ID, $service_id ); ?> value="<?php echo $service->ID; ?>"><?php echo esc_html( $appointments->get_service_name( $service->ID ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<label for="app_provider_id" class="screen-reader-text"><?php _e('Filter by service provider','appointments'); ?></label>
			<select name="app_provider_id" id="app_provider_id" style='float:none;'>
				<option value=""><?php _e('Filter by service provider','appointments'); ?></option>
				<?php foreach ( appointments_get_workers() as $worker ): ?>
					<option <?php selected( $worker->ID, $worker_id ); ?> value="<?php echo $worker->ID; ?>"><?php echo esc_html( appointments_get_worker_name( $worker->ID ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="submit" class="button" name="filter_action" value="<?php _e('Filter','appointments'); ?>" />
			<input type="submit" class="button" name="filter_reset_action" value="<?php _e('Reset filters','appointments'); ?>" />
		</div>
		
		<?php Appointments_Admin_Appointments_Page::pagination( $pagination_args, 'top' ); ?>
	</div>




</form>