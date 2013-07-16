<?php
/*
Plugin Name: Allow HTML emails
Description: By default, the plugin sents plain text emails. Activating this add-on will allow your emails to be sent as HTML.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Emails
Author: Ve Bailovity (Incsub)
*/

class App_Emails_HtmlEmails {

	private function __construct () {}

	public static function serve () {
		$me = new App_Emails_HtmlEmails;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_filter('app-emails-content_type', array($this, 'switch_content_type')); 
	}

	public function switch_content_type () {
		return 'text/html';
	}
}
App_Emails_HtmlEmails::serve();