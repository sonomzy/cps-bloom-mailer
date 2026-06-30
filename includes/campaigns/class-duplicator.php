<?php

namespace ChicpixiesBloomMailer\Campaigns;

use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class Duplicator
{
    /**
     * Duplicate a campaign into a new draft.
     *
     * Copies content/design fields, resets status to 'draft', clears
     * send-related timestamps and recipient count, and appends "(Copy)"
     * to the title so it's clearly distinguishable in the campaign list.
     *
     * @param int $campaign_id
     * @return int|WP_Error  New campaign ID, or WP_Error on failure.
     */
    public static function duplicate(int $campaign_id)
    {
        global $wpdb;

        $campaign = Campaign::get($campaign_id);
        if (empty($campaign)) {
            return new WP_Error('not_found', __('Campaign not found.', 'cps-bloom-mailer'), ['status' => 404]);
        }

        $table = $wpdb->prefix . 'cps_mailer_campaigns';

        $new_data = [
            'title'            => self::next_copy_title($campaign->title),
            'subject'          => $campaign->subject,
            'design'           => $campaign->design,
            'blocks'           => $campaign->blocks,
            'header'           => $campaign->header,
            'footer'           => $campaign->footer,
            'preview_text'     => $campaign->preview_text,
            'from_name'        => $campaign->from_name,
            'from_email'       => $campaign->from_email,
            'reply_to'         => $campaign->reply_to,
            'list'             => $campaign->list,
            'tags'             => $campaign->tags,
            'status'           => 'draft',
            'total_recipients' => 0,
            'created_at'       => current_time('mysql'),
            // scheduled_at / sent_at intentionally omitted — new campaign
            // has no send history of its own.
        ];

        $inserted = $wpdb->insert(
            $table,
            $new_data,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );

        if (! $inserted) {
            return new WP_Error('db_error', __('Failed to duplicate campaign.', 'cps-bloom-mailer'), ['status' => 500]);
        }

        return $wpdb->insert_id;
    }

    /**
     * Generates a "(Copy)" / "(Copy 2)" / "(Copy 3)" suffix, avoiding stacking
     * "(Copy) (Copy)" if you duplicate a campaign that's already a copy.
     */
    private static function next_copy_title(string $title): string
    {
        if (preg_match('/^(.*) \(Copy(?: (\d+))?\)$/', $title, $m)) {
            $base = $m[1];
            $n    = isset($m[2]) ? ((int) $m[2] + 1) : 2;
            return "{$base} (Copy {$n})";
        }

        return "{$title} (Copy)";
    }
}