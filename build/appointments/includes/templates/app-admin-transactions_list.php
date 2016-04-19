<?php

global $page, $action, $type, $appointments;

wp_reset_vars( array('type') );

if(empty($type)) $type = 'past';

?>
<div class='wrap'>
	<div class="icon32" style="margin:8px 0 0 0"><img src="<?php echo $appointments->plugin_url . '/images/transactions.png'; ?>" /></div>
	<h2><?php echo __('Transactions','appointments'); ?></h2>

	<ul class="subsubsub">
		<li><a href="<?php echo add_query_arg('type', 'past'); ?>" class="rbutton <?php if($type == 'past') echo 'current'; ?>"><?php  _e('Recent transactions', 'appointments'); ?></a> | </li>
		<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($type == 'pending') echo 'current'; ?>"><?php  _e('Pending transactions', 'appointments'); ?></a> | </li>
		<li><a href="<?php echo add_query_arg('type', 'future'); ?>" class="rbutton <?php if($type == 'future') echo 'current'; ?>"><?php  _e('Future transactions', 'appointments'); ?></a></li>
	</ul>

	<?php
		$appointments->mytransactions($type);
	?>
</div> <!-- wrap -->