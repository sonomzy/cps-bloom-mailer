<?php

namespace ChicpixiesBloomMailer;

use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class Resend
{
    /**
     * Build a new draft campaign targeting only the subscribers who did
     * NOT open the source campaign. Content is cloned from the source;
     * the new campaign's recipient list is hard-pinned to the resolved
     * non-opener subscriber IDs at creation time (not a dynamic list/tag
     * rule), so it won't drift if list membership changes later.
     *
     * @param int    $source_campaign_id
     * @param string $subject_override   Optional new subject (e.g. "Did you miss this?").
     * @return int|WP_Error  New campaign ID, or WP_Error on failure.
     */
    public static function to_non_openers(int $source_campaign_id, string $subject_override = '')
    {
        $source = Campaign::get($source_campaign_id);
        if (empty($source)) {
            return new WP_Error('not_found', __('Source campaign not found.', 'cps-bloom-mailer'), ['status' => 404]);
        }

        if ($source->status !== 'sent') {
            return new WP_Error('invalid_status', __('Source campaign must be fully sent before resending to non-openers.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        $non_opener_ids = self::get_non_opener_ids($source_campaign_id);

        if (empty($non_opener_ids)) {
            return new WP_Error('no_recipients', __('Everyone who received this campaign already opened it.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        // Clone the campaign content via the existing duplicator, then
        // pin the recipient set explicitly.
        $new_campaign_id = Duplicator::duplicate($source_campaign_id);
        if (is_wp_error($new_campaign_id)) {
            return $new_campaign_id;
        }

        $subject = $subject_override !== ''
            ? $subject_override
            : sprintf(
                /* translators: %s: original subject line */
                __('(Resend) %s', 'cps-bloom-mailer'),
                $source->subject
            );

        Campaign::update($new_campaign_id, [
            'title'   => $source->title . ' (Resend to non-openers)',
            'subject' => $subject,
            // Clear list/tag rules — recipients for this campaign come
            // exclusively from the pinned subscriber set below.
            'list'    => null,
            'tags'    => null,
        ]);

        self::pin_recipients($new_campaign_id, $non_opener_ids);

        return $new_campaign_id;
    }

    /**
     * Resolve subscriber IDs who received the campaign but have no 'open' event.
     */
    private static function get_non_opener_ids(int $campaign_id): array
    {
        global $wpdb;
        $sends_table  = $wpdb->prefix . 'cps_mailer_sends';
        $events_table = $wpdb->prefix . 'cps_mailer_events';

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT s.subscriber_id
             FROM {$sends_table} s
             WHERE s.campaign_id = %d
               AND s.status = 'sent'
               AND NOT EXISTS (
                   SELECT 1 FROM {$events_table} e
                   WHERE e.campaign_id = s.campaign_id
                     AND e.subscriber_id = s.subscriber_id
                     AND e.event_type = 'open'
               )",
            $campaign_id
        ));

        return array_map('intval', $ids);
    }

    /**
     * Queue the resend campaign for the resolved non-opener subscriber IDs.
     * Sender::prepare() already re-validates active status and suppression,
     * so we just hand it the ID list directly.
     */
    private static function pin_recipients(int $campaign_id, array $subscriber_ids)
    {
        Sender::prepare($campaign_id, $subscriber_ids);
    }
}