<?php

namespace ChicpixiesBloomMailer\Campaigns;
use ChicpixiesBloomMailer\Helpers;

use WP_Error;

if (! defined('ABSPATH')) {
	exit;
}

class Campaign
{
	const CAMPAIGNS_TABLE = 'cps_mailer_campaigns';
	const TEMPLATES_TABLE = 'cps_mailer_templates';

	/**
	 * create new campaign
	 * 
	 * @param array $data Template data
	 * @param boolean $is_template
	 * @return WP_Error|int insert_id on error
	 */
	public static function create($data, $is_template = false)
	{
		global $wpdb;

		$name = $is_template ? self::TEMPLATES_TABLE : self::CAMPAIGNS_TABLE;
		$table = $wpdb->prefix . $name;

		$data['created_at']	= current_time('mysql');
		$inserted = $wpdb->insert($table, $data, Helpers::formats($data));

		if (empty($inserted)) {
			return new WP_Error(
				'db_insert_error',
				__('Failed to create new campaign.', 'cps-bloom-mailer'),
				['status' => 500]
			);
		}
		return $wpdb->insert_id;
	}

	public static function get($id, $is_template = false)
	{
		if (empty($id)) return null;
		
		global $wpdb;

		$name = $is_template ? self::TEMPLATES_TABLE : self::CAMPAIGNS_TABLE;
		$table = $wpdb->prefix . $name;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
	}

	/**
	 * Update campaign
	 * 
	 * @param int $id campaign ID
	 * @param array $data Template data
	 * @param boolean $is_template
	 * @return WP_Error|int $id on error
	 */
	public static function update($id, $data, $is_template = false)
	{
		global $wpdb;

		$name = $is_template ? self::TEMPLATES_TABLE : self::CAMPAIGNS_TABLE;
		$table = $wpdb->prefix . $name;
		$allowed = array('title', 'subject', 'preview_text', 'blocks', 'design', 'header', 'footer', 'html', 'from_name', 'from_email', 'reply_to', 'status', 'scheduled_at', 'total_recipients', 'sent_at', 'template_key', 'is_default', 'description');
		$update  = array_intersect_key($data, array_flip($allowed));
		$updated = $wpdb->update($table, $update, array('id' => $id));

		if (empty($updated)) {
			return new WP_Error(
				'db_update_error',
				__('Failed to update campaign.', 'cps-bloom-mailer'),
				['status' => 500]
			);
		}

		return $id;
	}

	public static function get_all(array $args = [], bool $is_template = false)
	{
		global $wpdb;

		$table = $wpdb->prefix . (
			$is_template
			? self::TEMPLATES_TABLE
			: self::CAMPAIGNS_TABLE
		);

		$limit	= $args['limit'] ?? 20;
		$offset	= max(0, (int) ($args['offset'] ?? 0));
		$where  = [];
		$params = [];

		if (!$is_template) {
			$status     = $args['status'] ?? null;
			if (!empty($status)) {
				$where[]  = 'status = %s';
				$params[] = $status;
			}
		} else {
			if (empty($args['is_default'])) {
				$where[]  = 'is_default = %d';
				$params[] = 1;
			}
		}

		$sql = "SELECT * FROM {$table}";

		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		$sql .= ' ORDER BY created_at DESC LIMIT %d OFFSET %d';

		$params[] = $limit;
		$params[] = $offset;

		return $wpdb->get_results(
			$wpdb->prepare($sql, ...$params)
		);
	}

	public static function count($args = [], $is_template = false)
	{
		global $wpdb;

		$table = $wpdb->prefix . ($is_template ? self::TEMPLATES_TABLE : self::CAMPAIGNS_TABLE);

		$status = $args['status'] ?? null;

		$where  = [];
		$params = [];

		if ($status) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		$sql = "SELECT COUNT(*) FROM {$table}";

		if (!empty($where)) {
			$sql .= " WHERE " . implode(' AND ', $where);
		}

		if (!empty($params)) {
			return (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
		}

		return (int) $wpdb->get_var($sql);
	}

	public static function cancel_pause($id, $action)
	{
		$campaign = self::get($id);

		if (empty($campaign)) {
			return new WP_Error('not_found', __('Campaign not found.', 'cps-bloom-mailer'), ['status' => 404]);
		}

		if ($action == 'cancel' && $campaign->status !== 'sending') {
			return new WP_Error('invalid_status', __('Only a campaign that is currently sending can be cancelled', 'cps-bloom-mailer'), ['status' => 422]);
		}

		if ($action == 'pause' && ! in_array($campaign->status, ['scheduled', 'sending'], true)) {
			return new WP_Error('invalid_status', __('Only a scheduled or sending campaign can be paused.', 'cps-bloom-mailer'), ['status' => 422]);
		}

		if ($action === 'cancel') {
			global $wpdb;
			$sends_table = $wpdb->prefix . 'cps_mailer_sends';

			// Remove pending sends so the queue stops picking this campaign up.
			// Already-sent emails can't be unsent — this only stops what hasn't gone out yet.
			$wpdb->delete($sends_table, ['campaign_id' => $id, 'status' => 'pending'], ['%d', '%s']);
		}

		self::update($id, ['status' => 'paused']);
		Queue::invalidate_render_cache(intVal($id));
		return true;
	}

	public static function delete($id)
	{
		global $wpdb;
		$table = $wpdb->prefix . self::CAMPAIGNS_TABLE;
		return $wpdb->delete($table, array('id' => $id), array('%d'));
	}
}
