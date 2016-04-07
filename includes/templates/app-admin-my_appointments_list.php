<?php
global $appointments;

if(empty($_GET['paged'])) {
	$paged = 1;
} else {
	$paged = ((int) $_GET['paged']);
}

if ( isset( $appointments->options["records_per_page"] ) && $appointments->options["records_per_page"] )
	$rpp = $appointments->options["records_per_page"];
else
	$rpp = 50;

$args = array();
if ( isset( $_GET['s'] ) && trim( $_GET['s'] ) != '' ) {
	$args['s'] = $_GET['s'];
}

if ( isset( $_GET['app_service_id'] ) && $_GET['app_service_id'] ) {
	$args['service'] = $_GET['app_service_id'];
}

if ( isset( $_GET['app_provider_id'] ) && $_GET['app_provider_id'] ) {
	$args['worker'] = $_GET['app_provider_id'];
}

if ( isset( $_GET['app_order_by']) && $_GET['app_order_by'] ) {
	$_orderby        = explode( '_', $_GET['app_order_by'] );
	if ( count( $_orderby ) == 1 ) {
		$args['orderby']   = $_orderby[0];
		$args['order'] = 'ASC';
	}
	elseif ( count( $_orderby ) == 2 ) {
		$args['order']   = $_orderby[1];
		$args['orderby'] = $_orderby[0];
	}

}
else {
	$args['orderby']   = 'ID';
	$args['order'] = 'DESC';
}

switch($type) {
	case 'active':
		$args['status'] = array( 'confirmed', 'paid' );
		break;
	default:
		$args['status'] = array( $type );
		break;
}

$args['per_page'] = $rpp;
$args['page'] = $paged;

$apps = appointments_get_appointments( $args );
$args['count'] = true;
$total = appointments_get_appointments( $args );

$columns = array();

if ( true || isset( $_GET["type"] ) && 'removed' == $_GET["type"] )$columns['delete'] = '<input type="checkbox" />';
$columns['app_ID'] = __('ID','appointments');
$columns['user'] = __('Client','appointments');
$columns['date'] = __('Date/Time','appointments');
$columns['service'] = __('Service','appointments');
$columns['worker'] = __('Provider','appointments');
$columns['status'] = __('Status','appointments');
$columns = apply_filters( 'appointments_my_appointments_list_columns', $columns );

$pag_args = array(
	'base' => add_query_arg( 'paged', '%#%' ),
	'format' => '',
	'total' => ceil($total / $rpp),
	'current' => $paged
);
$trans_navigation = paginate_links( $pag_args );

if ( $trans_navigation ) {
	echo '<div class="tablenav">';
	echo "<div class='tablenav-pages'>$trans_navigation</div>";
	echo '</div>';
}

// Only for "Removed" tab
if ( true || isset( $_GET["type"] ) && 'removed' == $_GET["type"] ) {
?>
	<form method="post" >

<?php
}
?>

	<table cellspacing="0" class="widefat">
		<thead>
		<tr>
		<?php
			foreach($columns as $key => $col) {
				?>
				<th style="" class="manage-column column-<?php echo esc_attr($key); ?> app-column-<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" scope="col"><?php echo $col; ?></th>
				<?php
			}
		?>
		</tr>
		</thead>

		<tfoot>
		<tr>
		<?php
			reset($columns);
			foreach($columns as $key => $col) {
				?>
				<th style="" class="manage-column column-<?php echo esc_attr($key); ?> app-column-<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" scope="col"><?php echo $col; ?></th>
				<?php
			}
		?>
		</tr>
		</tfoot>

		<tbody>
			<?php
			if($apps) {
				foreach($apps as $key => $app) {

					?>
					<tr valign="middle" class="alternate app-tr">
					<?php
					// Only for "Removed" tab
					if ( true || isset( $_GET["type"] ) && 'removed' == $_GET["type"] ) {
					?>
						<td class="column-delete check-column app-check-column">
						<input type="checkbox" name="app[]" value="<?php echo esc_attr($app->ID);?>" />
						</td>

					<?php
					}
					?>
						<td class="column-app_ID">
							<span class="span_app_ID"><?php	echo $app->ID;?></span>

						</td>
						<td class="column-user">
							<?php
								echo stripslashes($appointments->get_client_name($app->ID));
							?>
							<div class="row-actions">
							<a href="javascript:void(0)" class="app-inline-edit"><?php if ( 'reserved' == $app->status ) _e('See Details (Cannot be edited)', 'appointments'); else _e('See Details and Edit', 'appointments') ?></a>
							<img class="waiting" style="display:none;" src="<?php echo admin_url('images/wpspin_light.gif')?>" alt="">
							</div>
						</td>
						<td class="column-date">
							<?php
								echo mysql2date($appointments->datetime_format, $app->start);

							?>
						</td>
						<td class="column-service">
							<?php
							echo $appointments->get_service_name( $app->service );
							?>
						</td>
						<td class="column-worker">
							<?php
								echo appointments_get_worker_name( $app->worker );
							?>
						</td>
						<td class="column-status">
							<?php
								if(!empty($app->status)) {
									echo App_Template::get_status_name($app->status);
								} else {
									echo __('None yet','appointments');
								}
							?>
						</td>
						<?php do_action( 'appointments_my_appointments_list_row', $app ); ?>
					</tr>
					<?php

				}
			}
			else {
				$columncount = count($columns);
				?>
				<tr valign="middle" class="alternate" >
					<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No appointments have been found.','appointments'); ?></td>
				</tr>
				<?php
			}
			?>

		</tbody>
	</table>
<?php
// Only for "Removed" tab
if ( isset( $_GET["type"] ) && 'removed' == $_GET["type"] ) {
?>
	<p>
	<input type="submit" id="delete_removed" class="button-secondary" value="<?php _e('Permanently Delete Selected Records', 'appointments') ?>" title="<?php _e('Clicking this button deletes logs saved on the server') ?>" />
	<input type="hidden" name="delete_removed" value="delete_removed" />

	</p>


<?php } ?>
	</form>

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
		var th_sel = $("th.column-delete input:checkbox");
		var td_sel = $("th.column-check input:checkbox");
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
			var data = {action: 'inline_edit', col_len: col_len, app_id:0, nonce: '<?php echo wp_create_nonce() ?>'};
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
			var data = {action: 'inline_edit', col_len: col_len, app_id: app_id, nonce: '<?php echo wp_create_nonce() ?>'};
			$.post(ajaxurl, data, function(response) {
				app_parent.find(".waiting").hide();
				if ( response && response.error ){
					alert(response.error);
				}
				else if (response) {
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
			var note = save_parent.find('textarea').val();
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
