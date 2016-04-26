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
							$class = $key === 'date' ? 'column-primary ' : '';
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
					<?php $primary = $key === 'date' ? 'column-primary' : ''; ?>
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
											<a href="javascript:void(0)" class="app-inline-edit">
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

							<?php do_action( 'appointments_my_appointments_list_row', $app ); ?>

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





<script type="text/javascript">
	var service_price = new Array();
	<?php foreach( appointments_get_services() as $service_obj ) { ?>
	service_price[<?php echo $service_obj->ID ?>] = '<?php echo $service_obj->price ?>';
	<?php
	}
	?>
	jQuery(document).ready(function($){
		$("#delete_removed").click( function() {
			if ( !confirm('<?php echo esc_js( __("Are you sure to delete the selected record(s)?","appointments") ) ?>') )
			{return false;}
			else {
				return true;
			}
		});
		var th_sel = $("td.check-column input:checkbox");
		var td_sel = $("th.check-column input:checkbox");
		th_sel.change( function() {
			if ( $(this).is(':checked') ) {
				td_sel.attr("checked","checked");
				th_sel.not(this).attr("checked","checked");
			}
			else{
				td_sel.removeAttr('checked');
				th_sel.not(this).removeAttr('checked');
			}
		});
		var col_len = $("table").find("tr:first th").length;
		// Add new
		$(".add-new-h2").click(function(){
			$("table.widefat .inline-edit-row .cancel").click(); // Remove active edits
			$(".add-new-waiting").show();
			var data = {action: 'inline_edit', col_len: col_len, app_id:0, nonce: '<?php echo wp_create_nonce() ?>', columns: <?php echo count( $columns ); ?>};
			$.post(ajaxurl, data, function(response) {
				$(".add-new-waiting").hide();
				if ( response && response.error ){
					alert(response.error);
				}
				else if (response) {
					$("table.widefat").prepend(response.result);
				}
				else {alert("<?php echo esc_js(__('Unexpected error','appointments'))?>");}
			},'json');
		});
		// Edit
		$(".app-inline-edit").click(function(){
			var app_parent = $(this).parents(".app-tr");
			app_parent.find(".waiting").show();
			var app_id = app_parent.find(".span_app_ID").html();
			var data = {action: 'inline_edit', col_len: col_len, app_id: app_id, nonce: '<?php echo wp_create_nonce() ?>', columns: <?php echo count( $columns ); ?>};
			$.post(ajaxurl, data, function(response) {
				app_parent.find(".waiting").hide();
				if ( response && response.error ){
					alert(response.error);
				}
				else if (response) {
					$('.inline-edit-row').hide();
					app_parent.hide();
					app_parent.after(response.result);
				}
				else {alert('<?php echo esc_js(__('Unexpected error','appointments'))?>');}
			},'json');
		});
		$("table").on("click", ".cancel", function(){
			$(".inline-edit-row").hide();
			$(".app-tr").show();
		});
		// Add datepicker only once and when focused
		// Ref: http://stackoverflow.com/questions/3796207/using-one-with-live-jquery
		$("table").on("focus", ".datepicker", function(e){
			var $me = $(e.target);
			$me.attr("data-timestamp", '');
			if( $me.data('focused')!='yes' ) {
				var php_date_format = "<?php echo $appointments->safe_date_format() ?>";
				var js_date_format = php_date_format.replace("F","MM").replace("j","dd").replace("Y","yyyy").replace("y","yy");
				$(".datepicker").datepick({
					dateFormat: js_date_format,
					onClose: function (dates) {
						if (!dates.length || !dates[0] || !dates[0].getFullYear) return;
						var time = dates[0].getFullYear() + '-' + (parseInt(dates[0].getMonth(), 10)+1) + '-' + dates[0].getDate();
						$me.attr("data-timestamp", time);
					}
				});
			}
			$(e.target).data('focused','yes');
		});
		$("table").on("click", ".save", function(){
			var save_parent = $(this).parents(".inline-edit-row");
			var user = save_parent.find('select[name="user"] option:selected').val();
			var name = save_parent.find('input[name="cname"]').val();
			var email = save_parent.find('input[name="email"]').val();
			var phone = save_parent.find('input[name="phone"]').val();
			var address = save_parent.find('input[name="address"]').val();
			var city = save_parent.find('input[name="city"]').val();
			var service = save_parent.find('select[name="service"] option:selected').val();
			var worker = save_parent.find('select[name="worker"] option:selected').val();
			var price = save_parent.find('input[name="price"]').val();
			var date = save_parent.find('input[name="date"]').val();
			var time = save_parent.find('select[name="time"] option:selected').val();
			var note = save_parent.find('textarea[name="note"]').val();
			var status = save_parent.find('select[name="status"] option:selected').val();

			var dt = save_parent.find('input[name="date"]').attr("data-timestamp");
			if (dt.length) date = dt;
			else return false;

			save_parent.find(".waiting").show();
			var resend = 0;
			if (save_parent.find('input[name="resend"]').is(':checked') ) { resend=1;}
			var app_id = save_parent.find('input[name="app_id"]').val();
			var data = {action: 'inline_edit_save', user:user, name:name, email:email, phone:phone, address:address,city:city, service:service, worker:worker, price:price, date:date, time:time, note:note, status:status, resend:resend, app_id: app_id, nonce: '<?php echo wp_create_nonce() ?>'};
			$(document).trigger('app-appointment-inline_edit-save_data', [data, save_parent]);
			$.post(ajaxurl, data, function(response) {
				save_parent.find(".waiting").hide();
				if ( response && response.error ){
					save_parent.find(".error").html(response.error).show().delay(10000).fadeOut('slow');
				} else if (response) {
					save_parent.find(".error").html(response.message).show().delay(10000).fadeOut('slow');
				} else {alert("<?php echo esc_js(__('Unexpected error','appointments'))?>");}
				if (!(app_id && parseInt(app_id, 10)) && response && response.app_id) {
					app_id = parseInt(response.app_id, 10);
					save_parent.find('input[name="app_id"]').val(app_id);
				}
			},'json');
		});
		// Change service price as selection changes
		$("table").on("change", 'select[name="service"]', function(){
			$(this).parents(".inline-edit-col").find('input[name="price"]').val(service_price[$(this).val()]);
		});
	});
