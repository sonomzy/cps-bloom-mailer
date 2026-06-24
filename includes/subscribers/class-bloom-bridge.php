<?php

namespace ChicpixiesBloomMailer;

use ChicpixiesBloom\Database;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Bridge between cps-bloom-mailer and cps-bloom subscriber data.
 *
 * Update the table name and column mappings below to match
 * your actual cps-bloom database schema.
 */
class Bloom_Bridge
{

	/**
	 * The cps-bloom subscribers table name (without prefix).
	 * Change this to match your actual table.
	 */
	const TABLE = 'cps_bloom_subscribers';

	/**
	 * Column name mappings.
	 * Adjust these to match your cps-bloom table columns.
	 */
	const COL_ID     = 'id';
	const COL_EMAIL  = 'email';
	const COL_STATUS = 'status';
	const COL_FNAME   = 'first_name';
	const COL_LNAME   = 'last_name';
	const COL_LIST   = 'list';
	const COL_TAGS   = 'tags';

	/**
	 * The value in the status column that means "active/subscribed".
	 */
	const STATUS_ACTIVE = 'subscribed';

	/**
	 * Import rows from a parsed CSV using a field map.
	 *
	 * $map is keyed by CSV column header, value is the DB field to map to.
	 * Use 'skip' or empty string to ignore a column.
	 *
	 * Tags column: comma-separated string, e.g. "newsletter, vip" -> ['newsletter', 'vip']
	 *
	 * @param array[] $rows  Rows from fgetcsv, each keyed by CSV header.
	 * @param array   $map   e.g. [ 'Email Address' => 'email', 'First' => 'first_name' ]
	 *
	 * @return array{
	 *   imported:     int,
	 *   updated:      int,
	 *   skipped:      int,
	 *   skipped_rows: array
	 * }
	 */
	public static function import_csv(array $rows, array $map): array
	{
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$result = [
			'imported'     => 0,
			'updated'      => 0,
			'skipped'      => 0,
			'skipped_rows' => [],
		];

		foreach ($rows as $index => $row) {
			$row_number = $index + 2; // account for header row

			// Build data array from map
			$data = [];
			foreach ($map as $csv_col => $db_field) {
				if (empty($db_field) || $db_field === 'skip') continue;
				$data[$db_field] = trim($row[$csv_col] ?? '');
			}

			// Email required
			if (empty($data['email'])) {
				$result['skipped']++;
				$result['skipped_rows'][] = [
					'row'    => $row_number,
					'reason' => 'Missing email',
					'value'  => '',
				];
				continue;
			}

			// Email format check
			if (!is_email($data['email'])) {
				$result['skipped']++;
				$result['skipped_rows'][] = [
					'row'    => $row_number,
					'reason' => 'Invalid email',
					'value'  => $data['email'],
				];
				continue;
			}

			// Parse tags: "newsletter, vip, sale" -> ['newsletter', 'vip', 'sale']
			if (!empty($data['tags']) && is_string($data['tags'])) {
				$data['tags'] = array_values(
					array_filter(array_map('trim', explode(',', $data['tags'])))
				);
			} else {
				$data['tags'] = [];
			}

			if (!empty($data['date_created'])) {
				$data['date_created'] = self::normalize_date($data['date_created']);
			}

			if (!empty($data['date_updated'])) {
				$data['date_updated'] = self::normalize_date($data['date_updated']);
			}

			// Apply defaults for anything not mapped
			$data = wp_parse_args($data, [
				'first_name' => '',
				'last_name'  => '',
				'platform'   => '',
				'source'     => 'import',
				'list'       => '',
				'status'     => 'subscribed',
				'date_created' => current_time('mysql'),
				'date_updated' => $data['date_created'] ?? current_time('mysql'),
				'timezone'   => '',
				'tags'       => [],
			]);

			// Check existence before save for accurate counting
			$existing_id = $wpdb->get_var(
				$wpdb->prepare("SELECT id FROM {$table} WHERE email = %s LIMIT 1", $data['email'])
			);

			$saved = Database::save($data);

			if ($saved === false) {
				$result['skipped']++;
				$result['skipped_rows'][] = [
					'row'    => $row_number,
					'reason' => 'Database error',
					'value'  => $data['email'],
				];
				continue;
			}

			if ($existing_id) {
				$result['updated']++;
			} else {
				$result['imported']++;
			}
		}

		return $result;
	}

