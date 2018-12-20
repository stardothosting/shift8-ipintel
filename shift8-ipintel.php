<?php
/**
 * Plugin Name: Shift8 IP Intel
 * Plugin URI: https://github.com/stardothosting/shift8-ipintel
 * Description: Plugin that establishes an IP Address reputation score from https://getipintel.net. Score is stored in an encrypted session variable.
 * Version: 1.06
 * Author: Shift8 Web 
 * Author URI: https://www.shift8web.ca
 * License: GPLv3
 */

require_once(plugin_dir_path(__FILE__).'components/enqueuing.php' );
require_once(plugin_dir_path(__FILE__).'components/settings.php' );
require_once(plugin_dir_path(__FILE__).'components/functions.php' );

// Admin welcome page
if (!function_exists('shift8_main_page')) {
	function shift8_main_page() {
	?>
	<div class="wrap">
	<h2>Shift8 Plugins</h2>
	Shift8 is a Toronto based web development and design company. We specialize in Wordpress development and love to contribute back to the Wordpress community whenever we can! You can see more about us by visiting <a href="https://www.shift8web.ca" target="_new">our website</a>.
	</div>
	<?php
	}
}

// Admin settings page
function shift8_ipintel_settings_page() {
?>
<div class="wrap">
<h2>Shift8 IP Intel Settings</h2>
<?php if (is_admin()) { ?>
<form method="post" action="options.php">
    <?php settings_fields( 'shift8-ipintel-settings-group' ); ?>
    <?php do_settings_sections( 'shift8-ipintel-settings-group' ); ?>
    <?php
	$locations = get_theme_mod( 'nav_menu_locations' );
	if (!empty($locations)) {
		foreach ($locations as $locationId => $menuValue) {
			if (has_nav_menu($locationId)) {
				$shift8_ipintel_menu = $locationId;
			}
		}
	}
	?>
    <table class="form-table shift8-ipintel-table">
	<tr valign="top">
	<th scope="row">Core Settings</th>
	</tr>
	<tr valign="top">
    <td><span id="shift8-ipintel-notice">
    <?php
    settings_errors('shift8_ipintel_email');
    settings_errors('shift8_ipintel_actionthreshold');
    settings_errors('shift8_ipintel_action');
    settings_errors('shift8_ipintel_action301');
    settings_errors('shift8_ipintel_timeout');
    settings_errors('shift8_ipintel_subdomain');
    ?>
    </span></td>
	</tr>
	<tr valign="top">
	<td>Enable IP Intel : </td>
	<td>
	<?php 
	if (esc_attr( get_option('shift8_ipintel_enabled') ) == 'on') { 
		$enabled_checked = "checked";
	} else {
		$enabled_checked = "";
	}

    // Set encryption key if empty
    if (empty(get_option('shift8_ipintel_encryptionkey') )) {
        $encryption_key = shift8_ipintel_encryption_key();
    } else {
        $encryption_key = esc_attr( get_option('shift8_ipintel_encryptionkey') );
    }

    // Get action threshold number for condition check later
    $action_threshold = esc_attr(get_option('shift8_ipintel_actionthreshold', '0.99'));
	// Get timeout for IP Intel curl
    $ipintel_timeout = esc_attr(get_option('shift8_ipintel_timeout', '5'));
	?>
                <label class="switch">
                <input type="checkbox" name="shift8_ipintel_enabled" <?php echo $enabled_checked; ?>>
                <div class="slider round"></div>
                </label>
	</td>
	</tr>
	<tr valign="top">
        <td>Enable Safe Mode : </td>
        <td>
        <?php
        if (esc_attr( get_option('shift8_ipintel_safemode') ) == 'on') {
                $safemode_enabled_checked = "checked";
        } else {
                $safemode_enabled_checked = "";
        }
        ?>
                <label class="switch">
                <input type="checkbox" name="shift8_ipintel_safemode" <?php echo $safemode_enabled_checked; ?>>
                <div class="slider round"></div>
                </label>
        </td>
	</th>
	</tr>
    <tr valign="top">
    <td>API Contact Email : <td><input type="text" name="shift8_ipintel_email" size="34" value="<?php echo ( empty(esc_attr( get_option('shift8_ipintel_email'))) ? get_option('admin_email') : esc_attr( get_option('shift8_ipintel_email')) ); ?>"></td>
    </tr>
    <tr valign="top">
    <td>Action On Probability : <td><input type="text" name="shift8_ipintel_actionthreshold" size="4" value="<?php echo (is_numeric($action_threshold) && $action_threshold >= 0 && $action_threshold <= 1 ? $action_threshold : '0.99'); ?>"></td>
    </tr>
    <tr valign="top">
    <td>Action to take : <td><input type="radio" id="action-403" name="shift8_ipintel_action" value="403" <?php echo (esc_attr(get_option('shift8_ipintel_action')) == "403" ? 'checked="checked"' : null) ?>><label for="action-403">Return a 403 forbidden error</label><br />
    <input type="radio" id="action-301" name="shift8_ipintel_action" value="301" <?php echo (esc_attr(get_option('shift8_ipintel_action')) == "301" ? 'checked="checked"' : null) ?>><label for="action-301">301 Redirect the user to any destination</label>
    </td>
    </tr>
    <tr id="shift8-ipintel-action-row" valign="top" style="display:none;">
    <td>Action 301 redirect destination (url) : <td><input type="text" id="action-301-destination" name="shift8_ipintel_action301" value="<?php echo (empty(esc_attr(get_option('shift8_ipintel_action301'))) ? 'http://www.google.com"' : esc_attr(get_option('shift8_ipintel_action301'))); ?>"></td>
    </tr>
    <tr valign="top">
    <td>Timeout (seconds) for IP Intel Response : <td><input type="text" name="shift8_ipintel_timeout" size="2" value="<?php echo (is_numeric($ipintel_timeout) && $ipintel_timeout >= 1 && $ipintel_timeout <= 5 ? $ipintel_timeout : '5'); ?>"></td>
    </tr>
    <tr valign="top">
    <td>Custom subdomain for IP Intel Request : <td><input type="text" name="shift8_ipintel_subdomain" value="<?php echo (empty(esc_attr(get_option('shift8_ipintel_subdomain'))) ? '' : esc_attr(get_option('shift8_ipintel_subdomain'))); ?>"></td>
    </tr>
	<tr valign="top">
	<td><input size="64" id="shift8-encryption-key" name="shift8_ipintel_encryptionkey" value="<?php echo $encryption_key; ?>" type="hidden"/>
	Encryption Key : <span id="shift8-encryption-hexkey"><?php echo $encryption_key; ?></span></td>
	<td><button id="shift8-key-button">Re-generate Encryption Key</button></td>
	</table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php 
	} // is_admin
}

// add the menu if its switched on
if (esc_attr( get_option('shift8_ipintel_enabled') ) == 'on') {
	add_action('wp_footer', 'add_shift8_ipintel_menu', 1);
}

