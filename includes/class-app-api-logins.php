<?php

// API login after the options have been initialized
add_action( 'appointments_init', array( 'Appointments_API_Logins', 'init' ), 20 );

class Appointments_API_Logins {

	public static function init() {
		$api_logins = new self();
		$api_logins->setup_api_logins();
	}

	function setup_api_logins () {
		$options = appointments_get_options();
		if ( ! @$options['accept_api_logins'] ) {
			return;
		}

		add_action('wp_ajax_nopriv_app_facebook_login', array($this, 'handle_facebook_login'));
		add_action('wp_ajax_nopriv_app_get_twitter_auth_url', array($this, 'handle_get_twitter_auth_url'));
		add_action('wp_ajax_nopriv_app_twitter_login', array($this, 'handle_twitter_login'));
		add_action('wp_ajax_nopriv_app_ajax_login', array($this, 'ajax_login'));
	}

	/**
	 * Handles Facebook user login and creation
	 * Modified from Events and Bookings by Ve
	 */
	function handle_facebook_login () {
		header("Content-type: application/json");

		$resp = array(
			"status" => 0,
		);
		$fb_uid = @$_POST['user_id'];
		$token = @$_POST['token'];
		if ( ! $token ) {
			die( json_encode( $resp ) );
		}


		$url = "https://graph.facebook.com/v2.4/me?fields=id,name,first_name,last_name,email,age_range,link,gender,picture,locale,verified&access_token=".$token;

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		$response = curl_exec( $curl );
		curl_close( $curl );

		$access_data = json_decode($response, true);

		$email = is_email($access_data["email"]);
		if (!$email) die(json_encode($resp)); // Wrong email



		$wp_user = get_user_by('email', $email);



		if (!$wp_user) { // Not an existing user, let's create a new one
			$password = wp_generate_password(12, false);

			$username = '';


			if(!empty($access_data['name'])) {
				$username = strtolower($access_data['name']);
			}
			else if (!empty($access_data['first_name']) && !empty($access_data['last_name'])) {
				$username = strtolower( $access_data['first_name'].'_'.$access_data['last_name'] );
			}
			else {
				$user_emailname = explode('@', $access_data['email']);
				$username = strtolower( $user_emailname[0] );
			}


			$username = preg_replace('/[^_0-9a-z]/i', '_', strtolower($username));

			$wp_user = wp_create_user($username, $password, $email);
			if (is_wp_error($wp_user)) die(json_encode($resp)); // Failure creating user
		} else {
			$wp_user = $wp_user->ID;
		}


		$user = get_userdata($wp_user);

		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID); // Logged in with Facebook, yay
		do_action('wp_login', $user->user_login);

		die(json_encode(array(
			"status" => 1,
			"user_id"=>(int)$user->ID
		)));


	}

	/**
	 * Get OAuth request URL and token.
	 */
	function handle_get_twitter_auth_url () {
		header("Content-type: application/json");
		$twitter = $this->_get_twitter_object();
		$request_token = $twitter->getRequestToken($_POST['url']);
		echo json_encode(array(
			'url' => $twitter->getAuthorizeURL($request_token['oauth_token']),
			'secret' => $request_token['oauth_token_secret']
		));
		die;
	}

	/**
	 * Spawn a TwitterOAuth object.
	 */
	function _get_twitter_object ($token=null, $secret=null) {
		$options = appointments_get_options();
		// Make sure options are loaded and fresh
		if ( @!$options['twitter-app_id'] )
			$options = get_option( 'appointments_options' );
		if (!class_exists('TwitterOAuth'))
			include WP_PLUGIN_DIR . '/appointments/includes/external/twitteroauth/twitteroauth.php';
		$twitter = new TwitterOAuth(
			$options['twitter-app_id'],
			$options['twitter-app_secret'],
			$token, $secret
		);
		return $twitter;
	}

	/**
	 * Login or create a new user using whatever data we get from Twitter.
	 */
	function handle_twitter_login () {
		header("Content-type: application/json");
		$resp = array(
			"status" => 0,
		);
		$secret = @$_POST['secret'];
		$data_str = @$_POST['data'];
		$data_str = ('?' == substr($data_str, 0, 1)) ? substr($data_str, 1) : $data_str;
		$data = array();
		parse_str($data_str, $data);
		if (!$data) die(json_encode($resp));

		$twitter = $this->_get_twitter_object($data['oauth_token'], $secret);
		$access = $twitter->getAccessToken($data['oauth_verifier']);

		$twitter = $this->_get_twitter_object($access['oauth_token'], $access['oauth_token_secret']);
		$tw_user = $twitter->get('account/verify_credentials');

		// Have user, now register him/her
		$domain = preg_replace('/www\./', '', parse_url(site_url(), PHP_URL_HOST));
		$username = preg_replace('/[^_0-9a-z]/i', '_', strtolower($tw_user->name));
		$email = $username . '@twitter.' . $domain; //STUB email
		$wp_user = get_user_by('email', $email);

		if (!$wp_user) { // Not an existing user, let's create a new one
			$password = wp_generate_password(12, false);
			$count = 0;
			while (username_exists($username)) {
				$username .= rand(0,9);
				if (++$count > 10) break;
			}

			$wp_user = wp_create_user($username, $password, $email);
			if (is_wp_error($wp_user)) die(json_encode($resp)); // Failure creating user
		} else {
			$wp_user = $wp_user->ID;
		}

		$user = get_userdata($wp_user);
		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID); // Logged in with Twitter, yay
		do_action('wp_login', $user->user_login);

		die(json_encode(array(
			"status" => 1,
			"user_id"=>$user->ID
		)));
	}

	/**
	 * Login from front end by WordPress
	 */
	function ajax_login( ) {

		header("Content-type: application/json");
		$user = wp_signon( );

		if ( !is_wp_error($user) ) {

			die(json_encode(array(
				"status" => 1,
				"user_id"=>$user->ID
			)));
		}
		die(json_encode(array(
			"status" => 0,
			"error" => $user->get_error_message()
		)));
	}
}