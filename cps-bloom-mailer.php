<?php

/**
 * Plugin Name: Bloom Mailer
 * Plugin URI:  https://chicpixies.com
 * Description: Email marketing for WordPress. Supports Amazon SES and any SMTP provider Requires the Bloom by Chicpixies plugin.
 * Version:     1.0.0
 * Author:      Chicpixies
 * Author URI:  https://chicpixies.com
 * License:     GPL-2.0+
 * Requires Plugins:  cps-bloom
 * Text Domain: cps-bloom-mailer
 */

use ChicpixiesBloomMailer\Plugin;

if (! defined('ABSPATH')) {
	exit;
}

define('CPS_BLOOM_MAILER_VERSION', '1.0.0');
define('CPS_BLOOM_MAILER_DIR', plugin_dir_path(__FILE__));
define('CPS_BLOOM_MAILER_URL', plugin_dir_url(__FILE__));

if (! class_exists('ActionScheduler')) {
	require_once CPS_BLOOM_MAILER_DIR . 'libraries/action-scheduler/action-scheduler.php';
}

require_once CPS_BLOOM_MAILER_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, array('ChicpixiesBloomMailer\\Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('ChicpixiesBloomMailer\\Plugin', 'deactivate'));

Plugin::instance();