<?php

namespace ChicpixiesBloomMailer\Subscribers;
use ChicpixiesBloomMailer\Core\Settings;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles inbound bounce/complaint notifications.
 *
 * Primary path: AWS SES sends bounce/complaint events to an SNS topic,
 * which is subscribed to a webhook URL on this site. We verify the SNS
 * message signature, parse the bounce type, and suppress hard bounces
 * and complaints.
 *
 * Secondary path (generic SMTP): no standard webhook format exists, so we
 * fall back to a heuristic — repeated 'failed' sends to the same address
 * across different campaigns gets treated as an implicit soft signal.
 */
class Bounce
{
    const SMTP_FAILURE_THRESHOLD = 3;

    // -------------------------------------------------------------------------
    // SES / SNS webhook
    // -------------------------------------------------------------------------

    public static function handle_ses_webhook()
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (empty($data) || ! is_array($data)) {
            status_header(400);
            exit;
        }

        $type = $data['Type'] ?? '';

        // SNS subscription handshake — must visit SubscribeURL to activate
        if ($type === 'SubscriptionConfirmation') {
            self::confirm_subscription($data);
            status_header(200);
            exit;
        }

        if ($type !== 'Notification') {
            status_header(200); // ack and ignore anything else
            exit;
        }

        if (! self::verify_sns_signature($data)) {
            error_log('CPS Mailer: SNS signature verification failed.');
            status_header(403);
            exit;
        }

        $message = json_decode($data['Message'] ?? '', true);
        if (empty($message)) {
            status_header(200);
            exit;
        }

        self::process_ses_message($message);

        status_header(200);
        exit;
    }

    private static function confirm_subscription(array $data)
    {
        $subscribe_url = $data['SubscribeURL'] ?? '';
        if (empty($subscribe_url)) {
            return;
        }

        // Only follow URLs on Amazon's SNS domain
        $host = wp_parse_url($subscribe_url, PHP_URL_HOST);
        if (! $host || ! preg_match('/\.amazonaws\.com$/', $host)) {
            error_log('CPS Mailer: Refused to confirm SNS subscription from untrusted host: ' . $host);
            return;
        }

        wp_remote_get($subscribe_url, ['timeout' => 15]);
    }

    /**
     * Verify the SNS message signature so we know this notification
     * genuinely came from AWS and wasn't spoofed by a third party hitting
     * our webhook URL directly.
     */
    private static function verify_sns_signature(array $data): bool
    {
        $cert_url = $data['SigningCertURL'] ?? '';
        $host     = wp_parse_url($cert_url, PHP_URL_HOST);

        // Cert must be served from an amazonaws.com host over https
        if (
            empty($cert_url) ||
            empty($host) ||
            ! preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com$/', $host) ||
            wp_parse_url($cert_url, PHP_URL_SCHEME) !== 'https'
        ) {
            return false;
        }

        $cert_pem = get_transient('cps_mailer_sns_cert_' . md5($cert_url));
        if ($cert_pem === false) {
            $response = wp_remote_get($cert_url, ['timeout' => 15]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }
            $cert_pem = wp_remote_retrieve_body($response);
            set_transient('cps_mailer_sns_cert_' . md5($cert_url), $cert_pem, DAY_IN_SECONDS);
        }

        $public_key = openssl_pkey_get_public($cert_pem);
        if (! $public_key) {
            return false;
        }

        $signature_version = $data['SignatureVersion'] ?? '1';
        $sig_algo           = ($signature_version === '2') ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;

        $signable_keys = ($data['Type'] === 'Notification')
            ? ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type']
            : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'Type'];

        $string_to_sign = '';
        foreach ($signable_keys as $key) {
            if (! isset($data[$key])) {
                continue;
            }
            $string_to_sign .= "{$key}\n{$data[$key]}\n";
        }

        $signature = base64_decode($data['Signature'] ?? '');
        if (empty($signature)) {
            return false;
        }

        $result = openssl_verify($string_to_sign, $signature, $public_key, $sig_algo);

        return $result === 1;
    }

    private static function process_ses_message(array $message)
    {
        $type = $message['notificationType'] ?? $message['eventType'] ?? '';

        if ($type === 'Bounce') {
            self::handle_bounce($message);
        } elseif ($type === 'Complaint') {
            self::handle_complaint($message);
        }
        // 'Delivery' notifications are ignored — nothing to suppress.
    }

    private static function handle_bounce(array $message)
    {
        $bounce      = $message['bounce'] ?? [];
        $bounce_type = $bounce['bounceType'] ?? ''; // 'Permanent' | 'Transient' | 'Undetermined'
        $recipients  = $bounce['bouncedRecipients'] ?? [];

        // Only hard (Permanent) bounces get suppressed. Transient bounces
        // (mailbox full, temp server issue) are expected to clear on their
        // own — repeatedly suppressing those would lose subscribers over
        // a temporary blip.
        if ($bounce_type !== 'Permanent') {
            return;
        }

        $campaign_id = self::extract_campaign_id($message);

        foreach ($recipients as $recipient) {
            $email = $recipient['emailAddress'] ?? '';
            if (empty($email)) {
                continue;
            }

            Suppression::add(
                $email,
                Suppression::REASON_BOUNCED,
                'ses_webhook',
                $campaign_id,
                $recipient['diagnosticCode'] ?? null
            );
        }
    }

    private static function handle_complaint(array $message)
    {
        $complaint  = $message['complaint'] ?? [];
        $recipients = $complaint['complainedRecipients'] ?? [];

        $campaign_id = self::extract_campaign_id($message);

        foreach ($recipients as $recipient) {
            $email = $recipient['emailAddress'] ?? '';
            if (empty($email)) {
                continue;
            }

            Suppression::add(
                $email,
                Suppression::REASON_COMPLAINED,
                'ses_webhook',
                $campaign_id,
                $complaint['complaintFeedbackType'] ?? null
            );
        }
    }

    /**
     * SES doesn't natively know about our campaign_id — if you tag your
     * sends with a custom SES message tag (e.g. 'cps_campaign_id') when
     * sending, it'll come back in mail.tags here. Falls back to null.
     */
    private static function extract_campaign_id(array $message): ?int
    {
        $tags = $message['mail']['tags']['cps_campaign_id'] ?? null;
        if (! empty($tags) && is_array($tags)) {
            return intval($tags[0]);
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // SMTP fallback — repeated failure heuristic
    // -------------------------------------------------------------------------

    /**
     * Runs after each queue batch. Looks for emails that have failed to
     * send SMTP_FAILURE_THRESHOLD or more times across distinct campaigns
     * and suppresses them. This is a weak signal compared to a real bounce
     * webhook, but it's the only thing available without one.
     */
    public static function check_smtp_repeat_failures()
    {
        if (Settings::get('mailer', 'smtp') !== 'smtp') {
            return; // only relevant when SMTP is the active mailer
        }

        global $wpdb;
        $sends_table = $wpdb->prefix . 'cps_mailer_sends';

        $repeat_offenders = $wpdb->get_results($wpdb->prepare(
            "SELECT email, COUNT(DISTINCT campaign_id) as fail_count
             FROM {$sends_table}
             WHERE status = 'failed'
             GROUP BY email
             HAVING fail_count >= %d",
            self::SMTP_FAILURE_THRESHOLD
        ));

        foreach ($repeat_offenders as $row) {
            if (Suppression::is_suppressed($row->email)) {
                continue;
            }

            Suppression::add(
                $row->email,
                Suppression::REASON_BOUNCED,
                'smtp_repeated_failure',
                null,
                sprintf('Failed to send %d times across distinct campaigns', $row->fail_count)
            );
        }
    }
}