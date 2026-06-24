<?php

namespace ChicpixiesBloomMailer;

use WP_REST_Request;
use Wp_Error;

class Rest
{
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        $routes = [
            'save' => ['POST', 'save_campaign'],
            'reset' => ['DELETE', 'reset'],
            'preview' => ['POST', 'preview'],
            'campaign' => ['GET', 'get_campaign'],
            'campaigns' => ['GET', 'get_campaigns'],
            'templates' => ['GET', 'get_templates'],
            'send-demo' => ['POST', 'send_email_demo'],
            'send-campaign' => ['POST', 'send_campaign'],
            'settings' => ['POST', 'save_settings'],
            'lists' => ['GET', 'get_lists'],
            'tags' => ['GET', 'get_tags'],
            'stats/overview' => ['GET', 'get_overview'],
            'stats/campaigns/(?P<id>\d+)' => ['GET', 'get_campaign_stats'],
            'recipients/count' => ['POST', 'get_recipient_count'],
            'automations' => ['GET', 'get_automations'],
            'automations/create' => ['POST', 'create_automation'],
            'automations/(?P<id>\d+)/toggle' => ['POST', 'toggle_automation'],
            'automations/(?P<id>\d+)/delete' => ['DELETE', 'delete_automation'],
            'campaigns/bulk' => ['POST', 'bulk_action'],
            'campaigns/(?P<id>\d+)' => ['POST', 'campaign_action'],
            'campaigns/export' => ['GET', 'export_campaigns'],
            'campaigns/(?P<id>\d+)/duplicate' => ['POST', 'duplicate_campaign'],
            'campaigns/(?P<id>\d+)/resend-non-openers' => ['POST', 'resend_non_openers'],
            'suppressions' => ['GET', 'get_suppressions'],
            'suppressions/(?P<email>[^/]+)' => ['DELETE', 'delete_suppression'],
        ];

