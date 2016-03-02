<?php

function appointments_get_options() {
	return get_option( 'appointments_options', appointments_get_default_options() );
}

function appointments_get_default_options() {
	return apply_filters( 'appointments_default_options', array() );
}