	private static function normalize_date(string $value): ?string
	{
		if (empty(trim($value))) {
			return current_time('mysql'); // fallback to now
		}

		if (ctype_digit($value)) {
			return gmdate('Y-m-d H:i:s', (int) $value);
		}

		$ts = strtotime($value);
		if ($ts === false) {
			return current_time('mysql'); // unparseable, fallback to now
		}

		return gmdate('Y-m-d H:i:s', $ts);
	}
	/**
	 * Get all active subscribers.
	 * @param array $args Optional filters (e.g. list, tags)
	 * @return array Array of objects with id, email, name properties.
	 */
	public static function get_active_subscribers(array $args = []): array
	{
		$defaults = [
			'status' => 'active',   // enforce active status
			'list'   => '',
			'search' => '',
			'tags'   => null,
		];
		$args = wp_parse_args($args, $defaults);

		$result = Database::query($args);

		// Optional: limit returned columns to match original method
		return array_map(function ($item) {
			return (object) [
				'id'         => $item->id,
				'email'      => $item->email,
				'first_name' => $item->first_name,
				'last_name'  => $item->last_name,
				'timezone'   => $item->timezone,
				'utc_offset' => $item->utc_offset,
			];
		}, $result['items']);
	}

	/**
	 * Get a single subscriber by ID.
	 *
	 * @param int $id
	 * @return object|null
	 */
	public static function get_subscriber(int $id)
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
				id,
				email,
				first_name,
				last_name,
				timezone,
				utc_offset
				FROM {$table}
				WHERE " . self::COL_ID . " = %d",
				$id
			)
		);
	}

	/**
	 * Get total active subscriber count.
	 *
	 * @return int
	 */
	public static function get_count(): int
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE " . self::COL_STATUS . " = %s",
				self::STATUS_ACTIVE
			)
		);
	}

	/**
	 * Get new subscriber count grouped by day for the last N days.
	 * Requires a created_at column in the bloom table.
	 *
	 * @param int $days
	 * @return array
	 */
	public static function get_growth_by_day(int $days = 30): array
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		// Gracefully return empty if column doesn't exist
		$columns = $wpdb->get_col("DESCRIBE {$table}", 0);
		if (! in_array('created_at', $columns, true)) {
			return array();
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) as date, COUNT(*) as count
				FROM {$table}
				WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				GROUP BY DATE(created_at)
				ORDER BY date ASC",
				$days
			)
		);
	}

	/**
	 * Get a flat array of IDs for all active subscribers.
	 *
	 * @return int[]
	 */
	public static function get_active_subscriber_ids(): array
	{
		global $wpdb;

		$table      = $wpdb->prefix . self::TABLE;
		$id_col     = self::COL_ID;
		$status_col = self::COL_STATUS;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_col($wpdb->prepare(
			"SELECT {$id_col} FROM {$table} WHERE {$status_col} = %s",
			self::STATUS_ACTIVE
		));

		return array_map('intval', $results ?? []);
	}

	/**
	 * Mark a subscriber as unsubscribed in cps-bloom.
	 *
	 * @param int $subscriber_id
	 * @return bool
	 */
	public static function unsubscribe(int $subscriber_id): bool
	{
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;

		return (bool) $wpdb->update(
			$table,
			array(self::COL_STATUS => 'unsubscribed'),
			array(self::COL_ID     => $subscriber_id),
			array('%s'),
			array('%d')
		);
	}
}
