<?php
/*
Plugin Name: Additional fields
Description: Allows you to require additional fields to be entered by your users.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Users
Author: WPMU DEV
*/

class App_Users_AdditionalFields {

	private $_data;
	/** @var  Appointments $_core */
	private $_core;

	private function __construct () {}

	public static function serve () {
		$me = new App_Users_AdditionalFields;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('plugins_loaded', array($this, 'initialize'));

		// Settings
		add_action('app-settings-display_settings', array($this, 'show_settings'));
		add_filter('app-options-before_save', array($this, 'save_settings'));

		// Field injection
		add_filter('app_additional_fields', array($this, 'inject_additional_fields'));
		add_filter('app-footer_scripts-after', array($this, 'inject_additional_fields_script'));
		add_filter('app_get_field_name', array($this, 'field_names'));

		// Values processing
		add_action('app-additional_fields-validate', array($this, 'validate_submitted_fields'));
		add_action('app_new_appointment', array($this, 'save_submitted_fields'));

		// Auto-cleanup
		add_action('app_remove_expired', array($this, 'cleanup_data'));
		add_action('app_remove_pending', array($this, 'cleanup_data'));
		// Manual cleanup
		add_action('wpmudev_appointments_update_appointment_status', array($this, 'manual_cleanup_data'), 10, 2);
		add_action('app_bulk_status_change', array($this, 'bulk_cleanup_data'));
		// Delete filters
		add_action('app_deleted', array($this, 'permanently_deleted_cleanup'));

		// Display additional notes
		add_filter('app-appointments_list-edit-client', array($this, 'display_inline_data'), 10, 2);
		add_action('app-appointment-inline_edit-before_response', array($this, 'save_admin_submitted_data'), 10, 2);

		// Email filters
		add_filter('app_notification_message', array($this, 'expand_email_macros'), 10, 3);
		add_filter('app_confirmation_message', array($this, 'expand_email_macros'), 10, 3);
		add_filter('app_reminder_message', array($this, 'expand_email_macros'), 10, 3);
		add_filter('app_removal_notification_message', array($this, 'expand_email_macros'), 10, 3);

		// GCal expansion filters
		add_filter('app-gcal-set_summary', array($this, 'expand_gcal_macros'), 10, 2);
		add_filter('app-gcal-set_description', array($this, 'expand_gcal_macros'), 10, 2);

		// Export filtering
		add_filter('app-export-columns', array($this, 'inject_additional_columns'));
		add_filter('app-export-appointment', array($this, 'inject_additional_properties'));

		// General fields substitution
		add_filter('app-internal-additional_fields-expand', array($this, 'expand_general_fields'), 10, 2);
	}

	public function inject_additional_columns ($cols) {
		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		if (empty($fields)) return $cols;

		foreach ($fields as $field) {
			$name = 'field_' .
				preg_replace('/[^_a-z]/i', '', $field['label']) .
				preg_replace('/[^0-9]/', '', $field['label'])
			;
			$cols[] = $name;
		}

		return $cols;
	}

	public function inject_additional_properties ($app) {
		if (empty($app['ID'])) return $app;

		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		if (empty($fields)) return $app;

		$app_meta = $this->_get_appointment_meta($app['ID']);
		if (empty($app_meta)) return $app;

		foreach ($fields as $field) {
			$label = esc_html($field['label']);
			$name = $this->_to_clean_name($label);
			$key = strtolower('field_' .
				preg_replace('/[^_a-z]/i', '', $label) .
				preg_replace('/[^0-9]/', '', $label)
			);
			$value = !empty($app_meta[$name]) ? esc_html($app_meta[$name]) : '';
			if (empty($app[$key])) $app[$key] = $value;
		}

		return $app;
	}

	public function expand_general_fields ($text, $app_id) {
		if (empty($text) || empty($app_id) || !is_numeric($app_id)) return $text;
		return $this->expand_email_macros($text, null, $app_id);
	}

