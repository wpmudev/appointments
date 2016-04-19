<?php

global $appointments;

if(empty($_GET['paged'])) {
	$paged = 1;
} else {
	$paged = ((int) $_GET['paged']);
}

$startat = ($paged - 1) * 50;

$transactions = $appointments->get_transactions($type, $startat, 50);
$total = $appointments->get_total();

$columns = array();

$columns['subscription'] = __('App ID','appointments');
$columns['user'] = __('User','appointments');
$columns['date'] = __('Date/Time','appointments');
$columns['service'] = __('Service','appointments');
$columns['amount'] = __('Amount','appointments');
$columns['transid'] = __('Transaction id','appointments');
$columns['status'] = __('Status','appointments');

$trans_navigation = paginate_links( array(
	'base' => add_query_arg( 'paged', '%#%' ),
	'format' => '',
	'total' => ceil($total / 50),
	'current' => $paged
));

echo '<div class="tablenav">';
if ( $trans_navigation ) echo "<div class='tablenav-pages'>$trans_navigation</div>";
echo '</div>';
?>

	<table cellspacing="0" class="widefat fixed">
		<thead>
		<tr>
		<?php
			foreach($columns as $key => $col) {
				?>
				<th class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
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
				<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
				<?php
			}
		?>
		</tr>
		</tfoot>

		<tbody>
			<?php
			if($transactions) {
				foreach($transactions as $key => $transaction) {
					?>
					<tr valign="middle" class="alternate">
						<td class="column-subscription">
							<?php
								echo $transaction->transaction_app_ID;
							?>

						</td>
						<td class="column-user">
							<?php
								echo $appointments->get_client_name( $transaction->transaction_app_ID );
							?>
						</td>
						<td class="column-date">
							<?php
								echo date_i18n($appointments->datetime_format, $transaction->transaction_stamp);

							?>
						</td>
						<td class="column-service">
						<?php
						$app = appointments_get_appointment( $transaction->transaction_app_ID );
						if ( $app ) {
							$service_id = $app->service;
						}
						echo $appointments->get_service_name( $service_id );
						?>
						</td>
						<td class="column-amount">
							<?php
								$amount = $transaction->transaction_total_amount / 100;

								echo $transaction->transaction_currency;
								echo "&nbsp;" . number_format($amount, 2, '.', ',');
							?>
						</td>
						<td class="column-transid">
							<?php
								if(!empty($transaction->transaction_paypal_ID)) {
									echo $transaction->transaction_paypal_ID;
								} else {
									echo __('None yet','appointments');
								}
							?>
						</td>
						<td class="column-status">
							<?php
								if(!empty($transaction->transaction_status)) {
									echo $transaction->transaction_status;
								} else {
									echo __('None yet','appointments');
								}
							?>
						</td>
					</tr>
					<?php
				}
			} else {
				$columncount = count($columns);
				?>
				<tr valign="middle" class="alternate" >
					<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Transactions have been found, patience is a virtue.','appointments'); ?></td>
				</tr>
				<?php
			}
			?>

		</tbody>
	</table>