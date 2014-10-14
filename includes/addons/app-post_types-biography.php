<?php
/*
Plugin Name: Biography post type
Description: Allows you to select a post type for your service providers biographies (available under Settings &gt; General &gt; Advanced Settings)
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Post Types
Author: WPMU DEV
*/

class App_PostTypes_Biography {

	const POST_TYPE = 'page';
	private $_data;

	private function __construct () {}

	public static function serve () {
		$me = new App_PostTypes_Biography;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));
		add_filter('app-biography_pages-get_list', array($this, 'get_biographies'));

		add_action('app-settings-advanced_settings', array($this, 'show_settings'));
		add_filter('app-options-before_save', array($this, 'save_settings'));
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;
	}

	public function get_biographies () {
		$post_type = $this->_get_post_type();
		$query = new WP_Query(array(
			'post_type' => $post_type,
			'posts_per_page' => -1,
		));
		return $query->posts;
	}

	public function save_settings ($options) {
		if (!empty($_POST['biography_post_type'])) $options['biography_post_type'] = $_POST['biography_post_type'];
		return $options;
	}

	public function show_settings () {
		$post_types = get_post_types(array(
			'public' => true,
		), 'objects');
		$bio = $this->_get_post_type();
		?>
		<tr valign="top">
			<th scope="row" ><?php _e('Biography post type', 'appointments')?></th>
			<td colspan="2">
				<select name="biography_post_type">
				<?php foreach ($post_types as $type => $obj) { ?>
					<option value="<?php esc_attr_e($type); ?>" <?php selected($type, $bio); ?> >
						<?php echo $obj->labels->singular_name; ?>
					</option>
				<?php } ?>
				</select>
				<span class="description"><?php _e('This is the post type that will be used as biographies for your service providers.', 'appointments') ?></span>
			</td>
		</tr>
		<?php
	}

	private function _get_post_type () {
		return !empty($this->_data['biography_post_type']) ? $this->_data['biography_post_type'] : self::POST_TYPE;
	}
}
App_PostTypes_Biography::serve();