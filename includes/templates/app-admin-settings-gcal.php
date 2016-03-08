<?php
global $appointments;
//if (is_object($appointments->gcal_api)) $appointments->gcal_api->render_tab();
if ( is_object( $appointments->get_gcal_api() ) ) {
	$appointments->get_gcal_api()->admin->render_tab();
}