	public function expand_gcal_macros ($body, $app) {
		$app_id = !empty($app->ID) ? $app->ID : false;
		if (empty($app_id)) return $body;

		return $this->expand_email_macros($body, $app, $app_id);
	}

	public function expand_email_macros ($body, $app, $app_id) {
		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		if (empty($fields)) return $body;

		$app_meta = $this->_get_appointment_meta($app_id);
		if (empty($app_meta)) return $body;

		foreach ($fields as $field) {
			$label = esc_html($field['label']);
			$name = $this->_to_clean_name($field['label']);
			$macro = $this->_to_email_macro($field['label']);
			$value = !empty($app_meta[$name]) ? esc_html($app_meta[$name]) : '';

			$value = apply_filters('app-additional_fields-field_value', $value, $field, $app);

			$body = preg_replace('/\b' . preg_quote($macro, '/') . '\b/U', $value, $body);
		}
		return $body;
	}

	public function display_inline_data ($form, $app) {
		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		if (empty($fields)) return $form;

		$is_editable = !empty($this->_data['additional_fields-admin_edit']);

		$app_meta = $this->_get_appointment_meta($app->ID);
		if (!$is_editable && empty($app_meta)) return $form;

		$disabled = $is_editable
			? ''
			: "disabled='disabled'"
		;

		foreach ($fields as $field) {
			$label = esc_html($field['label']);
			$name = $this->_to_clean_name($field['label']);
			$value = !empty($app_meta[$name]) ? esc_attr($app_meta[$name]) : '';

			$form .= '<label>' . 
				"<span class='title'>{$label}</span>" .
				"<span class='input-text-wrap'><input type='text' class='widefat appointments-field-entry' data-name='{$name}' {$disabled} value='{$value}' /></span>" .
			'</label>';
		}
		if (!$is_editable) return $form;

		$form .=<<<EO_ADMIN_JS
<script>
(function ($) {
	$.ajaxSetup({
		beforeSend: function (jqxhr, settings) {
			if (!(settings && "data" in settings && settings.data.match(/action=inline_edit_save/))) return;
			var matches = settings.data.match(/\bapp_id=(\d+)/),
				app_id = matches && matches.length ? matches[1] : false,
				root = app_id ? $(':hidden[name="app_id"][value="' + app_id + '"]').closest("tr") : $("body"),
				fields = root.find(".appointments-field-entry")
			;
			fields.each(function () {
				var me = $(this),
					name = me.attr("data-name"),
					value = me.is(":checkbox") ? (me.is(":checked") ? 1 : 0) : me.val()
				;
				settings.data += '&' + encodeURIComponent(name) + '=' + encodeURIComponent(value);
			});
		}
	});
})(jQuery);
</script>
EO_ADMIN_JS;
		return $form;
	}

	public function save_admin_submitted_data ($app_id, $data) {
		if (empty($this->_data['additional_fields-admin_edit'])) return false;
		if (empty($app_id) && !empty($data['ID']) && is_numeric($data['ID'])) $app_id = (int)$data['ID'];
		$this->validate_submitted_fields();
		if ($this->save_submitted_fields($app_id)) {
			// Okay, so we saved the data...
			// ... now let's say so!
			add_filter('app-appointment-inline_edit-result', array($this, 'mark_successful_save'));
		}
	}

	public function mark_successful_save ($result) {
		if (!empty($result['message'])) $result['message'] = __('<span style="color:green;font-weight:bold">Changes saved.</span>', 'appointments');
		return $result;
	}

	public function bulk_cleanup_data ($app_ids) {
		$status = !empty($_POST["app_new_status"]) ? $_POST['app_new_status'] : false;
		if ('removed' != $status) return false;
		$app_ids = !empty($app_ids) && is_array($app_ids) ? $app_ids : array();
		foreach ($app_ids as $app_id) {
			$app_id = (int)$app_id;
			if (!$app_id) continue;
			$this->cleanup_data(appointments_get_appointment($app_id));
		}
	}

