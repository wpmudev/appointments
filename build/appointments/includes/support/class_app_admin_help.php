<?php

class App_AdminHelp {
	
	private $_help;
	private $_sidebar;
	
	private $_pages = array (
		'post',
		'page',
	);
	
	private function __construct () {
		if (!class_exists('WpmuDev_ContextualHelp')) require_once 'class_wd_contextual_help.php';
		$this->_help = new WpmuDev_ContextualHelp();
		$this->_set_up_sidebar();
	}
	
	public static function serve () {
		$me = new App_AdminHelp;
		$me->_initialize();
	}
	
	private function _initialize () {
		foreach ($this->_pages as $page) {
			$method = "_add_{$page}_page";
			if (method_exists($this, $method)) $this->$method();
		}
		$this->_help->initialize();
	}
	
	private function _set_up_sidebar () {
		$this->_sidebar = '<h4>' . __('Appointments+', 'appointments') . '</h4>';
		if (defined('WPMUDEV_REMOVE_BRANDING') && constant('WPMUDEV_REMOVE_BRANDING')) {
			$this->_sidebar .= '<p>' . __('Lets you accept appointments from front end and manage or create them from admin side.', 'appointments') . '</p>';
		} else {
				$this->_sidebar .= '<ul>' .
					'<li><a href="http://premium.wpmudev.org/project/appointments-plus/" target="_blank">' . __('Project page', 'appointments') . '</a></li>' .
					'<li><a href="http://premium.wpmudev.org/project/appointments-plus/#usage" target="_blank">' . __('Installation and instructions page', 'appointments') . '</a></li>' .
					'<li><a href="http://premium.wpmudev.org/forums/tags/appointments-plus" target="_blank">' . __('Support forum', 'appointments') . '</a></li>' .
				'</ul>' . 
			'';
		}
	}

	private function _add_shortcodes_contextual_help ($screen_id) {
		$help = apply_filters('app-shortcodes-shortcode_help-string', '');

		$this->_help->add_page(
			$screen_id,
			array(
				array(
					'id' => 'app_shortcodes',
					'title' => __('Appointments shortcodes', 'appointments'),
					'content' => $help,
				),
			)
		);
	}

	private function _add_post_page () {
		$this->_add_shortcodes_contextual_help('post');
	}

	private function _add_page_page () {
		$this->_add_shortcodes_contextual_help('page');
	}
}