<?php
// This is not part of the plugin just a recopilation of all queries done to appointments table
exit();

$results = $wpdb->get_results( "SELECT * FROM " . $this->app_table .
" WHERE created>'".$checkdate."' AND status='pending' AND (".$q.")  " );


$wpdb->get_results( "SELECT * FROM " . $this->app_table . "
WHERE (status='paid' OR status='confirmed')
AND (sent NOT LIKE '%:{$rlike}:%' OR sent IS NULL)
AND DATE_ADD('".date( 'Y-m-d H:i:s', $this->local_time )."', INTERVAL ".(int)$hour." HOUR) > start " );


$rlike = esc_sql(like_escape(trim($hour)));
$results = $wpdb->get_results( "SELECT * FROM " . $this->app_table . "
WHERE (status='paid' OR status='confirmed')
AND worker <> 0
AND (sent_worker NOT LIKE '%:{$rlike}:%' OR sent_worker IS NULL)
AND DATE_ADD('".date( 'Y-m-d H:i:s', $this->local_time )."', INTERVAL ".(int)$hour." HOUR) > start " );


$expireds = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$this->app_table} WHERE start<%s AND status NOT IN ('completed', 'removed')", date("Y-m-d H:i:s", $this->local_time)) );

$update = $wpdb->update( $this->app_table,
	array( 'status'	=> $new_status ),
	array( 'ID'	=> $expired->ID )
);

$expireds = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$this->app_table} WHERE status='pending' AND created<%s", date("Y-m-d H:i:s", $this->local_time - $clear_secs)) );

$update = $wpdb->update( $this->app_table,
	array( 'status'	=> 'removed' ),
	array( 'ID'	=> $expired->ID )
);

$r2 = $wpdb->update(
			$this->app_table,
			array( 'worker'	=>	0 ),
			array( 'worker'	=> $ID )
		);


$sql = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('confirmed', 'paid') APP_ADD ORDER BY {$order_by} LIMIT %d, %d", $startat, $num);
			break;
case 'pending':
			$sql = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('pending') APP_ADD ORDER BY {$order_by} LIMIT %d, %d", $startat, $num);
			break;
case 'completed':
			$sql = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('completed') APP_ADD ORDER BY {$order_by} LIMIT %d, %d", $startat, $num);
			break;
case 'removed':
			$sql = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('removed') APP_ADD ORDER BY {$order_by} LIMIT %d, %d", $startat, $num);
			break;
case 'reserved':
			$sql = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('reserved') APP_ADD ORDER BY {$order_by} LIMIT %d, %d", $startat, $num);
			break;
default:
			$sql = $this->db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('confirmed', 'paid') APP_ADD ORDER BY {$order_by} LIMIT %d, %d", $startat, $num);
			break;
}

$num_active = $wpdb->get_var("SELECT COUNT(ID) FROM " . $appointments->app_table . " WHERE status='paid' OR status='confirmed' " );

$num_pending = $wpdb->get_var("SELECT COUNT(ID) FROM " . $appointments->app_table . " WHERE status='pending' " );

$result = $wpdb->insert(
			$appointments->app_table,
			array(
				'created'	=>	date ("Y-m-d H:i:s", $appointments->local_time ),
				'user'		=>	$user_id,
				'name'		=>	$name,
				'email'		=>	$email,
				'phone'		=>	$phone,
				'address'	=>	$address,
				'city'		=>	$city,
				'location'	=>	$location,
				'service'	=>	$service,
				'worker'	=> 	$worker,
				'price'		=>	$price,
				'status'	=>	$status,
				'start'		=>	date ("Y-m-d H:i:s", $start),
				'end'		=>	date ("Y-m-d H:i:s", $start + ($duration * 60 ) ),
				'note'		=>	$note
			)
		);




if ('selected' == $type && !empty($_POST['app'])) {
// selected appointments
$ids = array_filter(array_map('intval', $_POST['app']));
if ($ids) $sql = "SELECT * FROM {$appointments->app_table} WHERE ID IN(" . join(',', $ids) . ") ORDER BY ID";
} else if ('type' == $type) {
$status = !empty($_POST['status']) ? $_POST['status'] : false;
if ('active' === $status) $sql = $appointments->db->prepare("SELECT * FROM {$appointments->app_table} WHERE status IN('confirmed','paid') ORDER BY ID", $status);
else if ($status) $sql = $appointments->db->prepare("SELECT * FROM {$appointments->app_table} WHERE status=%s ORDER BY ID", $status);
} else if ('all' == $type) {
$sql = "SELECT * FROM {$appointments->app_table} ORDER BY ID";
}
if (!$sql) wp_die(__('Nothing to download!','appointments'));

