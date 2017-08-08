<?php

// create custom plugin settings menu
add_action('admin_menu', 'shift8_ipintel_create_menu');
function shift8_ipintel_create_menu() {
        //create new top-level menu
        if ( empty ( $GLOBALS['admin_page_hooks']['shift8-settings'] ) ) {
                add_menu_page('Shift8 Settings', 'Shift8', 'administrator', 'shift8-settings', 'shift8_main_page' , 'dashicons-building' );
        }
        add_submenu_page('shift8-settings', 'IP Intel Settings', 'IP Intel Settings', 'manage_options', __FILE__.'/custom', 'shift8_ipintel_settings_page');
        //call register settings function
        add_action( 'admin_init', 'register_shift8_ipintel_settings' );
}

// Register admin settings
function register_shift8_ipintel_settings() {
    //register our settings
    register_setting( 'shift8-ipintel-settings-group', 'shift8_ipintel_enabled' );
    register_setting( 'shift8-ipintel-settings-group', 'shift8_ipintel_safemode' );
    register_setting( 'shift8-ipintel-settings-group', 'shift8_ipintel_email', 'shift8_ipintel_email_validate' );
    register_setting( 'shift8-ipintel-settings-group', 'shift8_ipintel_encryptionkey' );
    register_setting( 'shift8-ipintel-settings-group', 'shift8_ipintel_actionthreshold', 'shift8_ipintel_actionthreshold_validate' );
    register_setting( 'shift8-ipintel-settings-group', 'shift8_ipintel_action', 'shift8_ipintel_action_validate' );
    register_setting( 'shift8-ipintel-settings-group', 'shift8_ipintel_action301', 'shift8_ipintel_action301_validate' );
    register_setting( 'shift8-ipintel-settings-group', 'shift8_ipintel_timeout', 'shift8_ipintel_timeout_validate' );
}

// Functions to validate settings prior to saving
function shift8_ipintel_email_validate($data) {
    if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
		return sanitize_email($data);
    } else {
        add_settings_error(
            'shift8_ipintel_email',
            'shift8-ipintel-notice',
            'For API Email variable, please enter a valid email address for the API Contact Email field',
            'error');
	}
}

function shift8_ipintel_actionthreshold_validate($data) {
	if (is_numeric($data) && $data >= 0 && $data <= 1) {
        return esc_attr($data);
    } else {
        add_settings_error(
            'shift8_ipintel_actionthreshold',
            'shift8-ipintel-notice',
            'For action threshold variable, please enter a valid number between 0.00000 and 1',
            'error');
    }
}

function shift8_ipintel_action_validate($data) {
	if ($data == "301" || $data == "403") {
		return esc_attr($data);
	} else {
        add_settings_error(
            'shift8_ipintel_action',
            'shift8-ipintel-notice',
            'For the action selection, something was wrong with the setting selected. Please select and try re-saving',
            'error');
    }
}

function shift8_ipintel_action301_validate($data) {
	if (filter_var($data, FILTER_VALIDATE_URL)) {
		return esc_url($data);
	} else {
        add_settings_error(
            'shift8_ipintel_action301',
            'shift8-ipintel-notice',
            'For 301 destination URL, please enter a valid URL',
            'error');
    }

}

function shift8_ipintel_timeout_validate($data) {
    if (is_numeric($data) && $data >= 1 && $data <= 5) {
        return esc_attr($data);
    } else {
        add_settings_error(
            'shift8_ipintel_timeout',
            'shift8-ipintel-notice',
            'For timeout variable, please enter a valid number between 1 and 5',
            'error');
    } 

}