        foreach ($routes as $route => $info) {
            $method = $info[0];
            $callback = $info[1];

            register_rest_route('cps/v1', '/mailer/' . $route, [
                'methods'  => $method,
                'callback' => [$this, $callback],
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ]);
        }
    }

    public function get_campaign($request)
    {
        $id = $request->get_param('id');

        if (empty($id) || !is_numeric($id)) {
            return new WP_Error('invalid_id',  'Campaign ID is required and must be numeric', ['status' => 400]);
        }

        if ($id <= 0) {
            return new WP_Error('invalid_id',  'Invalid campaign ID',  ['status' => 400]);
        }

        $campaign = Campaign::get($id);

        if (!$campaign) {
            return new WP_Error('not_found', 'Campaign not found', ['status' => 404]);
        }

        return rest_ensure_response($campaign);
    }

    public function get_campaigns($request)
    {
        $per_page = max(1, min(100, absint($request->get_param('per_page')) ?: 20));
        $page     = max(1, absint($request->get_param('page')) ?: 1);
        $status   = sanitize_text_field($request->get_param('status') ?? '');
        $search   = sanitize_text_field($request->get_param('search') ?? '');
        $orderby  = sanitize_key($request->get_param('orderby') ?: 'created_at');
        $order    = strtoupper($request->get_param('order') ?: 'DESC');
        $order    = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';

        $args = [
            'limit'   => $per_page,
            'offset'  => ($page - 1) * $per_page,
            'orderby' => in_array($orderby, ['created_at', 'status', 'sent_at', 'title'], true) ? $orderby : 'created_at',
            'order'   => $order,
            'status'  => $status ?: null,
            'search'  => $search ?: null,
        ];

        $items = Campaign::get_all($args);

        $count_args = $args;
        unset($count_args['limit'], $count_args['offset']);
        $total = Campaign::count($count_args);

        return rest_ensure_response([
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / $per_page),
        ]);
    }

    public function get_templates($request)
    {
        $templates = Templates::get_all();
        if (empty($templates)) {
            return new WP_Error('not_found', 'No Templates found.', array('status' => 404));
        }

        $preview =  $request->get_param('preview') ?? false;
        if (!empty($preview)) {
            $templates = array_map(function ($template) {
                $t = (array) $template;
                $t['html'] = Parser::from_campaign($t);
                return $t;
            }, $templates);
        }

        return rest_ensure_response($templates);
    }

    /**
     * Save Campaign
     * @param $request
     * $request parameters:
     * - id (string) - The id of the campaign to save
     * - data (array) - The data data to save
     * @return \WP_REST_Response|WP_Error
     */
    public function save_campaign($request)
    {
        $params = $request->get_json_params();
        $campaign_id = $params['id'] ?? 0;
        $data = $params['data'] ?? null;
        $isTemplate = isset($params['template']);
        $isAuto = $params['auto'] ?? false;

        if ($isAuto && empty($campaign_id)) return;

        if (empty($data)) {
            return new WP_Error('missing_params', sprintf(/* translators: %s: campaign or template */__('%s data missing', 'chicpixies-subscriptions'), ucfirst($params['template'] ?? 'campaign')), array('status' => 400));
        }

        $campaign_data = Sanitize::campaigns([$campaign_id => $data], $isTemplate);
        $result = null;
        if (!empty($campaign_id)) {
            $result = Campaign::update($campaign_id, $campaign_data[$campaign_id], $isTemplate);
        } else {
            $result = Campaign::create($campaign_data[$campaign_id], $isTemplate);
        }

        if (is_wp_error($result)) {
            return $result;
        }

        Queue::invalidate_render_cache($campaign_id);
        return rest_ensure_response(array_merge(['success' => true], [
            'id'      => (int) $result,
            'message' => sprintf(
                /* translators: %s: campaign ID */
                __('%s successfully', 'cps-bloom-mailer'),
                $campaign_id ? 'Updated' : 'Created'
            ),
            'data' => Campaign::get($result, $isTemplate),
        ]));
    }

    public function campaign_action($request)
    {
        $id       = absint($request->get_param('id'));
        $action = sanitize_text_field($request->get_param('action'));
        $result = '';

        if ($action === 'delete') {
            $result = Installer::delete([$id]);
        } else {
            $result = Campaign::cancel_pause($id, $action);
        }

        if (is_wp_error($result)) {
            return $result;
        }

        $map = [
            'cancel' => 'cancelled',
            'pause' => 'paused',
            'deleted' => 'deleted'
        ];
        return rest_ensure_response(['status' => $map[$action]]);
    }

    public function bulk_action($request)
    {
        $action = sanitize_key($request->get_param('bulk_action') ?? '');
        $ids    = array_map('absint', (array) $request->get_param('ids'));
        $ids    = array_values(array_filter($ids));

        if (empty($ids)) {
            return new WP_Error('no_ids', __('No campaigns selected.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        $results = ['succeeded' => [], 'failed' => []];

        switch ($action) {
            case 'delete':
                $result = Installer::delete($ids);
                if (is_wp_error($result)) {
                    return $result;
                }
                $results['succeeded'] = $ids;
                break;

            case 'pause':
            case 'cancel':
                foreach ($ids as $id) {
                    $result = Campaign::cancel_pause($id, $action);
                    if (is_wp_error($result)) {
                        $results['failed'][] = $id;
                        continue;
                    }
                    $results['succeeded'][] = $id;
                }
                break;
            default:
                return new WP_Error('invalid_action', __('Unknown bulk action.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        return rest_ensure_response($results);
    }

    /**
     * Exports selected campaigns (or all matching current filters if no
     * ids given) as CSV. Streams directly rather than building the whole
     * response in memory, since campaign lists can get long over time.
     */
    public function export_campaigns($request)
    {
        $ids = array_filter(array_map('absint', explode(',', (string) $request->get_param('ids'))));

        if (! empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            global $wpdb;
            $table = $wpdb->prefix . 'cps_mailer_campaigns';
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $campaigns = $wpdb->get_results($wpdb->prepare(
                "SELECT id, title, subject, status, total_recipients, created_at, sent_at
                 FROM {$table} WHERE id IN ({$placeholders}) ORDER BY created_at DESC",
                ...$ids
            ));
        } else {
            $campaigns = Campaign::get_all(['limit' => 1000, 'offset' => 0, 'orderby' => 'created_at', 'order' => 'DESC']);
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="campaigns-' . gmdate('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Title', 'Subject', 'Status', 'Recipients', 'Created', 'Sent At']);

        foreach ($campaigns as $c) {
            fputcsv($out, [
                $c->id,
                $c->title,
                $c->subject,
                $c->status,
                $c->total_recipients,
                $c->created_at,
                $c->sent_at ?: '',
            ]);
        }

        fclose($out);
        exit;
    }

    public function send_email_demo($request)
    {
        $params = $request->get_json_params();
        $to       = sanitize_email($params['to'] ?? '');

        if (!is_email($to)) {
            return new WP_Error('invalid_email', __('Invalid email address.', 'cps-bloom-mailer'), ['status' => 400]);
        }

        $campaign = $params['campaign'] ?? [];

        if (empty(trim($campaign['blocks']))) {
            return new WP_Error('empty_blocks', __('Campaign content cannot be empty.', 'cps-bloom-mailer'));
        }

        $subject  = sanitize_text_field($campaign['subject'] ?? __('Test Email', 'cps-bloom-mailer'));

        $html  = Parser::generate(
            $subject,
            $campaign['header'] ?? [],
            $campaign['blocks'],
            $campaign['footer'] ?? [],
            $campaign['design'] ?? []
        );

        if (empty($html)) {
            return new WP_Error('no_content', __('No email content.', 'cps-bloom-mailer'), ['status' => 400]);
        }

        $merge_data = array();
        $subject = Helpers::replace_tags($subject, $merge_data);
        $html = Helpers::replace_tags($html, $merge_data);

        $mailer = Mailer_Factory::make();
        $sent = $mailer->send(array(
            'to'      => $to,
            'subject' => '[Test] ' . $subject,
            'html'    => $html,
            'from_email' => '',
            'from_name' => get_bloginfo('name') . ' [Test]',
            'reply_to' => ''
        ));

        if (!$sent) {
            return new WP_Error(
                'mail_failed',
                __('Failed to send test email. Check your WordPress mail configuration.', 'cps-bloom-mailer'),
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'message' => sprintf(/* translators: %s: email address */__('Demo email sent to %s.', 'cps-bloom-mailer'), $to),
        ]);
    }

    public function preview($request)
    {
        $campaign = $request->get_json_params();
        $subject  = sanitize_text_field($campaign['subject'] ?? __('Test Email', 'cps-bloom-mailer'));

        $html  = Parser::generate(
            $subject,
            $campaign['header'] ?? [],
            $campaign['blocks'] ?? '',
            $campaign['footer'] ?? [],
            $campaign['design'] ?? []
        );

        if (empty($html)) {
            return new WP_Error('no_content', __('No email content.', 'cps-bloom-mailer'), ['status' => 400]);
        }

        return rest_ensure_response([
            'success' => true,
            'html' => $html,
        ]);
    }

    /**
     * GET /cps/v1/mailer/lists
     * Returns distinct list values from the bloom subscribers table.
     */
    public function get_lists($request)
    {
        global $wpdb;

        $table = $wpdb->prefix . Bloom_Bridge::TABLE;
        $col   = Bloom_Bridge::COL_LIST;

        $rows = $wpdb->get_col(
            "SELECT DISTINCT {$col} AS list_value
             FROM {$table}
             WHERE {$col} IS NOT NULL
               AND {$col} != ''
             ORDER BY {$col} ASC"
        );

        $lists = array_values(
            array_map(fn($row) => [
                'id'    => $row->list_value,
                'title' => $row->list_value,
            ], $rows ?: [])
        );

        return rest_ensure_response($lists);
    }

    /**
     * GET /cps/v1/mailer/tags
     * Extracts and flattens all unique tag values from the JSON tags column.
     */
    public function get_tags($request)
    {
        global $wpdb;

        $table      = $wpdb->prefix . Bloom_Bridge::TABLE;
        $col        = Bloom_Bridge::COL_TAGS;

        $rows = $wpdb->get_col(
            "SELECT DISTINCT {$col}
             FROM {$table}
             WHERE {$col} IS NOT NULL
               AND {$col} != ''
               AND {$col} != '[]'"

        );

        // Flatten JSON arrays from every subscriber row into one unique set
        $all_tags = [];
        foreach ($rows as $raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $tag) {
                    $tag = trim((string) $tag);
                    if ($tag !== '') {
                        $all_tags[$tag] = true;
                    }
                }
            } else {
                // fallback: comma-separated plain text
                foreach (explode(',', $raw) as $tag) {
                    $all_tags[] = trim($tag);
                }
            }
        }

        $tags = array_values(array_unique(array_filter($all_tags)));
        sort($tags);

        return rest_ensure_response($tags);
    }

    /**
     * POST /cps/v1/mailer/recipients/count
     *
     * Body: { included: [{list?, tag?}], excluded: [{list?, tag?}] }
     * Returns: { count: int }
     */
    public function get_recipient_count($request)
    {
        $recipients = $request->get_param('recipients');

        if (empty($recipients)) {
            return rest_ensure_response(['count' => Bloom_Bridge::get_count()]);
        }

        $included = $recipients['included'] ?? [];
        $excluded = $recipients['excluded'] ?? [];

        // Filter out completely empty rows (user added a row but didn't pick anything)
        $included = array_filter($included, fn($r) => !empty($r['list']) || !empty($r['tag']));
        $excluded = array_filter($excluded, fn($r) => !empty($r['list']) || !empty($r['tag']));

        $included_ids = self::resolve_recipient_ids(array_values($included));

        if (!empty($excluded)) {
            $excluded_ids = self::resolve_recipient_ids(array_values($excluded));
            $included_ids = array_values(array_diff($included_ids, $excluded_ids));
        }

        return rest_ensure_response(['count' => count($included_ids)]);
    }

    public function send_campaign($request)
    {
        $params      = $request->get_json_params();
        $campaign_id = intval($params['campaign_id'] ?? 0);
        $recipients  = $params['recipients'] ?? [];
        $scheduled_at = $params['scheduled_at'] ?? null;

        if (!$campaign_id) {
            return new WP_Error('missing_campaign', __('Campaign ID is required.', 'cps-bloom-mailer'), ['status' => 400]);
        }

        // — Scheduled send —
        if (!empty($scheduled_at)) {
            return $this->schedule($campaign_id, $recipients);
        }

        // Resolve included/excluded rows → flat array of subscriber IDs
        $included = array_filter($recipients['included'] ?? [], fn($r) => !empty($r['list']) || !empty($r['tag']));
        $excluded = array_filter($recipients['excluded'] ?? [], fn($r) => !empty($r['list']) || !empty($r['tag']));

        $subscriber_ids = self::resolve_recipient_ids(array_values($included));

        // Nothing explicitly selected → send to all active subscribers
        if (empty($included)) {
            $all = Bloom_Bridge::get_active_subscribers();
            $subscriber_ids = array_column(array_map(fn($s) => (array) $s, $all), 'id');
        }

        if (!empty($excluded)) {
            $excluded_ids   = self::resolve_recipient_ids(array_values($excluded));
            $subscriber_ids = array_values(array_diff($subscriber_ids, $excluded_ids));
        }

        if (empty($subscriber_ids)) {
            return new WP_Error('no_recipients', __('No recipients matched the selected lists and tags.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        // Guard rail — configurable via filter
        $max = (int) apply_filters('cps_mailer_max_recipients', 50000);
        if (count($subscriber_ids) > $max) {
            return new WP_Error(
                'too_many_recipients',
                sprintf(__('Recipient count (%d) exceeds the maximum allowed (%d).', 'cps-bloom-mailer'), count($subscriber_ids), $max),
                ['status' => 422]
            );
        }

        $result = Sender::prepare($campaign_id, $subscriber_ids);

        if (is_wp_error($result)) {
            return $result;
        }

        Automation::dispatch_queue_processing();

        return rest_ensure_response([
            'success' => true,
            'count'   => count($subscriber_ids),
            'message' => sprintf(
                __('Campaign queued for %d subscriber(s).', 'cps-bloom-mailer'),
                count($subscriber_ids)
            ),
        ]);
    }

    public function schedule($campaign_id, $recipients)
    {
        // — Scheduled send —
        if (!empty($scheduled_at)) {
            $send_ts = strtotime($scheduled_at);

            if (!$send_ts || $send_ts <= time()) {
                return new WP_Error('invalid_schedule', __('Scheduled time must be in the future.', 'cps-bloom-mailer'), ['status' => 422]);
            }

            // Save status + scheduled_at on the campaign — recipients resolved fresh at fire time
            Campaign::update($campaign_id, [
                'status'       => 'scheduled',
                'scheduled_at' => gmdate('Y-m-d H:i:s', $send_ts),
            ]);

            $args = ['campaign_id' => $campaign_id, 'recipients' => $recipients];

            if (function_exists('as_schedule_single_action')) {
                as_schedule_single_action($send_ts, Automation::ACTION_SCHEDULED_CAMPAIGN, $args, 'cps-bloom-mailer');
            } else {
                wp_schedule_single_event($send_ts, Automation::ACTION_SCHEDULED_CAMPAIGN, $args);
            }

            return rest_ensure_response([
                'success'      => true,
                'scheduled'    => true,
                'scheduled_at' => gmdate('c', $send_ts),
                'message'      => sprintf(
                    __('Campaign scheduled for %s.', 'cps-bloom-mailer'),
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $send_ts)
                ),
            ]);
        }
    }

    /**
     * GET /cps/v1/mailer/automations
     * Returns paginated automations with their linked campaign title.
     */
    public function get_automations($request)
    {
        global $wpdb;

        $table     = $wpdb->prefix . 'cps_mailer_automations';
        $campaigns = $wpdb->prefix . 'cps_mailer_campaigns';

        $page     = max(1, (int) $request->get_param('page') ?: 1);
        $per_page = max(1, (int) $request->get_param('per_page') ?: 20);
        $offset   = ($page - 1) * $per_page;

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        $automations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, c.title AS campaign_title
             FROM {$table} a
             LEFT JOIN {$campaigns} c ON c.id = a.campaign_id
             ORDER BY a.created_at DESC
             LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        return rest_ensure_response([
            'items'       => $automations ?: [],
            'total'       => $total,
            'total_pages' => (int) ceil($total / $per_page),
        ]);
    }

    /**
     * POST /cps/v1/mailer/automations
     *
     * Body: { name, campaign_id, event }
     * event must be one of: new_subscriber | new_post | new_product
     */
    public function create_automation($request)
    {
        $params      = $request->get_json_params();
        $name        = sanitize_text_field($params['name'] ?? '');
        $campaign_id = intval($params['campaign_id'] ?? 0);
        $event       = sanitize_text_field($params['event'] ?? '');

        $allowed_events = ['new_subscriber', 'new_post', 'new_product'];

        if (empty($name)) {
            return new WP_Error('missing_name', __('Automation name is required.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        if (empty($campaign_id)) {
            return new WP_Error('missing_campaign', __('A campaign must be selected.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        if (! in_array($event, $allowed_events, true)) {
            return new WP_Error(
                'invalid_event',
                sprintf(
                    /* translators: %s: comma-separated list of valid events */
                    __('Event must be one of: %s.', 'cps-bloom-mailer'),
                    implode(', ', $allowed_events)
                ),
                ['status' => 422]
            );
        }

        $campaign = Campaign::get($campaign_id);
        if (empty($campaign)) {
            return new WP_Error('campaign_not_found', __('Campaign not found.', 'cps-bloom-mailer'), ['status' => 404]);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cps_mailer_automations';

        $inserted = $wpdb->insert(
            $table,
            [
                'name'        => $name,
                'campaign_id' => $campaign_id,
                'event'       => $event,
                'status'      => 'active',
                'created_at'  => current_time('mysql'),
            ],
            ['%s', '%d', '%s', '%s', '%s']
        );

        if (! $inserted) {
            return new WP_Error('db_error', __('Failed to create automation.', 'cps-bloom-mailer'), ['status' => 500]);
        }

        $automation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $wpdb->insert_id
        ));

        return rest_ensure_response($automation);
    }

    /**
     * POST /cps/v1/mailer/automations/{id}/toggle
     * Flips status between active <-> paused.
     */
    public function toggle_automation($request)
    {
        global $wpdb;

        $id    = intval($request->get_param('id'));
        $table = $wpdb->prefix . 'cps_mailer_automations';

        $automation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        if (!$automation) {
            return new WP_Error('not_found', __('Automation not found.', 'cps-bloom-mailer'), ['status' => 404]);
        }

        $new_status = $automation->status === 'active' ? 'paused' : 'active';

        $wpdb->update($table, ['status' => $new_status], ['id' => $id], ['%s'], ['%d']);

        return rest_ensure_response(['status' => $new_status]);
    }

    /**
     * DELETE /cps/v1/mailer/automations/{id}/delete
     */
    public function delete_automation($request)
    {
        global $wpdb;

        $id    = intval($request->get_param('id'));
        $table = $wpdb->prefix . 'cps_mailer_automations';

        $automation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
        if (!$automation) {
            return new WP_Error('not_found', __('Automation not found.', 'cps-bloom-mailer'), ['status' => 404]);
        }

        // Cancel any pending Action Scheduler actions for this automation
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions(Automation::ACTION_HOOK, ['automation_id' => $id], 'cps-bloom-mailer');
        }

        $wpdb->delete($table, ['id' => $id], ['%d']);

        return rest_ensure_response(['deleted' => true, 'id' => $id]);
    }

    public function get_overview($request)
    {
        return rest_ensure_response(Stats::get_overview());
    }

    public function get_campaign_stats($request)
    {
        $id       = absint($request->get_param('id'));
        $campaign = Campaign::get($id);

        if (empty($campaign)) {
            return new WP_Error('not_found', __('Campaign not found.', 'cps-bloom-mailer'), ['status' => 404]);
        }

        return rest_ensure_response([
            'campaign' => $campaign,
            'stats'    => Stats::get_campaign_stats($id),
        ]);
    }

    public function save_settings($request)
    {
        $settings = $request->get_json_params();

        $fields = array('mailer', 'from_name', 'from_email', 'batch_size', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'ses_key', 'ses_secret', 'ses_region',);

        foreach ($fields as $field) {
            if (isset($settings[$field])) {
                Settings::set($field, sanitize_text_field($settings[$field]));
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'settings' => $settings,
            'message' => __('Settings saved successfully', 'chicpixies-subscriptions')
        ));
    }

    public function reset($request)
    {
        $params = $request->get_json_params();
        $type = $params['type'] ?? '';
        $success = ['message' => __('Reset successful!', 'chicpixies-subscriptions'),];
        
        if ($type === 'templates') {
            $templates = Templates::reset();
            if (is_wp_error($templates)) {
                return $templates;
            }
            $success['templates'] = $templates;
        }

        if ($type === 'settings') {
            Settings::delete();
            $default_settings = Settings::get_all();
            $success['settings'] = $default_settings;
        }

        return rest_ensure_response($success);
    }

    public function duplicate_campaign($request)
    {
        $id     = absint($request->get_param('id'));
        $new_id = Duplicator::duplicate($id);

        if (is_wp_error($new_id)) {
            return $new_id;
        }

        $campaign = Campaign::get($new_id);
        return rest_ensure_response($campaign);
    }

    public function resend_non_openers($request)
    {
        $id              = absint($request->get_param('id'));
        $subject_override = sanitize_text_field($request->get_param('subject') ?? '');

        $result = Resend::to_non_openers($id, $subject_override);

        if (is_wp_error($result)) {
            return $result;
        }

        $campaign = Campaign::get($result);
        return rest_ensure_response($campaign);
    }

    public function get_suppressions($request)
    {
        $page    = max(1, absint($request->get_param('page') ?: 1));
        $reason  = sanitize_key($request->get_param('reason') ?? '');

        $result = Suppression::list($page, 50, $reason ?: null);

        return rest_ensure_response($result);
    }

    public function delete_suppression($request)
    {
        $email = sanitize_email(urldecode($request->get_param('email')));

        if (empty($email)) {
            return new WP_Error('invalid_email', __('Invalid email address.', 'cps-bloom-mailer'), ['status' => 422]);
        }

        Suppression::remove($email);

        return rest_ensure_response(['removed' => true, 'email' => $email]);
    }

    /**
     * Resolve a set of { list, tag } rows into a flat unique array of subscriber IDs.
     * Each row is OR-ed (union), matching FluentCRM's "Add More" behaviour.
     *
     * @param array $rows  e.g. [['list' => 'Newsletter', 'tag' => null], ...]
     * @return int[]
     */
    public static function resolve_recipient_ids(array $rows): array
    {
        global $wpdb;

        $table      = $wpdb->prefix . Bloom_Bridge::TABLE;
        $id_col     = Bloom_Bridge::COL_ID;
        $status_col = Bloom_Bridge::COL_STATUS;
        $list_col   = Bloom_Bridge::COL_LIST;
        $tags_col   = Bloom_Bridge::COL_TAGS;

        $union_parts  = [];
        $union_values = [];

        foreach ($rows as $row) {
            $list = !empty($row['list']) ? trim($row['list']) : null;
            $tag  = !empty($row['tag'])  ? trim($row['tag'])  : null;

            $where_parts  = ["{$status_col} = %s"];
            $where_values = [Bloom_Bridge::STATUS_ACTIVE];

            if ($list !== null) {
                $where_parts[]  = "{$list_col} = %s";
                $where_values[] = $list;
            }

            if ($tag !== null) {
                $where_parts[]  = "JSON_CONTAINS({$tags_col}, %s)";
                $where_values[] = json_encode($tag);
            }

            $union_parts[]  = 'SELECT ' . $id_col . ' FROM ' . $table . ' WHERE ' . implode(' AND ', $where_parts);
            $union_values   = array_merge($union_values, $where_values);
        }

        if (empty($union_parts)) {
            return [];
        }

        // UNION (not UNION ALL) deduplicates across rows automatically in SQL
        $sql = implode(' UNION ', $union_parts);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return array_map('intval', $wpdb->get_col(
            $wpdb->prepare($sql, ...$union_values)
        ));
    }
}
new Rest();