$apps = $appointments->db->get_results($sql, ARRAY_A);


$export = $wpdb->get_col("SELECT ID FROM {$this->app_table} WHERE status IN({$clean_stat})");

$wpdb->update( $this->app_table, array( 'gcal_updated' => date ("Y-m-d H:i:s", $this->local_time ) ), array( 'ID'=>$app_id ) );

$present_events = $wpdb->get_col("SELECT DISTINCT gcal_ID FROM {$this->app_table} WHERE gcal_ID IS NOT NULL");

$wpdb->query("DELETE FROM {$this->app_table} WHERE status='reserved'");


$result = $wpdb->query( "INSERT INTO " . $this->app_table . " (created,service,worker,status,start,end,note,gcal_ID,gcal_updated)
VALUES ". implode(',',$values).  "
ON DUPLICATE KEY UPDATE start=VALUES(start), end=VALUES(end), gcal_updated=VALUES(gcal_updated)" ); // Key is autoincrement, it'll never update!



if (!empty($to_update)) {
	$result = (int)$result;
	foreach ($to_update as $upd) {
		$res2 = $wpdb->query("UPDATE {$this->app_table} SET {$upd}");
		if ($res2) $result++;
	}
	$message = sprintf( __('%s appointment record(s) affected.','appointments'), $result ). ' ';
}

if (!empty($event_ids)) {
	// Delete unlisted events for the selected worker
	$event_ids_range = '(' . join(array_filter($event_ids), ',') . ')';
	$r3 = $wpdb->query($wpdb->prepare("DELETE FROM {$this->app_table} WHERE status='reserved' AND worker=%d AND gcal_ID NOT IN {$event_ids_range}", $worker_id));
} else { // In case we have existing reserved events that have been removed from GCal and nothing else
	$r3 = $wpdb->query($wpdb->prepare("DELETE FROM {$this->app_table} WHERE status='reserved' AND worker=%d AND gcal_ID IS NOT NULL", $worker_id));
}


$max = $wpdb->get_var( "SELECT MAX(ID) FROM " . $this->app_table . " " );
if ( $max )
	 $wpdb->query( "ALTER TABLE " . $this->app_table ." AUTO_INCREMENT=". ($max+1). " " );


$stat = '';
foreach ( $statuses as $s ) {
	// Allow only defined stats
	if ( array_key_exists( trim( $s ), App_Template::get_status_names() ) )
		$stat .= " status='". trim( $s ) ."' OR ";
}
$stat = rtrim( $stat, "OR " );

$results = $wpdb->get_results( "SELECT * FROM " . $appointments->app_table . " WHERE (".$stat.") ORDER BY ".$appointments->sanitize_order_by( $order_by )." " );

$sql = "SELECT DISTINCT location FROM {$appointments->app_table} WHERE {$status} {$user}";


$res = $wpdb->update(
			$appointments->app_table,
			array('location' => $location_id),
			array(
				'location' => $old_location_id,
				'service' => $service_id,
			), '%s', '%s'
		);

    $res = $wpdb->update(
			$appointments->app_table,
			array('location' => $location_id),
			array(
				'location' => $old_location_id,
				'worker' => $worker_id,
			), '%s', '%s'
		);


    private function _get_booked_appointments_for_period ($service_ids, $period) {
    		$start = date('Y-m-d H:i:s', $period->get_start());
    		$end = date('Y-m-d H:i:s', $period->get_end());
    		$services = join(',', array_filter(array_map('intval', $service_ids)));

    		$sql = "SELECT COUNT(*) FROM {$this->_core->app_table} WHERE service IN ({$services}) AND end > '{$start}' AND start < '{$end}' AND status IN ('paid', 'confirmed')";
    		$cnt = (int)$this->_core->db->get_var($sql);

    		return $cnt;
    	}


      $table = $appointments->app_table;
		$where = "AND (status='pending' OR status='paid' OR status='confirmed' OR status='reserved')";

		if ($appointments->service) {
			$where .= " AND service={$appointments->service}";
		}
		if ($appointments->worker) {
			$where .= " AND worker={$appointments->worker}";
		}

		$sql = "SELECT name,user,start,end FROM {$table} WHERE UNIX_TIMESTAMP(start)>'{$interval_start}' AND UNIX_TIMESTAMP(end)<'{$interval_end}' {$where}";
		$res = $wpdb->get_results($sql);