<?php

$addon = new Appointments_Addon(appointments_plugin_dir() . 'includes/addons/app-locations-location_support.php' );

include_once( appointments_plugin_dir() . 'admin/class-app-addons-admin-list-table.php' );

$table = new Appointments_Addons_Admin_List_Table();
$table->prepare_items();
?>

<form action="" method="post">
	<?php $table->display(); ?>
</form>

<style>
	table.addons #the-list tr .check-column {
	}
	table.addons #the-list tr.active .check-column {
		border-left: 4px solid #00a0d2
	}
	table.addons #the-list .inactive .check-column,
	table.addons thead .check-column{
		padding-left:6px;
	}
	table.addons tr th,
	table.addons tr td {
		background: white;
	}

	table.addons #the-list tr th,
	table.addons #the-list tr td {
		-webkit-box-shadow: inset 0 -1px 0 rgba(0,0,0,0.1);
		box-shadow: inset 0 -1px 0 rgba(0,0,0,0.1);
	}

	table.addons #the-list tr.active th,
	table.addons #the-list tr.active td {
		background:#f7fcfe;
	}
	table.addons .row-actions {
		left:0;
	}
</style>
