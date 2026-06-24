<?php

namespace ChicpixiesBloomMailer;

use DateTime;
use DateTimeZone;
use Exception;

if (! defined('ABSPATH')) {
    exit;
}

class Automation
{
    const TABLE = 'cps_mailer_automations';
    const ACTION_HOOK = 'cps_mailer_run_automation';
    const ACTION_SINGLE_HOOK = 'cps_mailer_send_single_subscriber';
    const ACTION_SCHEDULED_CAMPAIGN = 'cps_mailer_send_scheduled_campaign';

    public static function init()
    {
        add_action('transition_post_status', [__CLASS__, 'handle_new_post'], 10, 3);
        add_action('transition_post_status', [__CLASS__, 'handle_new_product'], 10, 3);
        add_action('cps_bloom_subscriber_created', [__CLASS__, 'on_new_subscriber'], 10, 1);

        add_action(self::ACTION_HOOK, [__CLASS__, 'run_automation_action']);
        add_action(self::ACTION_SINGLE_HOOK, [__CLASS__, 'run_single_subscriber_action'], 10, 3);
        add_action(self::ACTION_SCHEDULED_CAMPAIGN, [__CLASS__, 'run_scheduled_campaign'], 10, 2);

        if (!function_exists('as_schedule_recurring_action')) {
            self::register_fallback_hooks();
        }
    }

    public static function handle_new_post($new_status, $old_status, $post)
    {
        if ($new_status !== 'publish' || $old_status === 'publish' || $post->post_type !== 'post') {
            return;
        }

        self::trigger_event('new_post', ['post_id' => $post->ID, 'post' => $post]);
    }

    public static function handle_new_product($new_status, $old_status, $post)
    {
        if ($new_status !== 'publish' || $old_status === 'publish' || $post->post_type !== 'product') {
            return;
        }

        self::trigger_event('new_product', ['post_id' => $post->ID, 'post' => $post]);
    }

    public static function on_new_subscriber($subscriber)
    {
        if (is_numeric($subscriber)) {
            $subscriber = Bloom_Bridge::get_subscriber(intval($subscriber));
        }

        if (empty($subscriber)) {
            return;
        }

        self::trigger_event('new_subscriber', ['subscriber' => $subscriber]);
    }

    private static function trigger_event($event, $meta = [])
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $automations = self::get_active($event);
        if (empty($automations)) {
            return;
        }

