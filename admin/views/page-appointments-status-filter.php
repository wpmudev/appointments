<h2 class="screen-reader-text"><?php _e( 'Filter Appointments List', 'appointments' ); ?></h2>
<ul class="subsubsub">
	<li><a href="<?php echo add_query_arg( 'type', 'active' ); ?>" class="rbutton <?php if ( $type == 'active' ) { echo 'current'; } ?>"><?php  _e( 'Active', 'appointments' ); ?></a> (<?php echo $status_count['paid'] + $status_count['confirmed']; ?>) | </li>
	<li><a href="<?php echo add_query_arg( 'type', 'pending' ); ?>" class="rbutton <?php if ( $type == 'pending' ) { echo 'current'; } ?>"><?php  _e( 'Pending', 'appointments' ); ?></a> (<?php echo $status_count['pending']; ?>) | </li>
	<li><a href="<?php echo add_query_arg( 'type', 'completed' ); ?>" class="rbutton <?php if ( $type == 'completed' ) { echo 'current'; } ?>"><?php  _e( 'Completed', 'appointments' ); ?></a> (<?php echo $status_count['completed']; ?>) | </li>
	<li><a href="<?php echo add_query_arg( 'type', 'reserved' ); ?>" class="rbutton <?php if ( $type == 'reserved' ) { echo 'current'; } ?>"><?php  _e( 'Reserved by GCal', 'appointments' ); ?></a> (<?php echo $status_count['reserved']; ?>) | </li>
	<li><a href="<?php echo add_query_arg( 'type', 'removed' ); ?>" class="rbutton <?php if ( $type == 'removed' ) { echo 'current'; } ?>"><?php  _e( 'Removed', 'appointments' ); ?></a> (<?php echo $status_count['removed']; ?>)</li>
</ul>

