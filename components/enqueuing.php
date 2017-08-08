<?php

// Load custom CSS and JS
function shift8_ipintel_scripts() {
        // Load google fonts if necessary
        $shift8_ipintel_bar_font = (esc_attr( get_option('shift8_ipintel_bar_font') ) == "Site default font" ? "inherit" : explode(':', esc_attr( get_option('shift8_ipintel_bar_font') ), 2));
        // Force mobile menu if option is enabled
        if (esc_attr( get_option('shift8_ipintel_mobilemode') ) == 'on') {
                $shift8_ipintel_mobileonly_css = "
                        .fn-secondary-nav {
                                display:none !important;
                        }
                        .fn-primary-nav-trigger {
                                display: inline-block !important;
                        }
                ";
        } else {
                $shift8_ipintel_mobileonly_css = null;
		$shift8_ipintel_mobilebreak = ( esc_attr( get_option('shift8_ipintel_mobilebreak') ) ? esc_attr( get_option('shift8_ipintel_mobilebreak') ) : '980');
        }
}

// Register admin scripts for custom fields
function load_shift8_ipintel_wp_admin_style() {
        // admin always last
        wp_enqueue_style( 'shift8_ipintel_css', plugin_dir_url(dirname(__FILE__)) . 'css/shift8_ipintel_admin.css' );
        wp_enqueue_script( 'shift8_ipintel_script', plugin_dir_url(dirname(__FILE__)) . 'js/shift8_ipintel_admin.js' );
        wp_localize_script( 'shift8_ipintel_script', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );  
}
add_action( 'admin_enqueue_scripts', 'load_shift8_ipintel_wp_admin_style' );
