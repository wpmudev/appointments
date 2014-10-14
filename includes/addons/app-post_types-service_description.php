<?php
/*
Plugin Name: Service Description post type
Description: Allows you to select a post type for your service descriptions (available under Settings &gt; General &gt; Advanced Settings)
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Post Types
Author: WPMU DEV
*/

class App_PostTypes_ServiceDescription {

	const POST_TYPE = 'page';
	private $_data;

	private function __construct () {}

	public static function serve () {
		$me = new App_PostTypes_ServiceDescription;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));
		add_filter('app-service_description_pages-get_list', array($this, 'get_descriptions'));

		add_action('app-settings-advanced_settings', array($this, 'show_settings'));
		add_filter('app-options-before_save', array($this, 'save_settings'));
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;
	}

	public function get_descriptions () {
		$post_type = $this->_get_post_type();
		$query = new WP_Query(array(
			'post_type' => $post_type,
			'posts_per_page' => -1,
		));
		return $query->posts;
	}

	public function save_settings ($options) {
		if (!empty($_POST['service_description_post_type'])) $options['service_description_post_type'] = $_POST['service_description_post_type'];
		return $options;
	}

	public function show_settings () {
		$post_types = get_post_types(array(
			'public' => true,
		), 'objects');
		$bio = $this->_get_post_type();
		?>
		<tr valign="top">
			<th scope="row" ><?php _e('Service Description post type', 'appointments')?></th>
			<td colspan="2">
				<select name="service_description_post_type">
				<?php foreach ($post_types as $type => $obj) { ?>
					<option value="<?php esc_attr_e($type); ?>" <?php selected($type, $bio); ?> >
						<?php echo $obj->labels->singular_name; ?>
					</option>
				<?php } ?>
				</select>
				<span class="description"><?php _e('This is the post type that will be used as descriptions for your services.', 'appointments') ?></span>
			</td>
		</tr>
		<?php
	}

	private function _get_post_type () {
		return !empty($this->_data['service_description_post_type']) ? $this->_data['service_description_post_type'] : self::POST_TYPE;
	}
}
App_PostTypes_ServiceDescription::serve();