<p class="description"><?php _e( 'Here you should define your services for which your client will be making appointments. <b>There must be at least one service defined.</b> Capacity is the number of customers that can take the service at the same time. Enter 0 for no specific limit (Limited to number of service providers, or to 1 if no service provider is defined for that service). Price is only required if you request payment to accept appointments. You can define a description page for the service you are providing.', 'appointments' ) ?></p>
<form method="post" action="">
    <input type="hidden" name="app-current-tab" value="services" />
<?php
global $appointments_services_list;
$appointments_services_list->prepare_items();
$appointments_services_list->display();
?>
</form>
