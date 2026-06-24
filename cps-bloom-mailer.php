<?php

/**
 * Plugin Name: Bloom Mailer
 * Plugin URI:  https://chicpixies.com
 * Description: Email marketing for WordPress. Supports Amazon SES and any SMTP provider.
 * Version:     1.0.0
 * Author:      Chicpixies
 * Author URI:  https://chicpixies.com
 * License:     GPL-2.0+
 * Requires Plugins:  cps-bloom
 * Text Domain: cps-bloom-mailer
 */

use ChicpixiesBloomMailer\Mailer;
use ChicpixiesBloomMailer\Installer;

if (! defined('ABSPATH')) {
	exit;
}

define('CPS_BLOOM_MAILER_VERSION', '1.0.0');
define('CPS_BLOOM_MAILER_DIR', plugin_dir_path(__FILE__));
define('CPS_BLOOM_MAILER_URL', plugin_dir_url(__FILE__));

require_once CPS_BLOOM_MAILER_DIR . 'includes/class-mailer.php';

register_activation_hook(__FILE__, array('ChicpixiesBloomMailer\\Mailer', 'activate'));
register_deactivation_hook(__FILE__, array('ChicpixiesBloomMailer\\Mailer', 'deactivate'));

Mailer::instance();

add_action('template_redirect', function() {
    if (strpos($_SERVER['REQUEST_URI'], 'cps-bloom-mailer') !== false) {
        error_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
        error_log('query_var: ' . get_query_var('cps_mailer_action'));
        global $wp_query;
        error_log('WP_Query vars: ' . print_r($wp_query->query_vars, true));
        die('Debug: check error log');
    }
}, 1);