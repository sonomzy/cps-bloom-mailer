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