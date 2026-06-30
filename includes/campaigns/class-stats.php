<?php

namespace ChicpixiesBloomMailer\Campaigns;
use ChicpixiesBloomMailer\Subscribers\BloomBridge;

if (! defined('ABSPATH')) {
	exit;
}

class Stats
{
	/**
	 * Get full stats for a single campaign.
	 */
	public static function get_campaign_stats(int $campaign_id): array
	{
		global $wpdb;

		$sends_table  = $wpdb->prefix . 'cps_mailer_sends';
		$events_table = $wpdb->prefix . 'cps_mailer_events';

		$total_sent = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$sends_table} WHERE campaign_id = %d AND status = 'sent'",
			$campaign_id
		));

		$total_failed = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$sends_table} WHERE campaign_id = %d AND status = 'failed'",
			$campaign_id
		));

		$unique_opens = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT subscriber_id) FROM {$events_table} WHERE campaign_id = %d AND event_type = 'open'",
			$campaign_id
		));

		$unique_clicks = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT subscriber_id) FROM {$events_table} WHERE campaign_id = %d AND event_type = 'click'",
			$campaign_id
		));

		$total_clicks = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$events_table} WHERE campaign_id = %d AND event_type = 'click'",
			$campaign_id
		));

		$unsubscribes = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$events_table} WHERE campaign_id = %d AND event_type = 'unsubscribe'",
			$campaign_id
		));

		$top_links = self::get_top_links($campaign_id);

		// Opens over time (hourly buckets)
		$opens_over_time = $wpdb->get_results($wpdb->prepare(
			"SELECT DATE_FORMAT(created_at, '%%Y-%%m-%%d %%H:00:00') as period, COUNT(*) as count
             FROM {$events_table}
             WHERE campaign_id = %d AND event_type = 'open'
             GROUP BY period
             ORDER BY period ASC",
			$campaign_id
		));

		$open_rate  = $total_sent > 0 ? round(($unique_opens / $total_sent) * 100, 1) : 0;
		$click_rate = $total_sent > 0 ? round(($unique_clicks / $total_sent) * 100, 1) : 0;
		$ctor       = $unique_opens > 0 ? round(($unique_clicks / $unique_opens) * 100, 1) : 0;

		return [
			'total_sent'      => $total_sent,
			'total_failed'    => $total_failed,
			'unique_opens'    => $unique_opens,
			'unique_clicks'   => $unique_clicks,
			'total_clicks'    => $total_clicks,
			'unsubscribes'    => $unsubscribes,
			'open_rate'       => $open_rate,
			'click_rate'      => $click_rate,
			'ctor'            => $ctor,
			'top_links'       => $top_links,
			'opens_over_time' => $opens_over_time,
		];
	}

	/**
	 * Top clicked links for a campaign, grouped by the actual destination URL
	 * (not the raw JSON meta blob, which can vary in formatting for the same URL).
	 */
	private static function get_top_links(int $campaign_id): array
	{
		global $wpdb;
		$events_table = $wpdb->prefix . 'cps_mailer_events';

		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT meta FROM {$events_table}
             WHERE campaign_id = %d AND event_type = 'click'",
			$campaign_id
		));

		$counts = [];
		foreach ($rows as $row) {
			$meta = json_decode($row->meta, true);
			$url  = $meta['url'] ?? '';

			if (empty($url)) {
				continue;
			}

			$counts[$url] = ($counts[$url] ?? 0) + 1;
		}

		arsort($counts);
		$counts = array_slice($counts, 0, 10, true);

		$result = [];
		foreach ($counts as $url => $clicks) {
			$result[] = (object) ['url' => $url, 'clicks' => $clicks];
		}

		return $result;
	}

	/**
	 * Get overview stats across all campaigns.
	 */
	public static function get_overview(): array
	{
		global $wpdb;

		$campaigns_table = $wpdb->prefix . 'cps_mailer_campaigns';
		$sends_table     = $wpdb->prefix . 'cps_mailer_sends';
		$events_table    = $wpdb->prefix . 'cps_mailer_events';

		$total_campaigns = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$campaigns_table} WHERE status = 'sent'");
		$total_sent      = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$sends_table} WHERE status = 'sent'");

		// Distinct subscriber+campaign pairs — use CONCAT rather than
		// COUNT(DISTINCT col1, col2), which is non-standard SQL and silently
		// breaks if either column can be NULL.
		$total_opens = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT CONCAT(subscriber_id, '-', campaign_id))
             FROM {$events_table} WHERE event_type = 'open'"
		);
		$total_clicks = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT CONCAT(subscriber_id, '-', campaign_id))
             FROM {$events_table} WHERE event_type = 'click'"
		);
		$total_unsubs = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$events_table} WHERE event_type = 'unsubscribe'"
		);

		$avg_open_rate  = $total_sent > 0 ? round(($total_opens / $total_sent) * 100, 1) : 0;
		$avg_click_rate = $total_sent > 0 ? round(($total_clicks / $total_sent) * 100, 1) : 0;

		// Last 12 campaigns performance
		$recent = $wpdb->get_results(
			"SELECT c.id, c.title, c.subject, c.sent_at, c.total_recipients,
                COUNT(DISTINCT CASE WHEN e.event_type = 'open'  THEN e.subscriber_id END) as opens,
                COUNT(DISTINCT CASE WHEN e.event_type = 'click' THEN e.subscriber_id END) as clicks
             FROM {$campaigns_table} c
             LEFT JOIN {$events_table} e ON e.campaign_id = c.id
             WHERE c.status = 'sent'
             GROUP BY c.id
             ORDER BY c.sent_at DESC
             LIMIT 12"
		);

		foreach ($recent as &$row) {
			$row->open_rate  = $row->total_recipients > 0
				? round(($row->opens  / $row->total_recipients) * 100, 1)
				: 0;
			$row->click_rate = $row->total_recipients > 0
				? round(($row->clicks / $row->total_recipients) * 100, 1)
				: 0;
		}
		unset($row);

		// Subscriber growth from cps-bloom (last 30 days)
		$subscriber_growth = BloomBridge::get_growth_by_day(30);

		return [
			'total_campaigns'   => $total_campaigns,
			'total_sent'        => $total_sent,
			'total_opens'       => $total_opens,
			'total_clicks'      => $total_clicks,
			'total_unsubs'      => $total_unsubs,
			'avg_open_rate'     => $avg_open_rate,
			'avg_click_rate'    => $avg_click_rate,
			'recent_campaigns'  => $recent,
			'subscriber_growth' => $subscriber_growth,
		];
	}
}