	public function permanently_deleted_cleanup ($app_ids) {
		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		if (empty($fields)) return false;
		$app_ids = !empty($app_ids) && is_array($app_ids) ? $app_ids : array();
		foreach ($app_ids as $app_id) {
			$app_id = (int)$app_id;
			if (!$app_id) continue;
			$this->_remove_appointment_meta($app_id);
		}
	}

	public function manual_cleanup_data ($app_id, $new_status) {
		if ('removed' != $new_status) return false;
		$this->cleanup_data(appointments_get_appointment($app_id));
	}

	public function cleanup_data ($app) {
		$cleanup = !isset($this->_data['additional_fields-cleanup']) || !empty($this->_data['additional_fields-cleanup']);
		if (!$cleanup) return false;
		
		$status = !empty($app->status) ? $app->status : '';
		if ('removed' != $status) return false;

		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		if (empty($fields)) return false;
		if (!empty($app->ID)) $this->_remove_appointment_meta($app->ID);
	}

	public function field_names ($map) {
		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		if (empty($fields)) return $map;

		foreach ($fields as $field) {
			$name = $this->_to_clean_name($field['label']);
			$map[$name] = $field['label'];
		}
		return $map;
	}

	public function validate_submitted_fields () {
		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		if (empty($fields)) return false;

		foreach ($fields as $field) {
			if (empty($field['required'])) continue;
			$name = $this->_to_clean_name($field['label']);
			if (empty($_POST[$name])) $this->_core->json_die($name);
		}
	}

	public function save_submitted_fields ($appointment_id) {
		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		if (empty($fields)) return false;

		$data = array();
		$raw = stripslashes_deep($_POST);

		foreach ($fields as $field) {
			$name = $this->_to_clean_name($field['label']);
			$data[$name] = !empty($raw[$name]) ? wp_strip_all_tags(rawurldecode($raw[$name])) : '';
		}
		//$data['__fields__'] = $fields;

		return $this->_add_appointment_meta($appointment_id, $data);

	}

    public function inject_additional_fields ($form) {
        global $current_user;

        $fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
        if (empty($fields)) return $form;

        foreach ($fields as $field) {
            $label = esc_html($field['label']);
            $clean = $this->_to_clean_name($field['label']);
            $id = "appointments-{$clean}" . md5(serialize($field));
            $type = esc_attr($field['type']);
            $user_meta_value = get_user_meta( $current_user->ID, 'app_' . $clean, true );
            $value = $user_meta_value ? $user_meta_value : ('checkbox' == $type ? 1 : '');
            $form .= "<div class='appointments-field appointments-{$clean}-field'>" .
                '<label for="' . $id . '"><span>' . $label . '</span></label>' .
                "<input type='{$type}' id='{$id}' class='appointments-field-entry appointments-{$clean}-field-entry' data-name='{$clean}' value='{$value}' />" .
                "</div>";
        }
        return $form;
    }

