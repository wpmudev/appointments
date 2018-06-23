<?php
/*
Plugin Name: Limit Services Login
Description: Allows you to choose which social services you allow your users to log in with.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Users
Requires: "Login required" setting to be set to "Yes".
Author: WPMU DEV
*/

class App_Users_LimitServicesLogin {

	private function __construct() {}

	public static function serve() {
		$me = new App_Users_LimitServicesLogin;
		$me->_add_hooks();
	}

	private function _add_hooks() {
		add_filter( 'app-scripts-api_l10n', array( $this, 'inject_scripts' ) );
		add_action( 'appointments_settings_tab-main-section-accesibility', array( $this, 'show_settings' ) );
		add_filter( 'app-options-before_save', array( $this, 'save_settings' ) );
		add_filter( 'appointments_default_options', array( $this, 'default_options' ) );
	}

	public function default_options( $defaults ) {
		$services = $this->_get_services();
		$defaults['show_login_button'] = array_keys( $services );
		$defaults['use_blogname_for_login'] = 0;
		return $defaults;
	}

	public function inject_scripts( $l10n ) {
		$options = appointments_get_options();
		$selected = $options['show_login_button'];
		$l10n['show_login_button'] = $selected;

		if ( ! empty( $l10n['wordpress'] ) && ! empty( $options['use_blogname_for_login'] ) ) {
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$l10n['wordpress'] = sprintf( __( 'Login with %s', 'appointments' ), $blogname );
			if ( ! empty( $l10n['register'] ) ) {
				$l10n['register'] = sprintf( __( 'Register with %s', 'appointments' ), $blogname );
			}
		}
		return $l10n;
	}


	public function save_settings( $options ) {
		if ( ! empty( $_POST['show_login_button'] ) ) {
			$options['show_login_button'] = stripslashes_deep( $_POST['show_login_button'] );
		}
		$options['use_blogname_for_login'] = ! empty( $_POST['use_blogname_for_login'] );
		return $options;
	}

	public function show_settings( $style ) {
		$options = appointments_get_options();
		$services = $this->_get_services();
		$selected = $options['show_login_button'];

		?>
		<div class="api_detail">
			<h2><?php _e( 'Show login buttons', 'appointments' ); ?></h2>
			<table class="form-table">
                        <?php foreach ( $services as $service => $label ) :  ?>
				<tr>
                <th scope="row" ><?php
				printf(
					esc_html__( 'Show %s login button', 'appointments' ),
					$label
				);

?></th>
                    <td colspan="2">
<input type="checkbox" name="show_login_button[]" id="slb-<?php esc_attr_e( $service ); ?>" value="<?php esc_attr_e( $service ); ?>" <?php checked( in_array( $service, $selected ) ); ?> class="switch-button" data-on="<?php esc_attr_e( 'Show', 'appointments' ); ?>" data-off="<?php esc_attr_e( 'Hide', 'appointments' ); ?>" />
					</td>
                </tr>
<?php endforeach; ?>
            </table>

			<h2><?php _e( 'Name for login button', 'appointments' ); ?></h2>
			<table class="form-table">
                <tr>
                    <th scope="row" ><?php printf( __( 'Use %s name for login button', 'appointments' ), ( is_multisite() ? __( 'network', 'appointments' ) : __( 'site', 'appointments' ) ) ); ?></th>
                    <td colspan="2">
						<hr />
						<div class="app-lsl-other_options">
							<label for="app-use_blogname_for_login">
                            <input type="checkbox" name="use_blogname_for_login" id="app-use_blogname_for_login" value="1" <?php checked( $options['use_blogname_for_login'], 1 ); ?> class="switch-button" data-on="<?php esc_attr_e( 'Use', 'appointments' ); ?>" data-off="<?php esc_attr_e( 'Do not use', 'appointments' ); ?>"/>
							</label>
						</div>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	private function _get_services() {
		return array(
			'facebook' => __( 'Facebook', 'appointments' ),
			'twitter' => __( 'Twitter', 'appointments' ),
			'google' => __( 'Google', 'appointments' ),
			'wordpress' => __( 'WordPress', 'appointments' ),
		);
	}
}
App_Users_LimitServicesLogin::serve();
