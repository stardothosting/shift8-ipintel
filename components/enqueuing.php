<?php

// Register admin scripts for custom fields
function load_shift8_ipintel_wp_admin_style() {
        // admin always last
        wp_enqueue_style( 'shift8_ipintel_css', plugin_dir_url(dirname(__FILE__)) . 'css/shift8_ipintel_admin.css' );
        wp_enqueue_script( 'shift8_ipintel_script', plugin_dir_url(dirname(__FILE__)) . 'js/shift8_ipintel_admin.js' );
        wp_localize_script( 'shift8_ipintel_script', 'the_ajax_script', array( 
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( "shift8_ipintel_response_nonce"),
        ));  
}
add_action( 'admin_enqueue_scripts', 'load_shift8_ipintel_wp_admin_style' );
