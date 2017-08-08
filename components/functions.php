<?php

// Load curl emulator library
require_once(plugin_dir_path(__FILE__).'includes/libcurlemu/libcurlemu.inc.php');

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
	if ( isset( $_POST["gen_key"] ) ) {
		$new_encryption_key = shift8_ipintel_encryption_key();
		echo $new_encryption_key;
		die();
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

// Function to initialize & check for cookie
function shift8_ipintel_init() {
    // Initialize only if enabled
    if (shift8_ipintel_check_options()) {
    	// Make sure you dont accidentally catch logged in admin
    	if (is_admin() && isset($_COOKIE['shift8_ipintel'])) {
    		clear_shift8_ipintel_cookie();
        } else {
            // Get the IP and encryption key once
            $ip_address = shift8_ipintel_get_ip();
            $encryption_key = esc_attr( get_option('shift8_ipintel_encryptionkey'));

    		// If the cookie isnt set
    		if (!isset($_COOKIE['shift8_ipintel'])) {
                $ip_intel = shift8_ipintel_check($ip_address);
                $cookie_data = $ip_address . '|' . $ip_intel;
    			$cookie_value = shift8_ipintel_encrypt($encryption_key, $cookie_data);
    			setcookie( 'shift8_ipintel', $cookie_value, strtotime( '+1 day' ), '/', false);
    		} else {
    			// if cookie is set, validate it and remove if not valid
    			$cookie_data = explode('|', shift8_ipintel_decrypt($encryption_key, $_COOKIE['shift8_ipintel']));
                // If the ip address doesnt match the encrypted value of the cookie
    			if ($cookie_data[0] != $ip_address) {
    				clear_shift8_ipintel_cookie();
    			}
                // Do ELSE logic here for action
    		}
    	}
    }
}
add_action('init', 'shift8_ipintel_init');

// Common function to clear the gga media cookie
function clear_shift8_ipintel_cookie() {
        unset($_COOKIE['shift8_ipintel']);
        setcookie('shift8_ipintel', '',  time()-3600, '/');
}

function shift8_ipintel_check($ip){
		$contact_email = esc_attr( get_option('shift8_ipintel_email'));
		$timeout = esc_attr(get_option('shift8_ipintel_actionthreshold')); //by default, wait no longer than 5 secs for a response
		$banOnProability = esc_attr(get_option('shift8_ipintel_timeout')); //if getIPIntel returns a value higher than this, function returns true, set to 0.99 by default
		
		//init and set cURL options
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		//if you're using custom flags (like flags=m), change the URL below
		curl_setopt($ch, CURLOPT_URL, "http://check.getipintel.net/check.php?ip=$ip&contact=$contact_email&format=json");
		$response=curl_exec($ch);
		
		curl_close($ch);
		
		
		if ($response > $banOnProability) {
				return true;
		} else {
			if ($response < 0 || strcmp($response, "") == 0 ) {
				//The server returned an error, you might want to do something
				//like write to a log file or email yourself
				//This could be true due to an invalid input or you've exceeded
				//the number of allowed queries. Figure out why this is happening
				//because you aren't protected by the system anymore
				//Leaving this section blank is dangerous because you assume
				//that you're still protected, which is incorrect
				//and you might think GetIPIntel isn't accurate anymore
				//which is also incorrect.
				//failure to implement error handling is bad for the both of us
			}
				return false;
		}
}
/*$ip=shift8_ipintel_get_ip();
if (shift8_ipintel_check($ip)) {
	echo "It appears you're a Proxy / VPN / bad IP, please contact [put something here] for more information <br />";
}*/

function shift8_ipintel_get_ip() {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
	    $ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
	    $ip = $_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}

function shift8_ipintel_check_options() {

    // If enabled is not set 
    if (empty(esc_attr(get_option('shift8_ipintel_timeout', '5')))) return false;
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