	public function inject_additional_fields_script () {
		if (empty($this->_data['additional_fields'])) return false;
		?>
<script type="text/javascript">
(function ($) {
$(document).ajaxSend(function(e, xhr, opts) {
	if (!opts.data) return true;
	if (!opts.data.match(/action=post_confirmation/)) return true;
	
	var $fields = $(".appointments-field-entry");
	$fields.each(function () {
		var $me = $(this),
			name = $me.attr("data-name"),
			value = $me.is(":checkbox") ? ($me.is(":checked") ? 1 : 0) : $me.val()
		;
		opts.data += '&' + encodeURIComponent(name) + '=' + encodeURIComponent(value);
	});
});
})(jQuery);
</script>
		<?php
	}

	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;
		$this->_core = $appointments;
	}

	public function save_settings ($options) {
		if (isset($_POST['app-additional_fields-cleanup'])) $options['additional_fields-cleanup'] = (int)$_POST['app-additional_fields-cleanup'];
		if (isset($_POST['app-additional_fields-admin_edit'])) $options['additional_fields-admin_edit'] = (int)$_POST['app-additional_fields-admin_edit'];
		//if (empty($_POST['app-additional_fields'])) return $options; // Allow additional fields cleaning up
		if (empty($_POST['app-additional_fields'])) $_POST['app-additional_fields'] = array();
		$data = stripslashes_deep($_POST['app-additional_fields']);
		$options['additional_fields'] = array();
		foreach ($data as $field) {
			if (!$field) continue;
			$field = json_decode(rawurldecode($field), true);
			$options['additional_fields'][] = $field;
		}
		return $options;
	}

	public function show_settings ($style) {
		wp_enqueue_script('underscore');
		$_types = array(
			'text' => __('Text', 'appointments'),
			'checkbox' => __('Checkbox', 'appointments'),
		);
		$fields = !empty($this->_data['additional_fields']) ? $this->_data['additional_fields'] : array();
		$cleanup = !isset($this->_data['additional_fields-cleanup']) || !empty($this->_data['additional_fields-cleanup']) ? 'checked="checked"' : '';
		$admin_edit = !empty($this->_data['additional_fields-admin_edit']) ? 'checked="checked"' : '';
		?>
<tr valign="top" class="api_detail" <?php echo $style?>>
	<th scope="row" ><?php _e('Additional fields', 'appointments')?></th>
	<td colspan="2">
		<div id="app-additional_fields-settings">
			<p>
				<label for="app-additional_fields-cleanup">
					<input type="hidden" name="app-additional_fields-cleanup" value="" />
					<input type="checkbox" name="app-additional_fields-cleanup" id="app-additional_fields-cleanup" value="1" <?php echo $cleanup; ?> />
					<?php echo esc_html(__('Cleanup saved data for removed appointments', 'appointments')); ?>
				</label>
				<br />
				<span class="description"><?php _e('This setting controls whether your additional fields data will be kept around when appointments change state to &quot;removed&quot;', 'appointments'); ?></span>
			</p>
			<p>
				<label for="app-additional_fields-admin_edit">
					<input type="hidden" name="app-additional_fields-admin_edit" value="" />
					<input type="checkbox" name="app-additional_fields-admin_edit" id="app-additional_fields-admin_edit" value="1" <?php echo $admin_edit; ?> />
					<?php echo esc_html(__('Allow admin-side fields editing', 'appointments')); ?>
				</label>
				<br />
				<span class="description"><?php _e('Enabling this setting will allow you to manually add/edit additional fields for your appointments.', 'appointments'); ?></span>
			</p>
		</div>
		<div id="app-additional_fields">
		<?php foreach ($fields as $field) { ?>
			<div class="app-field">
				<b><?php echo esc_html($field['label']); ?></b> <em><small>(<?php echo esc_html($field['type']); ?>)</small></em>
				<br />
				<?php echo esc_html('Required', 'appointments'); ?>: <b><?php echo esc_html(($field['required'] ? __('Yes', 'appointments') : __('No', 'appointments'))); ?></b>
				<br />
				<?php _e('E-mail macro:', 'appointments'); ?> <code><?php echo esc_html($this->_to_email_macro($field['label'])); ?></code>
				<span class="description"><?php _e('This is the placeholder you can use in your emails.', 'appointments'); ?></span>
				<input type="hidden" name="app-additional_fields[]" value="<?php echo rawurlencode(json_encode($field)); ?>" />
				<a href="#remove" class="app-additional_fields-remove"><?php echo esc_html('Remove', 'appointments'); ?></a>
			</div>
		<?php } ?>
		</div>
		<div id="app-new_additional_field">
			<h4><?php _e('Add new field', 'appointments'); ?></h4>
			<label for="app-new_additional_field-label">
				<?php _e('Field label:', 'appointments'); ?>
				<input type="text" value="" id="app-new_additional_field-label" />
			</label>
			<label for="app-new_additional_field-type">
				<?php _e('Field type:', 'appointments'); ?>
				<select id="app-new_additional_field-type">
				<?php foreach ($_types as $type => $label) { ?>
					<option value="<?php esc_attr_e($type); ?>"><?php echo esc_html($label); ?></option>
				<?php } ?>
				</select>
			</label>
			<label for="app-new_additional_field-required">
				<input type="checkbox" value="" id="app-new_additional_field-required" />
				<?php _e('Required?', 'appointments'); ?>
			</label>
			<button type="button" class="button-secondary" id="app-new_additional_field-add"><?php _e('Add', 'appointments'); ?></button>
		</div>
	</td>
</tr>
<script id="app-additional_fields-template" type="text/template">
	<div class="app-field">
		<b>{{ label }}</b> <em><small>({{ type }})</small></em>
		<br />
		<?php echo esc_html('Required', 'appointments'); ?>: <b>{{ required ? '<?php echo esc_js(__("Yes", "appointments")); ?>' : '<?php echo esc_js(__("No", "appointments")); ?>' }}</b>
		<input type="hidden" name="app-additional_fields[]" value="{{ encodeURIComponent(_value) }}" />
		<a href="#remove" class="app-additional_fields-remove"><?php echo esc_html('Remove', 'appointments'); ?></a>
	</div>
</script>
<script>
(function ($) {

var tpl = $("#app-additional_fields-template").html();

function parse_template (str, data) {
	var orig_settings = _.templateSettings,
		t = false
	;
	_.templateSettings = {
		evaluate : /\{\[([\s\S]+?)\]\}/g,
		interpolate : /\{\{([\s\S]+?)\}\}/g
	};

	t = _.template(str, data);

	_.templateSettings = orig_settings;

	return t;
}

function add_new_field () {
	var $new_fields = $("#app-new_additional_field").find("input,select"),
		$root = $("#app-additional_fields"),
		data = {}
	;
	$new_fields.each(function () {
		var $me = $(this),
			name = $me.attr("id").replace(/app-new_additional_field-/, ''),
			value = $me.is(":checkbox") ? $me.is(":checked") : $me.val()
		;
		data[name] = value;
	});
	data._value = JSON.stringify(data);
	$root.append(parse_template(tpl, data));
	return false;
}

function remove_field () {
	var $me = $(this);
	$me.closest(".app-field").remove();
	return false;
}

$(function () {
	$(document).on("click", "#app-new_additional_field-add", add_new_field);
	$(document).on("click", ".app-additional_fields-remove", remove_field);
});

})(jQuery);
</script>
<style>
.app-field {
	border: 1px solid #ccc;
	border-radius: 3px;
	padding: 1em;
	margin-bottom: 1em;
	width: 40%;
}
.app-field .app-additional_fields-remove {
	display: block;
	float: right;
}
</style>
		<?php
	}

	private function _to_clean_name ($label) {
		$clean = preg_replace('/[^-_a-z0-9]/', '', strtolower($label));
		if (empty($clean)) $clean = substr(md5($label), 0, 8);
		return $clean;
	}

	private function _to_email_macro ($label) {
		return 'FIELD_' . strtoupper($this->_to_clean_name($label));
	}

	private function _add_appointment_meta ($appointment_id, $data) {
		$appointments_data = get_option('appointments_data', array());
		if (!empty($appointment_id)) $appointments_data[$appointment_id] = $data;
		return update_option("appointments_data", $appointments_data);
	}

	private function _remove_appointment_meta ($appointment_id) {
		$appointments_data = get_option('appointments_data', array());
		if (!empty($appointment_id) && !empty($appointments_data[$appointment_id])) unset($appointments_data[$appointment_id]);
		update_option("appointments_data", $appointments_data);
	}

	private function _get_appointment_meta ($appointment_id) {
		$appointments_data = get_option('appointments_data', array());
		return empty($appointments_data[$appointment_id])
			? array()
			: $appointments_data[$appointment_id]
		;
	}

}
App_Users_AdditionalFields::serve();

if (!function_exists('app_additional_fields_expand')) {
	function app_additional_fields_expand ($text, $app_id) {
		return !empty($app_id)
			? apply_filters('app-internal-additional_fields-expand', $text, $app_id)
			: $text
		;
	}
}