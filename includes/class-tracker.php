<?php

namespace ChicpixiesBloomMailer;

if (! defined('ABSPATH')) {
	exit;
}

class Tracker
{

	public static function handle_open()
	{
		$campaign_id   = intval($_GET['c'] ?? 0);
		$subscriber_id = intval($_GET['s'] ?? 0);

		if ($campaign_id && $subscriber_id) {
			self::log($campaign_id, $subscriber_id, 'open');
		}

		// Serve 1x1 transparent GIF
		header('Content-Type: image/gif');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
		exit;
	}

	public static function handle_click()
	{
		$campaign_id   = intval($_GET['c']        ?? 0);
		$subscriber_id = intval($_GET['s']        ?? 0);
		$token         = sanitize_text_field($_GET['t'] ?? '');
		$raw_url       = rawurldecode($_GET['url'] ?? '');

		// Validate destination URL before anything else
		$url = self::validate_url($raw_url);

		// Verify token — skip logging on failure but still redirect
		// so legitimate broken links don't strand the user
		if ($campaign_id && $subscriber_id && $token) {
			$expected = self::make_click_token($subscriber_id, $campaign_id, $raw_url);
			if (hash_equals($expected, $token)) {
				self::log($campaign_id, $subscriber_id, 'click', ['url' => $url ?: $raw_url]);
			}
		}

		wp_redirect($url ?: home_url(), 302);
		exit;
	}

	private static function log($campaign_id, $subscriber_id, $event_type, $meta = array())
	{
		global $wpdb;

		$table = $wpdb->prefix . 'cps_mailer_events';

		// For opens: only log once per subscriber per campaign
		if ($event_type === 'open') {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE campaign_id = %d AND subscriber_id = %d AND event_type = 'open' LIMIT 1",
					$campaign_id,
					$subscriber_id
				)
			);

			if ($exists) {
				return;
			}
		}

		$wpdb->insert(
			$table,
			array(
				'campaign_id'   => $campaign_id,
				'subscriber_id' => $subscriber_id,
				'event_type'    => $event_type,
				'meta'          => ! empty($meta) ? wp_json_encode($meta) : null,
				'created_at'    => current_time('mysql'),
			),
			array('%d', '%d', '%s', '%s', '%s')
		);
	}

    // -------------------------------------------------------------------------
    // Token helpers
    // -------------------------------------------------------------------------

	/**
	 * Token for click tracking links — covers subscriber, campaign, and the
	 * destination URL so the token can't be reused for a different URL.
	 */
	public static function make_click_token(int $subscriber_id, int $campaign_id, string $url): string
	{
		return hash_hmac('sha256', "{$subscriber_id}:{$campaign_id}:{$url}", wp_salt('auth'));
	}

    // -------------------------------------------------------------------------
    // URL builders — call these from your email generator
    // -------------------------------------------------------------------------

	/**
	 * 1×1 tracking pixel URL to embed as <img src="..."> in every email.
	 *
	 * @param int $subscriber_id
	 * @param int $campaign_id
	 * @return string
	 */
	public static function open_pixel_url(int $subscriber_id, int $campaign_id): string
	{
		return add_query_arg(
			[
				'c' => $campaign_id,
				's' => $subscriber_id,
			],
			home_url('/cps-bloom-mailer/open/')
		);
	}

	/**
	 * Wrapped click-tracking URL for a destination link.
	 *
	 * @param string $destination  The real URL to redirect to.
	 * @param int    $subscriber_id
	 * @param int    $campaign_id
	 * @return string
	 */
	public static function click_url(string $destination, int $subscriber_id, int $campaign_id): string
	{
		$token = self::make_click_token($subscriber_id, $campaign_id, $destination);

		return add_query_arg(
			[
				'c'     => $campaign_id,
				's'     => $subscriber_id,
				't' => $token,
				'u'   => rawurlencode($destination),
			],
			home_url('/cps-bloom-mailer/click/')
		);
	}

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

	/**
	 * Validate that a URL is a safe http/https destination.
	 * Returns the sanitized URL or empty string on failure.
	 */
	private static function validate_url(string $url): string
	{
		if (empty($url)) {
			return '';
		}

		$sanitized = esc_url_raw($url);
		$scheme    = wp_parse_url($sanitized, PHP_URL_SCHEME);

		if (! in_array($scheme, ['http', 'https'], true)) {
			return '';
		}

		return $sanitized;
	}
}
