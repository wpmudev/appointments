<?php

global $appointments;
if ( is_object( $appointments->get_gcal_api() ) ) {
	$appointments->get_gcal_api()->admin->render_tab();
}