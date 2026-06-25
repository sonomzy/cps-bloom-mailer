<?php

namespace ChicpixiesBloomMailer;

use Wp_Error;
use Exception;

if (! defined('ABSPATH')) {
	exit;
}

class Installer
{
	const DB_VERSION = '1.0.0';
	const DB_OPTION = 'cbm_db_version';

	public static function run()
	{
		if (! current_user_can('manage_options')) {
			return;
		}

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		self::create_campaigns_table($charset_collate);
		self::create_automations_table($charset_collate);
		self::create_sends_table($charset_collate);
		self::create_events_table($charset_collate);
		self::create_settings_table($charset_collate);
		self::create_templates_table($charset_collate);
		self::create_suppressions_table($charset_collate);

		update_option('cps_mailer_db_version', CPS_BLOOM_MAILER_VERSION);
	}

	private static function create_campaigns_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'cps_mailer_campaigns';

		// Check MySQL version for JSON support
		$mysql_version = $wpdb->get_var("SELECT VERSION()");
		$json_type = version_compare($mysql_version, '5.7.8', '>=') ? 'JSON' : 'LONGTEXT';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL DEFAULT '',
			subject varchar(255) NOT NULL DEFAULT '',
			design LONGTEXT NOT NULL,
			blocks LONGTEXT NOT NULL,
			header LONGTEXT NULL,
			footer LONGTEXT NULL,
			preview_text varchar(255) NOT NULL DEFAULT '',
			from_name varchar(100) NOT NULL DEFAULT '',
			from_email varchar(100) NOT NULL DEFAULT '',
			reply_to varchar(100) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'draft',
			list varchar(255) NULL,
			tags {$json_type} NULL,
			total_recipients int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			scheduled_at datetime DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY status (status)
		) {$charset_collate};";

		dbDelta($sql);
	}

	private static function create_sends_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'cps_mailer_sends';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			campaign_id bigint(20) unsigned NOT NULL,
			subscriber_id bigint(20) unsigned NOT NULL,
			email varchar(100) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'pending',
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY campaign_id (campaign_id),
			KEY status (status),
			KEY subscriber_id (subscriber_id)
		) {$charset_collate};";

		dbDelta($sql);
	}

	private static function create_events_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'cps_mailer_events';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			campaign_id bigint(20) unsigned NOT NULL,
			subscriber_id bigint(20) unsigned NOT NULL,
			event_type varchar(20) NOT NULL DEFAULT '',
			meta longtext DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY campaign_id (campaign_id),
			KEY event_type (event_type),
			KEY subscriber_id (subscriber_id)
		) {$charset_collate};";

		dbDelta($sql);
	}

	private static function create_settings_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'cps_mailer_settings';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			option_key varchar(100) NOT NULL DEFAULT '',
			option_value longtext DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY option_key (option_key)
		) {$charset_collate};";

		dbDelta($sql);

		self::seed_default_settings();
	}

	private static function create_templates_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'cps_mailer_templates';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			template_key varchar(100) NOT NULL DEFAULT '',
			title varchar(255) NOT NULL DEFAULT '',
			subject varchar(255) NOT NULL DEFAULT '',
			description varchar(255) DEFAULT NULL,
			preview_text varchar(255) NOT NULL DEFAULT '',
			design LONGTEXT NOT NULL,
			blocks LONGTEXT NOT NULL,
			header LONGTEXT NULL,
			footer LONGTEXT NULL,
			is_default TINYINT(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY is_default (is_default),
			UNIQUE KEY template_key (template_key)
		) {$charset_collate};";

		dbDelta($sql);
	}

	private static function create_automations_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'cps_mailer_automations';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL DEFAULT '',
			campaign_id bigint(20) unsigned NOT NULL,
			event varchar(100) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			last_triggered_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY event_status (event, status),
			KEY campaign_id (campaign_id)
		) {$charset_collate};";

		dbDelta($sql);
	}

	private static function create_suppressions_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'cps_mailer_suppressions';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(100) NOT NULL DEFAULT '',
			reason varchar(20) NOT NULL DEFAULT 'unsubscribed',
			source varchar(20) NOT NULL DEFAULT 'manual',
			campaign_id bigint(20) unsigned DEFAULT NULL,
			detail text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY email (email),
			KEY reason (reason)
		) {$charset_collate};";

		dbDelta($sql);
	}

	private static function sequences_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'wp_cps_mailer_sequences';

		$sql = "CREATE TABLE {$table} (
			id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
			name varchar(255) NOT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP
		) {$charset_collate};";

		dbDelta($sql);
	}

	private static function sequence_steps_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'wp_cps_mailer_sequence_steps';

		$sql = "CREATE TABLE {$table} (
			id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
			sequence_id bigint unsigned NOT NULL,
			campaign_id bigint unsigned NOT NULL,
			step_order int NOT NULL DEFAULT 0,
			delay_days int NOT NULL DEFAULT 0,  -- days after previous step
			KEY sequence_id (sequence_id)
		) {$charset_collate};";

		dbDelta($sql);
	}

	private static function sequence_progress_table($charset_collate)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'wp_cps_mailer_sequence_progress';

		$sql = "CREATE TABLE {$table} (
			id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
			sequence_id bigint unsigned NOT NULL,
			subscriber_id bigint unsigned NOT NULL,
			current_step int NOT NULL DEFAULT 0,
			next_send_at datetime DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			enrolled_at datetime DEFAULT CURRENT_TIMESTAMP,
			KEY sequence_id (sequence_id),
			KEY subscriber_id (subscriber_id),
			KEY next_send_at (next_send_at),
			UNIQUE KEY subscriber_sequence (sequence_id, subscriber_id)
		) {$charset_collate};";

		dbDelta($sql);
	}

	public static function maybe_upgrade()
	{
		$installed_version = get_option(self::DB_OPTION);
		if ($installed_version === self::DB_VERSION) {
			return;
		}

		self::run();
		update_option(self::DB_OPTION, self::DB_VERSION);
	}

	private static function seed_default_settings()
	{
		global $wpdb;

		$table = $wpdb->prefix . 'cps_mailer_settings';

		$defaults = array(
			'mailer'          => 'smtp',
			'batch_size'      => 50,
			'cron_frequency'  => 'every_minute',
			'from_name'       => get_bloginfo('name'),
			'from_email'      => get_option('admin_email'),
			'reply_to'        => '',
			'smtp_host'       => '',
			'smtp_port'       => '587',
			'smtp_encryption' => 'tls',
			'smtp_username'   => '',
			'smtp_password'   => '',
			'ses_key'         => '',
			'ses_secret'      => '',
			'ses_region'      => 'us-east-1',
		);

		foreach ($defaults as $key => $value) {
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$table} (option_key, option_value) VALUES (%s, %s)",
					$key,
					$value
				)
			);
		}
	}

	public static function delete($campaign_ids = null)
	{
		if (!current_user_can('manage_options')) {
			return new Wp_Error('deletion_failed', __('You are not allowed to do this', 'cps-bloom-mailer'));
		}

		global $wpdb;

		$campaigns  = $wpdb->prefix . 'cps_mailer_campaigns';
		$sends = $wpdb->prefix . 'cps_mailer_sends';
		$events  = $wpdb->prefix . 'cps_mailer_events';
		$settings  = $wpdb->prefix . 'cps_mailer_settings';
		$automations = $wpdb->prefix . 'cps_mailer_automations';

		// If no specific IDs — truncate all tables
		if ($campaign_ids === null) {
			$wpdb->query('START TRANSACTION');
			try {
				$wpdb->query("TRUNCATE TABLE $campaigns");
				$wpdb->query("TRUNCATE TABLE $sends");
				$wpdb->query("TRUNCATE TABLE $events");
				$wpdb->query("TRUNCATE TABLE $settings");
				$wpdb->query("TRUNCATE TABLE $automations");
				$wpdb->query('COMMIT');
				return true;
			} catch (Exception $e) {
				$wpdb->query('ROLLBACK');
				return new Wp_Error('deletion_failed', $e->getMessage());
			}
		}

		if (!is_array($campaign_ids)) {
			$campaign_ids = [$campaign_ids];
		}

		$campaign_ids = array_values(array_filter(array_map('absint', $campaign_ids)));
		if (empty($campaign_ids)) {
			return new Wp_Error('deletion_failed', __('Subscription id must be a single or array of numeric values', 'cps-bloom-mailer'));
		}

		$ids_placeholder = implode(',', $campaign_ids);
		$rows = $wpdb->get_results("SELECT id FROM $campaigns WHERE id IN ($ids_placeholder)");

		$wpdb->query('START TRANSACTION');
		try {
			$wpdb->query("DELETE FROM $sends WHERE campaign_id IN ($ids_placeholder)");
			$wpdb->query("DELETE FROM $events WHERE campaign_id IN ($ids_placeholder)");
			$wpdb->query("DELETE FROM $automations WHERE campaign_id IN ($ids_placeholder)");
			// Then delete the subscriptions
			$wpdb->query("DELETE FROM $campaigns WHERE id IN ($ids_placeholder)");
			$wpdb->query('COMMIT');

			return wp_list_pluck($rows, 'id');
		} catch (Exception $e) {
			$wpdb->query('ROLLBACK');
			return new Wp_Error('deletion_failed', $e->getMessage());
		}
	}

	public static function drop_tables()
	{
		global $wpdb;
		$tables = [
			'cps_mailer_campaigns',
			'cps_mailer_sends',
			'cps_mailer_events',
			'cps_mailer_settings',
			'cps_mailer_automations',
			'cps_mailer_templates',
			'cps_mailer_suppressions',
		];

		foreach ($tables as $table_suffix) {
			$table = $wpdb->prefix . $table_suffix;
			$wpdb->query("DROP TABLE IF EXISTS {$table}");
		}

		delete_option(self::DB_OPTION);
	}
}
