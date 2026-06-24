<?php

namespace ChicpixiesBloomMailer;

if (! defined('ABSPATH')) {
	exit;
}

class Queue
{

	public static function process()
	{
		global $wpdb;

		$batch_size  = intval(Settings::get('batch_size', 50));
		$sends_table = $wpdb->prefix . 'cps_mailer_sends';
		$campaigns_table = $wpdb->prefix . 'cps_mailer_campaigns';
		$bloom_table     = $wpdb->prefix . Bloom_Bridge::TABLE;
		$fname_col       = Bloom_Bridge::COL_FNAME;
		$lname_col       = Bloom_Bridge::COL_LNAME;

		// JOIN bloom table for subscriber name data
		$pending = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
                    s.id,
                    s.campaign_id,
                    s.subscriber_id,
                    s.email,
                    c.subject,
                    c.blocks,
                    c.header,
                    c.footer,
                    c.design,
                    c.from_name,
                    c.from_email,
                    c.reply_to,
                    c.preview_text,
                    b.{$fname_col} AS first_name,
                    b.{$lname_col} AS last_name
                 FROM {$sends_table} s
                 INNER JOIN {$campaigns_table} c ON c.id = s.campaign_id
                 LEFT JOIN {$bloom_table} b ON b.id = s.subscriber_id
                 WHERE s.status = 'pending'
                   AND c.status = 'sending'
                 LIMIT %d",
				$batch_size
			)
		);

		if (empty($pending)) {
			return;
		}

		$mailer = Mailer_Factory::make();
		$failed_count  = 0;

		foreach ($pending as $send) {
			$base_html = self::get_rendered_html($send);
			$merge_data = self::get_merge_data($send);
			$subject = Helpers::replace_tags($send->subject, $merge_data);
			$body         = self::personalize(
				Helpers::replace_tags($base_html, $merge_data),
				$send
			);

			$result = $mailer->send(array(
				'to'           => $send->email,
				'subject'      => $subject,
				'html'         => $body,
				'reply_to'     => $send->reply_to,
				'from_name'    => $send->from_name,
				'from_email'   => $send->from_email,
			));

			$status = $result ? 'sent' : 'failed';

			$wpdb->update(
				$sends_table,
				array(
					'status'  => $status,
					'sent_at' => current_time('mysql'),
				),
				array('id' => $send->id),
				array('%s', '%s'),
				array('%d')
			);

			if (! $result) {
				$failed_count++;
			}
		}

		// Notify admin if every send in this batch failed — likely a mailer config issue
		if ($failed_count > 0 && $failed_count === count($pending)) {
			self::admin_notification(
				__('Email sending failed', 'cps-bloom-mailer'),
				sprintf(
					/* translators: %d: number of failed sends */
					__('All %d emails in the latest send batch failed. Please check your mailer settings.', 'cps-bloom-mailer'),
					$failed_count
				)
			);
		}

		// Check if campaign is fully sent
		self::maybe_complete_campaigns();
	}

	/**
	 * Get subscription data
	 */
	private static function get_merge_data($data)
	{
		$personalized_data = array(
			"{{email}}" => $data->email,
			"{{first_name}}" => ucfirst($data->first_name ?? 'Amazing'),
			"{{last_name}}" => ucfirst($data->last_name ?? 'Subscriber'),
			"{{full_name}}" => trim(ucfirst($data->first_name) . ' ' . ucfirst($data->last_name)),
			"{{unsubscribe_url}}" => Unsubscribe::make_url($data->subscriber_id, $data->campaign_id),
		);
		return apply_filters('cps_mailer_user_merge_data', $personalized_data);
	}

	private static function personalize(string $html, $send): string
	{
		$click_base = home_url('/cps-bloom-mailer/click/');

		// Wrap links for click tracking — skip already-tracked URLs and
		// the unsubscribe link (we don't want to double-wrap or log those)
		$html = preg_replace_callback(
			'/<a(\s[^>]*)href=["\']([^"\']+)["\']([^>]*)>/i',
			function ($matches) use ($send, $click_base) {
				$before = $matches[1];
				$url    = $matches[2];
				$after  = $matches[3];

				// Skip already-tracked links and unsubscribe links
				if (
					strpos($url, $click_base) === 0 ||
					strpos($url, 'cps-bloom-mailer/unsubscribe') !== false
				) {
					return $matches[0];
				}

				$tracked = Tracker::click_url($url, $send->subscriber_id, $send->campaign_id);
				return "<a{$before}href=\"{$tracked}\"{$after}>";
			},
			$html
		);

		// Append open-tracking pixel before </body>
		$pixel = sprintf(
			'<img src="%s" width="1" height="1" style="display:none;" alt="" />',
			esc_url(Tracker::open_pixel_url($send->subscriber_id, $send->campaign_id))
		);

		if (stripos($html, '</body>') !== false) {
			$html = str_ireplace('</body>', $pixel . '</body>', $html);
		} else {
			// No </body> tag — append at the end
			$html .= $pixel;
		}

		return $html;
	}

	// -------------------------------------------------------------------------
	// Rendered HTML cache (transient-backed)
	// -------------------------------------------------------------------------

	const RENDER_CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Get the rendered HTML for a campaign, using a transient cache so the
	 * (potentially expensive) block-parsing only happens once per campaign,
	 * across all batches/recipients, not once per recipient or per process() call.
	 */
	private static function get_rendered_html($send): string
	{
		$key    = self::cache_key($send->campaign_id);
		$cached = get_transient($key);

		if ($cached !== false) {
			return $cached;
		}

		$html = Parser::from_campaign($send);

		set_transient($key, $html, self::RENDER_CACHE_TTL);

		return $html;
	}

	private static function cache_key(int $campaign_id): string
	{
		return 'cps_mailer_rendered_' . $campaign_id;
	}

	/**
	 * Clear the rendered HTML cache for a campaign — call this whenever a
	 * campaign's blocks/header/footer/design are saved, so the next queue
	 * run re-renders instead of serving a stale cached version.
	 *
	 * Hook from your campaign save handler:
	 *   Queue::invalidate_render_cache($campaign_id);
	 */
	public static function invalidate_render_cache(int $campaign_id): void
	{
		delete_transient(self::cache_key($campaign_id));
	}

	private static function maybe_complete_campaigns()
	{
		global $wpdb;

		$sends_table     = $wpdb->prefix . 'cps_mailer_sends';
		$campaigns_table = $wpdb->prefix . 'cps_mailer_campaigns';

		// Find all campaigns currently in 'sending' status
		$sending_ids = $wpdb->get_col(
			"SELECT id FROM {$campaigns_table} WHERE status = 'sending'"
		);

		if (empty($sending_ids)) {
			return;
		}

		foreach ($sending_ids as $campaign_id) {
			$pending_count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$sends_table}
                 WHERE campaign_id = %d AND status = 'pending'",
				(int) $campaign_id
			));

			if ((int) $pending_count === 0) {
				Campaign::update((int) $campaign_id, [
					'status'  => 'sent',
					'sent_at' => current_time('mysql'),
				]);
			}
		}
	}

	private static function admin_notification($subject, $message, $type = 'failed')
	{
		$admin_email = get_option('admin_email');
		$site_name = get_bloginfo('name');


		wp_mail($admin_email, "[{$site_name}] {$subject}", $message);
	}
}
