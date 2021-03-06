<?php

// Encryption key generation 
function shift8_ipintel_encryption_key() {
    $cstrong = false;
    $encryption_key = bin2hex(openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'), $cstrong));
    // Fallback if no openssl
    if (!$cstrong) {
        $encryption_key = bin2hex(random_bytes(32));
    }
    return $encryption_key;
}

// Callback for key regeneration
function shift8_ipintel_ajax_process_request() {
    // first check if data is being sent and that it is the data we want
    $check_nonce = check_ajax_referer( 'shift8_ipintel_response_nonce', 'nonce');
    if ( $check_nonce == true) {
        $new_encryption_key = shift8_ipintel_encryption_key();
        echo $new_encryption_key;
        die();
    } else {
        wp_send_json_error( array( 'error' => $custom_error ) );
    }
}
add_action('wp_ajax_shift8_ipintel_response', 'shift8_ipintel_ajax_process_request');

// Function to encrypt session data
function shift8_ipintel_encrypt($key, $payload) {
    if (!empty($key) && !empty($payload)) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($payload, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    } else {
        return false;
    }        
}

// Function to decrypt session data
function shift8_ipintel_decrypt($key, $garble) {
    if (!empty($key) && !empty($garble)) {
        list($encrypted_data, $iv) = explode('::', base64_decode($garble), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    } else {
        return false;
    }       
}

// Function to initialize & check for session 
function shift8_ipintel_init() {
    // Initialize only if enabled
    if (shift8_ipintel_check_options()) {
        // Make sure you dont accidentally catch logged in admin
        if (is_admin() && isset($_SESSION['shift8_ipintel'])) {
            clear_shift8_ipintel_session();
        } else if (!is_admin()) {
            // Get the IP and encryption key once
            $ip_address = shift8_ipintel_get_ip();
            $encryption_key = esc_attr( get_option('shift8_ipintel_encryptionkey'));
            if (!session_id()) {
                session_start();
            }
            // If the session isnt set
            if (!isset($_SESSION['shift8_ipintel']) || empty($_SESSION['shift8_ipintel'])) {
                // Only set the session if a valid IP address was found
                if ($ip_address) {
                    $ip_intel = shift8_ipintel_check($ip_address);
                    $session_data = $ip_address . '_' . $ip_intel . '_' . strtotime( '+1 day' );
                    $session_value = shift8_ipintel_encrypt($encryption_key, $session_data);
                    $_SESSION['shift8_ipintel'] = $session_value;
                }
            } else {
                // if session is set, validate it and remove if not valid
                $session_data = explode('_', shift8_ipintel_decrypt($encryption_key, $_SESSION['shift8_ipintel']));
                // If the ip address doesnt match the encrypted value of the session 
                if ($session_data[0] != $ip_address) {
                    clear_shift8_ipintel_session();
                } else if ($session_data[1] == 'banned') {
                    if (esc_attr(get_option('shift8_ipintel_action')) == '403') {
                        header('HTTP/1.0 403 Forbidden');
                        echo 'Forbidden';
                        die();
                    } else if (esc_attr(get_option('shift8_ipintel_action')) == '301') {
                        header("HTTP/1.1 301 Moved Permanently"); 
                        header("Location: " . esc_attr(get_option('shift8_ipintel_action301')));
                        die();
                    }
                } else if ($session_data[1] == 'error_detected') {
                    // Unset the existing session, re-set it with a shorter expiration time
                    clear_shift8_ipintel_session();
                    // Set the ip address but clear any IP Intel values for now
                    $session_newdata = $session_data[0] . '_ignore_' . strtotime( '+1 hour' );
                    $session_value = shift8_ipintel_encrypt($encryption_key, $session_newdata);
                    // Generally if there is an error detected, its likely because you exceeded the threshold. Wait an hour before doing this process again
                    $_SESSION['shift8_ipintel'] = $session_value;
                }
            }
        }
    } 
}
add_action('init', 'shift8_ipintel_init', 1);

// Common function to clear the session 
function clear_shift8_ipintel_session() {
    unset($_SESSION['shift8_ipintel']);
}

function shift8_ipintel_check($ip){
        $contact_email = esc_attr( get_option('shift8_ipintel_email'));
        $timeout = esc_attr(get_option('shift8_ipintel_timeout')); //by default, wait no longer than 5 secs for a response
        $ban_threshold = esc_attr(get_option('shift8_ipintel_actionthreshold')); //if getIPIntel returns a value higher than this, function returns true, set to 0.99 by default
        if (empty(esc_attr(get_option('shift8_ipintel_subdomain')))) { 
            $getipintel_url = 'check.getipintel.net';
        } else {
            $getipintel_url = esc_attr(get_option('shift8_ipintel_subdomain')) . '.getipintel.net';
        }
        
        $response = wp_remote_get( "http://" . $getipintel_url . "/check.php?ip=$ip&contact=$contact_email", 
            array(
                'httpversion' => '1.1',
                'timeout' => $timeout,
            )
        );

        if (is_array($response) && !empty($response['body'])) {
            if ($response['body'] > $ban_threshold) {
                return 'banned';
            } else {
                if ($response['body'] < 0 || strcmp($response['body'], "") == 0 ) {
                    // Return with encrypted error flag
                    return 'error_detected';
                }
                    return 'valid';
            }
        } else { 
            return 'error_detected';
        }
}

// Function to get public IP of end-user
function shift8_ipintel_get_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                // trim for safety measures
                $ip = trim($ip);
                // attempt to validate IP
                if (shift8_ipintel_validate_ip($ip)) {
                    return $ip;
                }
            }
        }
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
}

function shift8_ipintel_validate_ip($ip)
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return false;
    }
    return true;
}


function shift8_ipintel_check_options() {

    // If enabled is not set 
    if (empty(esc_attr(get_option('shift8_ipintel_timeout', '5')))) return false;
    // If timeout is not set
    if (empty(esc_attr(get_option('shift8_ipintel_timeout', '5')))) return false;
    // If safe mode is enabled
    if (esc_attr( get_option('shift8_ipintel_safemode') ) == 'on') return false;
    // If there's no encryption key set
    if (empty(get_option('shift8_ipintel_encryptionkey'))) return false;
    // If there's no action threshold set
    if (!is_numeric(esc_attr(get_option('shift8_ipintel_actionthreshold'))) || (esc_attr(get_option('shift8_ipintel_actionthreshold')) != 0 && esc_attr(get_option('shift8_ipintel_actionthreshold')) > 1)) return false;
    // If there's no email defined
    if (empty(esc_attr( get_option('shift8_ipintel_email')))) return false;
    // If theres no action defined
    if (empty(esc_attr(get_option('shift8_ipintel_action')))) return false;
    // If action is not empty and its a 301 action
    if (!empty(esc_attr(get_option('shift_ipintel_action'))) && esc_attr(get_option('shift_ipintel_action')) == '301') 
        if (empty(esc_attr(get_option('shift8_ipintel_action301')))) 
            return false;
    // If theres no timeout defined
    if(!is_numeric(esc_attr(get_option('shift8_ipintel_timeout'))) && !(esc_attr(get_option('shift8_ipintel_timeout')) <= 5)) return false;

    // If none of the above conditions match, return true
    return true;
}

