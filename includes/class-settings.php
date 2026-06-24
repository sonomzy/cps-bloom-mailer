<?php

namespace ChicpixiesBloomMailer;

if (! defined('ABSPATH')) {
	exit;
}

class Settings
{

	private static array $cache = array();

	public static function get(string $key, $default = '')
	{
		if (isset(self::$cache[$key])) {
			return self::$cache[$key];
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cps_mailer_settings';

		$value = $wpdb->get_var(
			$wpdb->prepare("SELECT option_value FROM {$table} WHERE option_key = %s", $key)
		);

		$result = $value !== null ? $value : $default;
		self::$cache[$key] = $result;

		return $result;
	}

	public static function set(string $key, $value): bool
	{
		global $wpdb;
		$table = $wpdb->prefix . 'cps_mailer_settings';

		self::$cache[$key] = $value;

		$existing = $wpdb->get_var(
			$wpdb->prepare("SELECT id FROM {$table} WHERE option_key = %s", $key)
		);

		if ($existing) {
			return (bool) $wpdb->update(
				$table,
				array('option_value' => $value),
				array('option_key'   => $key),
				array('%s'),
				array('%s')
			);
		}

		return (bool) $wpdb->insert(
			$table,
			array('option_key' => $key, 'option_value' => $value),
			array('%s', '%s')
		);
	}

	public static function get_all(): array
	{
		global $wpdb;
		$table = $wpdb->prefix . 'cps_mailer_settings';
		$rows  = $wpdb->get_results("SELECT option_key, option_value FROM {$table}");

		$settings = array();
		foreach ($rows as $row) {
			$settings[$row->option_key] = $row->option_value;
		}

		return $settings;
	}

	public static function delete()
	{
		global $wpdb;
		$table = $wpdb->prefix . 'cps_mailer_settings';
		return $wpdb->query("DELETE FROM {$table}");
	}
}
