<?php

$addon = new Appointments_Addon(appointments_plugin_dir() . 'includes/addons/app-locations-location_support.php' );

include_once( appointments_plugin_dir() . 'admin/class-app-addons-admin-list-table.php' );

$table = new Appointments_Addons_Admin_List_Table();
$table->prepare_items();
$table->display();
