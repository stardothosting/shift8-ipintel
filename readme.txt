=== Shift8 IP Intel ===
* Contributors: shift8
* Donate link: https://www.shift8web.ca
* Tags: getipintel, ip address, proxy, security, reputation, tor, ip reputation, detect proxy, detect tor, detect vpn, ip security, block ip, block tor, block vpn, block proxy, ip address reputation, getipintel.net, get ip intel, get ip intelligence, ip intel, ip intelligence, ip audit, ip address audit, security audit
* Requires at least: 3.0.1
* Tested up to: 5.2.2
* Stable tag: 1.06
* License: GPLv3
* License URI: http://www.gnu.org/licenses/gpl-3.0.html

Plugin that establishes an IP Address reputation score from [getipintel.net](https://getipintel.net). IP Reputation data is encrypted using OpenSSL and stored in a _SESSION variable. You can read more about how the GetIPIntel service works by reading the [API Documentation](http://getipintel.net/#API). 

== Want to see the plugin in action? ==

You can view two example sites where this plugin is live :

- Example Site 1 : [Wordpress Hosting](https://www.stackstar.com "Wordpress Hosting")
- Example Site 2 : [Web Design in Toronto](https://www.shift8web.ca "Web Design in Toronto")

= Features =

- Encrypted cookie session containing the IP Intel reputation score from https://getipintel.net/

- Flexible yet simple defined rules for actions to be taken based on score thresholds


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/shif8-ipintel` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to the plugin settings page and define your settings
3. Once enabled, the system should trigger for every site visit.

== Frequently Asked Questions ==

= I tested it on myself and its not working for me! =

If you are logged in as an administrator, the entire system ignores your connection. This is by design to avoid accidentally locking yourself out of your own site! To test, try in a different browser or try logging out (but be careful).

== Screenshots ==

1. Admin area 
2. Rules definitions

== Changelog ==

= 1.0 =
* Stable version created

= 1.01 =
* Fixed issue with ajax callback for encryption key regeneration and nonce validation

= 1.02 =
* Added timeout plugin option to wp_remote_get

= 1.03 =
* Removed references to cookies, fixed bug in init when session has to be reset

= 1.04 =
* Disable main functionality when safe mode is active

= 1.05 =
* Improvement to functions obtaining and validating public IP address

= 1.06 =
* Wordpress 5 compatibility
