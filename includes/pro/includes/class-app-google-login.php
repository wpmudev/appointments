<?php

/**
 * Manages Google Login from front-end
 *
 * @since 1.9
 */
class Appointments_Google_Login {

	public $_google_user_cache = null;

	public $openid = false;

	public function __construct() {

		// Settings
		add_filter( 'appointments_default_options', array( $this, 'add_default_options' ) );
		add_action( 'appointments_settings_tab-main-section-accesibility', array( $this, 'add_accesibility_settings' ) );

		// Script localization
		add_filter( 'app-scripts-api_l10n', array( $this, 'localize_script' ) );

		// Handle login
		add_action( 'wp_ajax_nopriv_app_google_plus_login', array( $this, 'handle_gplus_login' ) );

		// Execute this after logins have been setup
		add_action( 'appointments_init', array( $this, 'setup_api_login' ), 50 );

		// Custom styles
		add_action( 'wp_head', array( $this, 'wp_head' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Add a default value for the Google Login setting
	 *
	 * @param array $defaults Current default values
	 *
	 * @return array
	 */
	public function add_default_options( $defaults ) {
		$defaults['google-client_id'] = '';
		return $defaults;
	}

	/**
	 * Add values to the localization script
	 */
	public function localize_script( $i10n ) {
		$options = appointments_get_options();
		$i10n['gg_client_id'] = $options['google-client_id'];
		return $i10n;
	}

	/**
	 * Add custom styles
	 */
	public function wp_head() {
		// Don't show Google+ button if openid is not enabled
		if ( ! $this->openid ) {
			?>
			<style>.appointments-login_link-google{display:none !important;}</style>
			<?php
		}
	}

	/**
	 * Add settings to Accesibility section
	 */
	public function add_accesibility_settings() {
		$options = appointments_get_options();
		$class = 'yes' != $options["login_required"] ? 'hidden' : '';
		$value = $options["google-client_id"];
		?>
		<div id="google-login" class="<?php echo $class; ?>">
			<h3><?php esc_html_e( 'Login with Google', 'appointments' ); ?></h3>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="google-client_id">
							<?php _e( 'Google Client ID', 'appointments' ) ?>
						</label>
					</th>
					<td>
						<input type="text" class="widefat" name="google-client_id" id="google-client_id" value="<?php echo esc_attr( $value ); ?>"/>
						<p class="description">
							<?php printf( __( 'Enter your Google App Client ID here. If you don\'t have a Google App yet, you will need to create one <a href="%s">here</a>', 'appointments' ), 'https://console.developers.google.com/' ); ?>.
							<a class="app-info_trigger" data-target="gauth-instructions" href="#gauth-instructions"><?php _e( 'Show me how', 'appointments' ); ?></a>
						</p>
						<p class="description">
							<?php _e( 'If you leave this field empty, Google Auth will revert to legacy OpenID.', 'appointments' ); ?>
							<b><?php _e( 'The legacy OpenID has been deprecated by Google, and will not work if the domain for your site wasn\'t set up to use it before May 2014.', 'appointments' ); ?></b>
						</p>
						<div id="gauth-instructions" class="description app-info_target gauth-instructions">
							<h4><?php _e( 'Creating and setting up a Google Application to work with Appointments Plus authentication', 'appointments' ); ?></h4>
							<p><?php _e( 'Before we begin, you need to <a target="_blank" href="https://console.developers.google.com/">create a Google Application</a>', 'appointments' ); ?>.</p>
							<p><?php _e( 'To do so, follow these steps:', 'appointments' ); ?></p>
							<ol>
								<li><a target="_blank" href="https://console.developers.google.com/"><?php _e( 'Create your application', 'appointments' ); ?></a>
								</li>
								<li><?php _e( 'Click <em>Create Project</em> button', 'appointments' ); ?></li>
								<li><?php _e( 'In the left sidebar, select <em>APIs & auth</em>.', 'appointments' ); ?></li>
								<li><?php _e( 'Find the <em>Google+ API</em> service and set its status to <em>ON</em>.', 'appointments' ); ?></li>
								<li><?php _e( 'In the sidebar, select <em>Credentials</em>, then in the <em>OAuth</em> section of the page, select <em>Create New Client ID</em>.', 'appointments' ); ?></li>
								<li><?php _e( 'In the <em>Application type</em> section of the dialog, select <em>Web application</em>.', 'appointments' ); ?></li>
								<li><?php _e( 'In the <em>Authorized JavaScript origins</em> field, enter the origin for your app. You can enter multiple origins to allow for your app to run on different protocols, domains, or subdomains.', 'appointments' ); ?></li>
								<li><?php _e( 'In the <em>Authorized redirect URI</em> field, delete the default value.', 'appointments' ); ?></li>
								<li><?php _e( 'Select <em>Create Client ID</em>.', 'appointments' ); ?></li>
								<li><?php _e( 'Copy the value of the field labeled <em>Client ID</em>, and enter it in the text field in plugin settings labeled <strong>Google Client ID</strong>', 'appointments' ); ?></li>
							</ol>
						</div>

					</td>
				</tr>
			</table>
		</div>
		<script>
			jQuery( document ).ready( function( $ ) {
				var loginSettings = $('#google-login');
				$('select[name="login_required"]').change(function () {
					if ($(this).val() == 'yes') {
						loginSettings.removeClass( 'hidden' );
					}
					else {
						loginSettings.addClass( 'hidden' );
					}
				});
			});
		</script>
		<?php
	}

	public function admin_notices() {
		if ( ! current_user_can( 'manage_options'  ) ) {
			return;
		}

		$options = appointments_get_options();
		// Warn if Openid is not loaded
		$dismissed_g = false;
		$dismiss_id_g = get_user_meta( get_current_user_id(), 'app_dismiss_google', true );
		if ( $dismiss_id_g ) {
			$dismissed_g = true;
		}
		if ( @$options['accept_api_logins'] && ! $this->openid && ! $dismissed_g ) {
			echo '<div class="error"><p>' .
			     __('<b>[Appointments+]</b> Either php curl is not installed or HTTPS wrappers are not enabled. Login with Google+ will not work.', 'appointments') .
			     '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss_google=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
			     '</p></div>';
			$r = true;
		}
	}

	/**
	 * Handles the Google+ OAuth type login.
	 */
	function handle_gplus_login () {
		header("Content-type: application/json");
		$options = appointments_get_options();
		$resp = array(
			"status" => 0,
		);
		if ( empty( $options['google-client_id'] ) ) {
			die( json_encode( $resp ) );
		} // Yeah, we're not equipped to deal with this

		$data = stripslashes_deep($_POST);
		$token = ! empty( $data['token'] ) ? $data['token'] : false;
		if ( empty( $token ) ) {
			die( json_encode( $resp ) );
		}

		// Start verifying
		$page = wp_remote_get('https://www.googleapis.com/userinfo/v2/me', array(
			'sslverify' => false,
			'timeout' => 5,
			'headers' => array(
				'Authorization' => sprintf( 'Bearer %s', $token ),
			)
		));

		if ( 200 != wp_remote_retrieve_response_code( $page ) ) {
			die( json_encode( $resp ) );
		}

		$body = wp_remote_retrieve_body($page);
		$response = json_decode( $body, true ); // Body is JSON
		if ( empty( $response['id'] ) ) {
			die( json_encode( $resp ) );
		}

		$first = !empty($response['given_name']) ? $response['given_name'] : '';
		$last = !empty($response['family_name']) ? $response['family_name'] : '';
		$email = !empty($response['email']) ? $response['email'] : '';

		if ( empty( $email ) || ( empty( $first ) && empty( $last ) ) ) {
			die( json_encode( $resp ) );
		} // In case we're missing stuff

		$username = false;
		if ( ! empty( $last ) && ! empty( $first ) ) {
			$username = "{$first}_{$last}";
		} else if ( ! empty( $first ) ) {
			$username = $first;
		} else if ( ! empty( $last ) ) {
			$username = $last;
		}

		if ( empty( $username ) ) {
			die( json_encode( $resp ) );
		} // In case we're missing stuff

		$wordp_user = get_user_by( 'email', $email );

		if ( ! $wordp_user ) { // Not an existing user, let's create a new one
			$password = wp_generate_password( 12, false );
			$count    = 0;
			while ( username_exists( $username ) ) {
				$username .= rand( 0, 9 );
				if ( ++ $count > 10 ) {
					break;
				}
			}

			$wordp_user = wp_create_user( $username, $password, $email );
			if ( is_wp_error( $wordp_user ) ) {
				die( json_encode( $resp ) );
			} // Failure creating user
			else {
				update_user_meta( $wordp_user, 'first_name', $first );
				update_user_meta( $wordp_user, 'last_name', $last );
			}
		} else {
			$wordp_user = $wordp_user->ID;
		}

		$user = get_userdata( $wordp_user );
		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID ); // Logged in with Google, yay
		do_action( 'wp_login', $user->user_login );

		die( json_encode( array(
			"status" => 1,
		) ) );
	}

	public function setup_api_login() {
		$appointments = appointments();

		// Google+ login
		if ( ! class_exists( 'LightOpenID' ) ) {
			if ( function_exists( 'curl_init' ) || in_array( 'https', stream_get_wrappers() ) ) {
				include_once( appointments_plugin_dir() . '/includes/pro/external/lightopenid/openid.php' );
				$this->openid = new LightOpenID;
			}
		} else {
			$this->openid = new LightOpenID;
		}

		if ( @$this->openid ) {

			if ( !session_id() )
				@session_start();

			add_action('wp_ajax_nopriv_app_get_google_auth_url', array($this, 'handle_get_google_auth_url'));
			add_action('wp_ajax_nopriv_app_google_login', array($this, 'handle_google_login'));

			$this->openid->identity = 'https://www.google.com/accounts/o8/id';
			$this->openid->required = array('namePerson/first', 'namePerson/last', 'namePerson/friendly', 'contact/email');
			if (!empty($_REQUEST['openid_ns'])) {
				$cache = $this->openid->getAttributes();
				if (isset($cache['namePerson/first']) || isset($cache['namePerson/last']) || isset($cache['contact/email'])) {
					$_SESSION['app_google_user_cache'] = $cache;
				}
			}
			if ( isset( $_SESSION['app_google_user_cache'] ) )
				$this->_google_user_cache = $_SESSION['app_google_user_cache'];
			else
				$this->_google_user_cache = '';
		}
	}

	/**
	 * Get OAuth request URL and token.
	 */
	function handle_get_google_auth_url () {
		header("Content-type: application/json");

		$this->openid->returnUrl = $_POST['url'];

		/** @var LightOpenID $this->openid */
		echo json_encode(array(
			'url' => $this->openid->authUrl()
		));
		exit();
	}

	/**
	 * Login or create a new user using whatever data we get from Google.
	 */
	function handle_google_login () {
		header("Content-type: application/json");
		$resp = array(
			"status" => 0,
		);

		/** @var LightOpenID $this->openid */
		$cache = $this->openid->getAttributes();

		if (isset($cache['namePerson/first']) || isset($cache['namePerson/last']) || isset($cache['namePerson/friendly']) || isset($cache['contact/email'])) {
			$this->_google_user_cache = $cache;
		}

		// Have user, now register him/her
		if ( isset( $this->_google_user_cache['namePerson/friendly'] ) )
			$username = $this->_google_user_cache['namePerson/friendly'];
		else
			$username = $this->_google_user_cache['namePerson/first'];
		$email = $this->_google_user_cache['contact/email'];
		$wordp_user = get_user_by('email', $email);

		if (!$wordp_user) { // Not an existing user, let's create a new one
			$password = wp_generate_password(12, false);
			$count = 0;
			while (username_exists($username)) {
				$username .= rand(0,9);
				if (++$count > 10) break;
			}

			$wordp_user = wp_create_user($username, $password, $email);
			if (is_wp_error($wordp_user))
				die(json_encode($resp)); // Failure creating user
			else {
				update_user_meta($wordp_user, 'first_name', $this->_google_user_cache['namePerson/first']);
				update_user_meta($wordp_user, 'last_name', $this->_google_user_cache['namePerson/last']);
			}
		}
		else {
			$wordp_user = $wordp_user->ID;
		}

		$user = get_userdata($wordp_user);
		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID); // Logged in with Google, yay
		do_action('wp_login', $user->user_login);

		die(json_encode(array(
			"status" => 1,
		)));
	}

	
}