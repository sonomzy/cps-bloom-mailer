<?php

namespace ChicpixiesBloomMailer;

if (! defined('ABSPATH')) {
    exit;
}

class Mailer_SES extends Mailer_Base
{
    private string $key;
    private string $secret;
    private string $region;
    private string $endpoint;

    public function __construct()
    {
        // Cast explicitly — typed properties throw a TypeError if
        // Settings::get() ever returns null for an unset option.
        $this->key      = (string) Settings::get('ses_key', '');
        $this->secret   = (string) Settings::get('ses_secret', '');
        $this->region   = (string) Settings::get('ses_region', 'us-east-1');
        $this->endpoint = "https://email.{$this->region}.amazonaws.com/v2/email/outbound-emails";
    }

    public function send(array $args): bool
    {
        $to         = $args['to'] ?? '';
        $subject    = $args['subject'] ?? '';
        $html       = $args['html'] ?? '';
        $from_name  = $args['from_name'] ?: $this->get_default_from_name();
        $from_email = $args['from_email'] ?: $this->get_default_from_email();
        $reply_to   = $args['reply_to'] ?: $this->get_default_reply_to();

        if (! $to || ! $subject || ! $html) {
            return false;
        }

        if (! $this->key || ! $this->secret) {
            error_log('CPS Mailer SES: Missing API credentials.');
            return false;
        }

        $from    = $from_name ? "{$from_name} <{$from_email}>" : $from_email;
        $payload = wp_json_encode([
            'FromEmailAddress'  => $from,
            'ReplyToAddresses'  => $reply_to ? [$reply_to] : [$from_email],
            'Destination'       => [
                'ToAddresses' => [$to],
            ],
            'Content' => [
                'Simple' => [
                    'Subject' => [
                        'Data'    => $subject,
                        'Charset' => 'UTF-8',
                    ],
                    'Body' => [
                        'Html' => [
                            'Data'    => $html,
                            'Charset' => 'UTF-8',
                        ],
                    ],
                ],
            ],
            'EmailTags' => [
                [
                    'Name'  => 'cps_campaign_id',
                    'Value' => $args['campaign_id']??'unset'
                ],
            ]
        ]);

        $response = wp_remote_post(
            $this->endpoint,
            [
                'timeout' => 15,
                'headers' => $this->build_headers($payload),
                'body'    => $payload,
            ]
        );

        if (is_wp_error($response)) {
            error_log('CPS Mailer SES error: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            error_log('CPS Mailer SES error: HTTP ' . $code . ' - ' . wp_remote_retrieve_body($response));
            return false;
        }

        return true;
    }

    private function build_headers(string $payload): array
    {
        $service   = 'ses';
        $method    = 'POST';
        $uri       = '/v2/email/outbound-emails';
        $query     = '';
        $host      = "email.{$this->region}.amazonaws.com";
        $date_time = gmdate('Ymd\THis\Z');
        $date      = gmdate('Ymd');

        $payload_hash      = hash('sha256', $payload);
        $canonical_headers = "content-type:application/json\nhost:{$host}\nx-amz-date:{$date_time}\n";
        $signed_headers    = 'content-type;host;x-amz-date';
        $canonical_request = implode("\n", [$method, $uri, $query, $canonical_headers, $signed_headers, $payload_hash]);
        $credential_scope  = "{$date}/{$this->region}/{$service}/aws4_request";
        $string_to_sign    = implode("\n", ['AWS4-HMAC-SHA256', $date_time, $credential_scope, hash('sha256', $canonical_request)]);

        $signing_key = $this->derive_signing_key($date, $service);
        $signature   = hash_hmac('sha256', $string_to_sign, $signing_key);

        $authorization = "AWS4-HMAC-SHA256 Credential={$this->key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

        return [
            'Content-Type'  => 'application/json',
            'X-Amz-Date'    => $date_time,
            'Authorization' => $authorization,
        ];
    }

    private function derive_signing_key(string $date, string $service): string
    {
        $k_date    = hash_hmac('sha256', $date, 'AWS4' . $this->secret, true);
        $k_region  = hash_hmac('sha256', $this->region, $k_date, true);
        $k_service = hash_hmac('sha256', $service, $k_region, true);
        return hash_hmac('sha256', 'aws4_request', $k_service, true);
    }
}
