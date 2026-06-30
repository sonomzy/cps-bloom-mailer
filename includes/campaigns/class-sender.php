<?php

namespace ChicpixiesBloomMailer\Campaigns;
use ChicpixiesBloomMailer\Subscribers\BloomBridge;
use ChicpixiesBloomMailer\Subscribers\Suppression;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

class Sender
{
    /**
     * Prepare a campaign for sending by populating the sends queue.
     *
     * @param int   $campaign_id
     * @param int[] $subscriber_ids  Pre-resolved subscriber IDs.
     * @return int|WP_Error  Number of recipients queued, or WP_Error on failure.
     */
    public static function prepare(int $campaign_id, array $subscriber_ids)
    {
        global $wpdb;

        $campaign = Campaign::get($campaign_id);

        if (!$campaign) {
            return new WP_Error('not_found', __('Campaign not found.', 'cps-bloom-mailer'), ['status' => 404]);
        }

        if (!in_array($campaign->status, ['draft', 'scheduled'], true)) {
            return new WP_Error('invalid_status', __('Campaign cannot be sent in its current status.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        if (empty($subscriber_ids)) {
            return new WP_Error('no_subscribers', __('No subscribers found for this campaign.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        // Fetch only id + email in one query, filtering to active subscribers only
        $bloom_table = $wpdb->prefix . BloomBridge::TABLE;
        $id_col      = BloomBridge::COL_ID;
        $email_col   = BloomBridge::COL_EMAIL;
        $status_col  = BloomBridge::COL_STATUS;

        $placeholders = implode(',', array_fill(0, count($subscriber_ids), '%d'));

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $subscribers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT {$id_col} AS id, {$email_col} AS email
                 FROM {$bloom_table}
                 WHERE {$id_col} IN ({$placeholders})
                   AND {$status_col} = %s",
                ...[...$subscriber_ids, BloomBridge::STATUS_ACTIVE]
            )
        );

        // A subscriber may have unsubscribed between resolution and send
        if (empty($subscribers)) {
            return new WP_Error('no_subscribers', __('No active subscribers found.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        // Filter out bounced/complained/unsubscribed addresses. This is the
        // last line of defense — bloom should already mark unsubscribes as
        // inactive, but bounces/complaints land here independently via the
        // suppression list, and bloom status alone won't catch those.
        $subscribers = Suppression::filter_subscribers($subscribers);

        if (empty($subscribers)) {
            return new WP_Error('no_subscribers', __('All matched subscribers are suppressed (bounced, complained, or unsubscribed).', 'cps-bloom-mailer'), ['status' => 422]);
        }

        $sends_table = $wpdb->prefix . 'cps_mailer_sends';

        // Clear previous pending sends for this campaign
        $wpdb->delete($sends_table, ['campaign_id' => $campaign_id, 'status' => 'pending'], ['%d', '%s']);

        // Bulk insert — one query instead of N inserts
        $values      = [];
        $value_items = [];

        foreach ($subscribers as $subscriber) {
            $value_items[] = '(%d, %d, %s, %s)';
            array_push($values, $campaign_id, (int) $subscriber->id, $subscriber->email, 'pending');
        }

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$sends_table} (campaign_id, subscriber_id, email, status) VALUES "
                    . implode(', ', $value_items),
                ...$values
            )
        );

        $total = count($subscribers);

        Campaign::update($campaign_id, [
            'status'           => 'sending',
            'total_recipients' => $total,
        ]);

        return $total;
    }

    /**
     * Resolve the full list of active subscriber IDs for a campaign.
     *
     * Reads the campaign's list/tag recipients config and returns a flat
     * array of subscriber IDs. Used by Automation to avoid passing an
     * empty array to prepare().
     *
     * @param int $campaign_id
     * @return int[]
     */
    public static function resolve_subscriber_ids(int $campaign_id): array
    {
        $campaign = Campaign::get($campaign_id);

        if (empty($campaign)) {
            return [];
        }

        // Build recipient config from campaign meta
        $included = [];
        if (! empty($campaign->list)) {
            $included[] = ['list' => $campaign->list];
        }

        $tags = is_string($campaign->tags) ? json_decode($campaign->tags, true) : (array) $campaign->tags;
        if (! empty($tags)) {
            foreach ($tags as $tag) {
                $included[] = ['tag' => $tag];
            }
        }

        // Fall back to all active subscribers when no list/tag is configured
        if (empty($included)) {
            return BloomBridge::get_active_subscriber_ids();
        }

        return BloomBridge::resolve_recipient_ids($included);
    }
}
