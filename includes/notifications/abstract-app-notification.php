<?php

abstract class Appointments_Notification {
	
	/** @var  Appointments_Notifications_Manager */
	protected $manager;

	public function __construct( $manager ) {
		$this->manager = $manager;
	}

	abstract function send( $app_id );
}