</script>

<script>
	(function ($) {
		function toggle_selected_export () {
			var $sel = $("#the-list .check-column :checked");
			if ($sel.length) $("#app-export-selected").show();
			else $("#app-export-selected").hide();
		}
		$(document).on("click", ".app-export_trigger", function () {
			var $me = $(this),
				$form = $me.closest("form"),
				$sel = $(".column-delete.app-check-column :checked"),
				$type = $form.find("#app-export_type")
				;

			if ($me.is("#app-export-selected") && $sel.length) {
				$sel.each(function () {
					$form.append("<input type='hidden' name='app[]' value='" + $(this).val() + "' />");
				});
				$type.val("selected");
				return true;
			} else if ($me.is("#app-export-type")) {
				$form.append("<input type='hidden' name='status' value='" + $me.attr("data-type") + "' />");
				$type.val("type");
				return true;
			} else if ($me.is("#app-export-all")) {
				$type.val("all");
				return true;
			}
			return false;
		});
		$(document).on("change", ".check-column input, .app-column-cb input", toggle_selected_export);
		$(toggle_selected_export);
	})(jQuery);

	jQuery(document).ready(function($){
		$(".app-change-status-btn").click(function(e){
			var button = $(this);
			var selection = $("th.app-check-column input:checkbox:checked");
			// var data = { 'app[]' : []};
			selection.each(function() {
				// data['app[]'].push($(this).val());
				button.after('<input type="hidden" name="app[]" value="'+$(this).val()+'"/>');
			});

			return true;


		});
	});

	jQuery(document).ready(function($){
		$(".info-button").click(function(){
			$(".status-description").toggle('fast');
		});
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
</style>