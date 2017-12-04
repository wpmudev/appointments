<div class='wrap'>
	<h2><?php echo __('Transactions','appointments'); ?></h2>

	<ul class="subsubsub">
		<li><a href="<?php echo add_query_arg('type', 'past'); ?>" class="rbutton <?php if($type == 'past') echo 'current'; ?>"><?php  _e('Recent transactions', 'appointments'); ?></a> | </li>
		<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($type == 'pending') echo 'current'; ?>"><?php  _e('Pending transactions', 'appointments'); ?></a> | </li>
		<li><a href="<?php echo add_query_arg('type', 'future'); ?>" class="rbutton <?php if($type == 'future') echo 'current'; ?>"><?php  _e('Future transactions', 'appointments'); ?></a></li>
	</ul>

	<div class="tablenav">
		<?php if ( $trans_navigation ): ?>
			<div class='tablenav-pages'><?php echo $trans_navigation; ?></div>
		<?php endif; ?>
	</div>

	<table cellspacing="0" class="widefat fixed">
		<thead>
			<tr>
				<?php foreach($columns as $key => $col): ?>
					<th class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>

		<tfoot>
			<tr>
				<?php reset($columns); ?>
				<?php foreach($columns as $key => $col): ?>
					<th class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
				<?php endforeach; ?>
			</tr>
		</tfoot>

		<tbody>
			<?php if($transactions): ?>

				<?php foreach($transactions as $key => $transaction): ?>
				<?php /** @var Appointments_Transaction $transaction */ ?>

				<?php
					$service = $transaction->get_service();
					$service_name = '';

					//Chose instanceof as it seems to be faster: http://dev.airve.com/demo/speed_tests/php/is_a_is_object.php
					if( $service instanceof Appointments_Service ){
						$service_name = appointments_get_service_name( $service->ID );
					}
					else{
						$service_name = appointments_get_service_name( null );
					}
				?>

					<tr valign="middle" class="alternate">
						<td class="column-subscription"><?php echo $transaction->transaction_app_ID; ?></td>
						<td class="column-user"><?php echo $transaction->get_client_name(); ?></td>
						<td class="column-date"><?php echo date_i18n($appointments->datetime_format, $transaction->transaction_stamp); ?></td>
						<td class="column-service"><?php echo $service_name; ?></td>
						<td class="column-amount"> <?php echo $transaction->transaction_currency . " " . $transaction->get_total_amount(); ?></td>
						<td class="column-transid">
							<?php echo ! empty( $transaction->transaction_paypal_ID ) ? $transaction->transaction_paypal_ID : __('None yet','appointments'); ?>
						</td>
						<td class="column-status">
							<?php echo ! empty( $transaction->transaction_status ) ? $transaction->transaction_status : __('None yet','appointments'); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr valign="middle" class="alternate" >
					<td colspan="<?php echo count($columns); ?>" scope="row"><?php _e('No Transactions have been found, patience is a virtue.','appointments'); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

</div> <!-- wrap -->