<?php

namespace ChicpixiesBloomMailer;

if (! defined('ABSPATH')) {
    exit;
}

class Suppression
{
    const REASON_BOUNCED      = 'bounced';
    const REASON_COMPLAINED   = 'complained';
    const REASON_UNSUBSCRIBED = 'unsubscribed';
    const REASON_MANUAL       = 'manual';

    /**
     * Add an email to the suppression list. Idempotent — re-suppressing
     * the same email just updates the reason/source rather than erroring.
     */
    public static function add(
        string $email,
        string $reason,
        string $source = 'manual',
        ?int $campaign_id = null,
        ?string $detail = null
    ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'cps_mailer_suppressions';

        $email = sanitize_email($email);
        if (empty($email)) {
            return false;
        }

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE email = %s LIMIT 1",
            $email
        ));

        $data = [
            'email'       => $email,
            'reason'      => $reason,
            'source'      => $source,
            'campaign_id' => $campaign_id,
            'detail'      => $detail,
        ];

        if ($existing) {
            // Don't downgrade — e.g. don't let a later "unsubscribed" overwrite
            // an existing "complained", since complaint is the more severe signal.
            if (self::severity($reason) < self::severity(self::get_reason($email))) {
                return true;
            }

            $wpdb->update(
                $table,
                $data,
                ['id' => $existing],
                ['%s', '%s', '%s', '%d', '%s'],
                ['%d']
            );
            return true;
        }

        $data['created_at'] = current_time('mysql');

        $inserted = $wpdb->insert(
            $table,
            $data,
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );

        return (bool) $inserted;
    }

    /**
     * Remove an email from the suppression list (e.g. manual re-activation).
     */
    public static function remove(string $email): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cps_mailer_suppressions';

        $email = sanitize_email($email);
        if (empty($email)) {
            return false;
        }

        return (bool) $wpdb->delete($table, ['email' => $email], ['%s']);
    }

    /**
     * Check if a single email is suppressed.
     */
    public static function is_suppressed(string $email): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cps_mailer_suppressions';

        $email = sanitize_email($email);
        if (empty($email)) {
            return false;
        }

        $found = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE email = %s LIMIT 1",
            $email
        ));

        return (bool) $found;
    }

    /**
     * Filter a list of [id => email] pairs down to non-suppressed ones.
     * Used by Sender::prepare() right before queuing sends.
     *
     * @param array $rows  Array of objects/arrays with at least 'id' and 'email'.
     * @return array       Same shape, filtered.
     */
    public static function filter_subscribers(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cps_mailer_suppressions';

        $emails = array_map(fn($r) => is_object($r) ? $r->email : $r['email'], $rows);
        $emails = array_filter(array_unique($emails));

        if (empty($emails)) {
            return $rows;
        }

        $placeholders = implode(',', array_fill(0, count($emails), '%s'));

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $suppressed = $wpdb->get_col($wpdb->prepare(
            "SELECT email FROM {$table} WHERE email IN ({$placeholders})",
            ...array_values($emails)
        ));

        if (empty($suppressed)) {
            return $rows;
        }

        $suppressed = array_flip($suppressed);

        return array_values(array_filter($rows, function ($r) use ($suppressed) {
            $email = is_object($r) ? $r->email : $r['email'];
            return ! isset($suppressed[$email]);
        }));
    }

    /**
     * Get the current reason an email is suppressed for, or null.
     */
    public static function get_reason(string $email): ?string
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cps_mailer_suppressions';

        $reason = $wpdb->get_var($wpdb->prepare(
            "SELECT reason FROM {$table} WHERE email = %s LIMIT 1",
            sanitize_email($email)
        ));

        return $reason ?: null;
    }

    /**
     * Paginated list for an admin UI.
     */
    public static function list(int $page = 1, int $per_page = 50, ?string $reason = null): array
    {
        global $wpdb;
        $table  = $wpdb->prefix . 'cps_mailer_suppressions';
        $offset = max(0, ($page - 1) * $per_page);

        if ($reason) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE reason = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $reason,
                $per_page,
                $offset
            ));
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE reason = %s",
                $reason
            ));
        } else {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ));
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        }

        return ['items' => $rows, 'total' => $total];
    }

    /**
     * Reason severity — higher number means "don't let a lower-severity
     * event overwrite this." Complaints are the most severe signal, then
     * bounces, then voluntary unsubscribes, then manual additions.
     */
    private static function severity(?string $reason): int
    {
        return match ($reason) {
            self::REASON_COMPLAINED   => 3,
            self::REASON_BOUNCED      => 2,
            self::REASON_UNSUBSCRIBED => 1,
            default                   => 0,
        };
    }
}