        foreach ($automations as $automation) {
            $campaign = Campaign::get($automation->campaign_id);
            if (empty($campaign)) {
                continue;
            }

            // For new_subscriber automations (e.g. welcome emails) we still want
            // to send even if the campaign is in 'scheduled' status.
            // For new_post / new_product, skip if the campaign was already fully sent.
            $skip_statuses = ($event === 'new_subscriber') ? ['paused'] : ['sent', 'paused'];
            if (in_array($campaign->status, $skip_statuses, true)) {
                continue;
            }

            // Update last_triggered_at
            $wpdb->update(
                $table,
                ['last_triggered_at' => current_time('mysql')],
                ['id' => $automation->id],
                ['%s'],
                ['%d']
            );
            $subscriber = $meta['subscriber'] ?? null;
            if (!empty($subscriber) && $event === 'new_subscriber') {
                $handled = self::new_subscriber_event($subscriber, $automation, $campaign);

                if ($handled) {
                    continue;
                }

                // Fallback: no timezone data available, prepare and send immediately.
                Sender::prepare($campaign->id, [$subscriber->id]);
                self::dispatch_queue_processing();
                continue;
            }

            // For new_post/new_product we simply prepare the campaign and process queue
            Sender::prepare($campaign->id);
            self::dispatch_queue_processing();
        }
    }

    /**
     * Schedule a per-subscriber send at the right local time.
     *
     * campaign->scheduled_at is expected to be stored in UTC (MySQL datetime).
     * The time component (H:i:s) is treated as the desired local send time for
     * each subscriber; we convert it to a UTC timestamp using their timezone or
     * UTC offset.
     *
     * @return bool  true if a scheduled action was enqueued, false if we should fall back.
     */
    private static function new_subscriber_event($subscriber, $automation, $campaign)
    {
        if (empty($campaign->scheduled_at)) {
            return false;
        }

        if (empty($subscriber->timezone) && ! isset($subscriber->utc_offset)) {
            return false;
        }

        $send_ts = null;
        $scheduled_time = date('H:i:s', strtotime($campaign->scheduled_at));

        // timezone handling
        if (
            ! empty($subscriber->timezone) &&
            in_array($subscriber->timezone, timezone_identifiers_list(), true)
        ) {
            try {
                $tz       = new DateTimeZone($subscriber->timezone);
                $local_dt = new DateTime('today ' . $scheduled_time, $tz);
                $now_local = new DateTime('now', $tz);

                if ($local_dt <= $now_local) {
                    $local_dt->modify('+1 day');
                }

                $utc = clone $local_dt;
                $utc->setTimezone(new DateTimeZone('UTC'));
                $send_ts = $utc->getTimestamp();
            } catch (Exception $e) {
                $send_ts = null;
            }
        }

        // utc offset fallback
        elseif (isset($subscriber->utc_offset)) {
            $offset = $subscriber->utc_offset;

            // utc_offset may be stored as hours (e.g. 1, -5) or full seconds.
            $offset_secs = (abs($offset) <= 14)
                ? intval($offset) * HOUR_IN_SECONDS
                : intval($offset);

            // Build a UTC DateTime set to the scheduled wall-clock time,
            // then subtract the subscriber's offset to get the true UTC send time.
            // e.g. want to send at 09:00 for UTC+1 subscriber → send at 08:00 UTC.
            $timeparts = explode(':', $scheduled_time);
            $utc_base = new DateTime('now', new DateTimeZone('UTC'));
            $utc_base->setTime(
                intval($timeparts[0] ?? 0),
                intval($timeparts[1] ?? 0),
                intval($timeparts[2] ?? 0)
            );

            // Treat the scheduled time as the subscriber's local time and
            // convert to UTC by subtracting their offset.
            $send_ts = $utc_base->getTimestamp() - $offset_secs;

            if ($send_ts <= time()) {
                $send_ts += DAY_IN_SECONDS;
            }
        }

        if (empty($send_ts)) {
            return false;
        }

        $args = [
            'automation_id' => $automation->id,
            'campaign_id'   => $campaign->id,
            'subscriber_id' => $subscriber->id ?? null,
        ];

        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action($send_ts, self::ACTION_SINGLE_HOOK, $args, 'cps-bloom-mailer');
        } else {
            if (! wp_next_scheduled(self::ACTION_SINGLE_HOOK, $args)) {
                wp_schedule_single_event($send_ts, self::ACTION_SINGLE_HOOK, $args);
            }
        }

        return true;
    }

    /**
     * Fired by ACTION_SINGLE_HOOK.
     *
     * Action Scheduler unpacks the args array and passes each value as a
     * separate positional argument, so declare them individually.
     */
    public static function run_single_subscriber_action($automation_id = null, $campaign_id = null, $subscriber_id = null)
    {
        if (empty($campaign_id) || empty($subscriber_id)) {
            return;
        }

        global $wpdb;
        $sends_table = $wpdb->prefix . 'cps_mailer_sends';

        // avoid duplicates
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$sends_table} WHERE campaign_id = %d AND subscriber_id = %d LIMIT 1",
            $campaign_id,
            $subscriber_id
        ));

        if ($exists) {
            return;
        }

        // resolve subscriber email
        $subscriber = null;
        $subscriber = Bloom_Bridge::get_subscriber($subscriber_id);

        if (empty($subscriber) || empty($subscriber->email)) {
            return;
        }

        $wpdb->insert(
            $sends_table,
            [
                'campaign_id'   => $campaign_id,
                'subscriber_id' => $subscriber_id,
                'email'         => $subscriber->email,
                'status'        => 'pending',
            ],
            ['%d', '%d', '%s', '%s']
        );

        // trigger queue processingargs
        self::dispatch_queue_processing();
    }

    /**
     * Fired by ACTION_HOOK.
     *
     * Action Scheduler passes 'automation_id' as the first positional argument.
     */
    public static function run_automation_action($automation_id = null)
    {
        $automation_id = intval($automation_id);
        if (empty($automation_id)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $automation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $automation_id));
        if (empty($automation)) {
            return;
        }

        // Update last triggered
        $wpdb->update(
            $table,
            ['last_triggered_at' => current_time('mysql')],
            ['id' => $automation_id],
            ['%s'],
            ['%d']
        );

        $campaign = Campaign::get($automation->campaign_id);
        if (empty($campaign)) {
            return;
        }

        // If campaign is draft, set scheduled
        if (in_array($campaign->status, ['draft', 'scheduled'], true)) {
            Sender::prepare($campaign->id);
            self::dispatch_queue_processing();
        }

        // WP Cron fallback: re-schedule next occurrence manually.
        // Action Scheduler handles recurrence automatically so this block is
        // only reached when AS is not available.
        if (!function_exists('as_schedule_recurring_action') && !empty($automation->frequency)) {
            $interval = self::frequency_to_interval($automation->frequency);
            $hook = self::ACTION_HOOK . '_fallback_' . $automation->id;
            wp_schedule_single_event(time() + $interval, $hook, ['automation_id' => $automation->id]);
        }
    }

    public static function run_scheduled_campaign($campaign_id, $recipients)
    {
        $included = array_filter($recipients['included'] ?? [], fn($r) => !empty($r['list']) || !empty($r['tag']));
        $excluded = array_filter($recipients['excluded'] ?? [], fn($r) => !empty($r['list']) || !empty($r['tag']));

        // Resolve fresh at send time — more accurate than resolving at schedule time
        $subscriber_ids = (new Api())->resolve_recipient_ids(array_values($included));

        if (!empty($excluded)) {
            $excluded_ids   = (new Api())->resolve_recipient_ids(array_values($excluded));
            $subscriber_ids = array_values(array_diff($subscriber_ids, $excluded_ids));
        }

        Sender::prepare($campaign_id, $subscriber_ids);
        self::dispatch_queue_processing();
    }

    /**
     * Schedule recurring automations (frequency = daily|weekly|monthly|yearly | event = 'scheduled')
     * 
     * campaign->scheduled_at is stored in UTC. We convert to local time only
     * for determining the initial fire time; after that the interval drives recurrence.
     */
    public static function schedule_recurring_automations()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $automations = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE event = 'scheduled' AND status = 'active'"
        );

        if (empty($automations)) {
            return;
        }

        foreach ($automations as $automation) {
            // Determine schedule interval
            $interval = self::frequency_to_interval($automation->frequency);
            $campaign = Campaign::get($automation->campaign_id);
            $next_run_ts = null;

            if (!empty($campaign->scheduled_at)) {
                // scheduled_at is stored in UTC; convert to local WP time for the
                // initial run so it fires at the right wall-clock time.
                $next_run_ts = strtotime(get_date_from_gmt($campaign->scheduled_at));
            }

            if (empty($next_run_ts) || $next_run_ts < time()) {
                $next_run_ts = time();
            }

            $args = ['automation_id' => $automation->id];
            if (function_exists('as_schedule_recurring_action')) {
                $exists = as_next_scheduled_action(self::ACTION_HOOK, $args, 'cps-bloom-mailer');
                if (empty($exists)) {
                    as_schedule_recurring_action($next_run_ts, $interval, self::ACTION_HOOK, $args, 'cps-bloom-mailer');
                }
            } else {
                // Fallback: schedule a single event via wp_schedule_single_event and re-schedule after running
                $hook = self::ACTION_HOOK . '_fallback_' . $automation->id;
                if (! wp_next_scheduled($hook, $args)) {
                    wp_schedule_single_event($next_run_ts, $hook, $args);
                }
                // The fallback hook listener is registered in register_fallback_hooks(),
                // called from init(), not here — avoids stacking duplicate listeners.
            }
        }
    }

    /**
     * Register WP Cron fallback action hooks for all active scheduled automations.
     * Called once from init() when Action Scheduler is not available.
     */
    private static function register_fallback_hooks()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $ids = $wpdb->get_col(
            "SELECT id FROM {$table} WHERE event = 'scheduled' AND status = 'active'"
        );

        foreach ($ids as $id) {
            $hook = self::ACTION_HOOK . '_fallback_' . intval($id);
            add_action($hook, [__CLASS__, 'run_automation_action'], 10, 1);
        }
    }

    public static function dispatch_queue_processing()
    {
        // Prefer Action Scheduler to trigger queue processing
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(time() + 5, 'cps_mailer_process_queue', array(), 'cps-bloom-mailer');
        } else {
            // Fallback to WP Cron
            if (!wp_next_scheduled('cps_mailer_process_queue')) {
                wp_schedule_single_event(time() + 5, 'cps_mailer_process_queue');
            }
        }
    }

    private static function frequency_to_interval($frequency)
    {
        // Return seconds interval for schedule_recurring_action. Action Scheduler accepts human schedule names in some installs
        switch ($frequency) {
            case 'daily':
                return DAY_IN_SECONDS;
            case 'weekly':
                return WEEK_IN_SECONDS;
            case 'monthly':
                return DAY_IN_SECONDS * 30;
            case 'yearly':
                return DAY_IN_SECONDS * 365;
            default:
                return DAY_IN_SECONDS;
        }
    }

    private static function get_active($event)
    {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE event = %s AND status = %s",
            $event,
            'active'
        ));
    }
}
// Initialize
Automation::init();
