<?php

namespace ChicpixiesBloomMailer\Subscribers;

if (! defined('ABSPATH')) {
	exit;
}

class Unsubscribe
{
	/**
	 * Hooked to template_redirect. Bails early if this isn't an unsubscribe request.
	 */
	public static function handle()
	{
		$campaign_id   = intval($_GET['c'] ?? 0);
		$subscriber_id = intval($_GET['s'] ?? 0);
		$token         = sanitize_text_field($_GET['t'] ?? '');

		if (! $campaign_id || ! $subscriber_id || ! $token) {
			self::render_page(
				__('Invalid Link', 'cps-bloom-mailer'),
				__('This unsubscribe link is missing required information. Please use the link from your email.', 'cps-bloom-mailer'),
				'error'
			);
			exit;
		}

		// Token covers both subscriber + campaign so the same link can't be
		// reused across campaigns.
		$expected = self::make_token($subscriber_id, $campaign_id);

		if (! hash_equals($expected, $token)) {
			self::render_page(
				__('Invalid Link', 'cps-bloom-mailer'),
				__('This unsubscribe link is invalid or has expired. Please use the link from your original email.', 'cps-bloom-mailer'),
				'error'
			);
			exit;
		}

		global $wpdb;
		$events_table = $wpdb->prefix . 'cps_mailer_events';

		// Guard against duplicate event rows
		$already_logged = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$events_table}
             WHERE campaign_id = %d AND subscriber_id = %d AND event_type = 'unsubscribe'
             LIMIT 1",
			$campaign_id,
			$subscriber_id
		));

		if (! $already_logged) {
			$wpdb->insert(
				$events_table,
				[
					'campaign_id'   => $campaign_id,
					'subscriber_id' => $subscriber_id,
					'event_type'    => 'unsubscribe',
					'created_at'    => current_time('mysql'),
				],
				['%d', '%d', '%s', '%s']
			);
		}

		// Add to the suppression list so future sends (any campaign) skip this email
		$subscriber = BloomBridge::get_subscriber($subscriber_id);
		if (! empty($subscriber->email)) {
			Suppression::add(
				$subscriber->email,
				Suppression::REASON_UNSUBSCRIBED,
				'unsubscribe_link',
				$campaign_id
			);
		}

		// Unsubscribe in cps-bloom regardless — idempotent
		BloomBridge::unsubscribe($subscriber_id);

		self::render_page(
			__('You\'ve been unsubscribed', 'cps-bloom-mailer'),
			__('You have been successfully removed from our mailing list. You won\'t receive any further emails from us.', 'cps-bloom-mailer'),
			'success'
		);
		exit;
	}
	
	// -------------------------------------------------------------------------
    // Token helpers — used here and in Email_Generator when building links
    // -------------------------------------------------------------------------

	/**
	 * Generate the HMAC token for a subscriber + campaign pair.
	 *
	 * @param int $subscriber_id
	 * @param int $campaign_id
	 * @return string
	 */
	public static function make_token(int $subscriber_id, int $campaign_id): string
	{
		return hash_hmac('sha256', "{$subscriber_id}:{$campaign_id}", wp_salt('auth'));
	}

	/**
	 * Build the full unsubscribe URL to embed in emails.
	 *
	 * @param int $subscriber_id
	 * @param int $campaign_id
	 * @return string
	 */
	public static function make_url(int $subscriber_id, int $campaign_id): string
	{
		$token = self::make_token($subscriber_id, $campaign_id);

		return add_query_arg(
			[
				'c'     => $campaign_id,
				's'     => $subscriber_id,
				'token' => $token,
			],
			home_url('/cps-bloom-mailer/unsubscribe/')
		);
	}

	// -------------------------------------------------------------------------
	// Page renderer
	// -------------------------------------------------------------------------

	private static function render_page(string $title, string $message, string $type = 'success')
	{
		$site_name = get_bloginfo('name');
		$home_url  = home_url('/');
		$color     = ($type === 'success') ? '#2e7d32' : '#c62828';
		$icon      = ($type === 'success') ? '&#10003;' : '&#10005;';

		status_header(200);
		header('Content-Type: text/html; charset=utf-8');

		echo '<!DOCTYPE html>
<html lang="' . esc_attr(get_bloginfo('language')) . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . esc_html($title) . ' &mdash; ' . esc_html($site_name) . '</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            max-width: 480px;
            width: 100%;
            padding: 48px 40px;
            text-align: center;
        }
        .icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: ' . esc_attr($color) . '1a;
            color: ' . esc_attr($color) . ';
            font-size: 28px;
            line-height: 56px;
            margin: 0 auto 24px;
        }
        h1 { font-size: 22px; color: #1a1a1a; margin-bottom: 12px; }
        p  { font-size: 15px; color: #555; line-height: 1.6; margin-bottom: 32px; }
        a  {
            display: inline-block;
            padding: 10px 24px;
            background: #1a1a1a;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }
        a:hover { background: #333; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">' . $icon . '</div>
        <h1>' . esc_html($title) . '</h1>
        <p>' . esc_html($message) . '</p>
        <a href="' . esc_url($home_url) . '">' . esc_html__('Back to site', 'cps-bloom-mailer') . '</a>
    </div>
</body>
</html>';
	}
}
