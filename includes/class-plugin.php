<?php

namespace ChicpixiesBloomMailer;
use ChicpixiesBloomMailer\Core;
use ChicpixiesBloomMailer\Templates\Templates;
use ChicpixiesBloomMailer\Campaigns\Tracker;
use ChicpixiesBloomMailer\Campaigns\Automation;
use ChicpixiesBloomMailer\Subscribers;

/**
 * Main Plugin Class
 * @package CChicpixiesBloom
 * @since 1.0.0
 */
class Plugin
{
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init();
        $this->init_hooks();
    }

    private function init()
    {
        spl_autoload_register([$this, 'load']);

        new Core\Admin();
        new Core\Rest();
        new Subscribers\BloomRest();
    }

    private function init_hooks()
    {
        add_filter('query_vars', [$this, 'add_query_vars'], 0);
        add_action('template_redirect', array($this, 'handle_public_endpoints'));
        add_action('init', ['ChicpixiesBloomMailer\Core\Installer', 'maybe_upgrade']);

        // Queue cron
        add_action('cps_mailer_process_queue', array('ChicpixiesBloomMailer\Campaigns\Queue', 'process'));
        add_action('cps_mailer_process_queue', ['ChicpixiesBloomMailer\Subscribers\Bounce', 'check_smtp_repeat_failures'], 20);
    }

    public function add_query_vars(array $vars): array
    {
        $vars[] = 'cps_mailer_action';
        return $vars;
    }

    public function handle_public_endpoints()
    {
        $action = get_query_var('cps_mailer_action');

        if (! $action) {
            return;
        }

        switch ($action) {
            case 'open':
                Tracker::handle_open();
                break;
            case 'click':
                Tracker::handle_click();
                break;
            case 'unsubscribe':
                Subscribers\Unsubscribe::handle();
                break;
            case 'ses_webhook':
                Subscribers\Bounce::handle_ses_webhook();
        }

        exit;
    }

    function load(string $class): bool
    {
        $namespace = 'ChicpixiesBloomMailer\\';

        if (!str_starts_with($class, $namespace)) {
            return false;
        }

        $relative      = substr($class, strlen($namespace));
        $relative_path = str_replace('\\', '/', $relative);
        $parts         = explode('/', $relative_path);

        // Convert CamelCase to kebab-case for each part
        $parts = array_map(function ($part) {
            return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $part));
        }, $parts);

        $class_file = 'class-' . array_pop($parts) . '.php';
        $dir_path   = !empty($parts) ? implode('/', $parts) . '/' : '';
        $file_path  = CPS_BLOOM_MAILER_DIR . 'includes/' . $dir_path . $class_file;
        
        if (file_exists($file_path)) {
            require_once $file_path;
            return true;
        }

        error_log("CPS Bloom: File not found for {$class} -> {$file_path}");
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        foreach ($trace as $i => $frame) {
            error_log("  #{$i} {$frame['file']}:{$frame['line']} {$frame['function']}");
        }
        return false;
    }

    public static function activate()
    {
        Core\Installer::run();
        Templates::import_defaults();
        self::schedule_cron();

        self::register_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        self::clear_cron();
        flush_rewrite_rules();
    }

    public static function uninstall()
    {
        // Clean up database tables and options if needed
        //Database::drop_tables();
    }

    private static function register_rewrite_rules()
    {
        add_rewrite_rule('^cps-bloom-mailer/open/?$', 'index.php?cps_mailer_action=open', 'top');
        add_rewrite_rule('^cps-bloom-mailer/click/?$', 'index.php?cps_mailer_action=click', 'top');
        add_rewrite_rule('^cps-bloom-mailer/unsubscribe/?$', 'index.php?cps_mailer_action=unsubscribe', 'top');
        add_rewrite_rule('^cps-bloom-mailer/ses-webhook/?$', 'index.php?cps_mailer_action=ses_webhook', 'top');
        add_rewrite_tag('%cps_mailer_action%', '([^&]+)');
    }

    private static function schedule_cron()
    {
        if (function_exists('as_schedule_recurring_action')) {
            if (! as_next_scheduled_action('cps_mailer_process_queue')) {
                as_schedule_recurring_action(time(), 300, 'cps_mailer_process_queue', array(), 'cps-bloom-mailer');
            }
        } else {
            if (! wp_next_scheduled('cps_mailer_process_queue')) {
                wp_schedule_event(time(), 'every_five_minutes', 'cps_mailer_process_queue');
            }
            add_filter('cron_schedules', array(__CLASS__, 'add_cron_interval'));
        }
    }

    private static function clear_cron()
    {
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('cps_mailer_process_queue', array(), 'cps-bloom-mailer');
            as_unschedule_all_actions(Automation::ACTION_HOOK, array(), 'cps-bloom-mailer');
            as_unschedule_all_actions(Automation::ACTION_SINGLE_HOOK, array(), 'cps-bloom-mailer');
            as_unschedule_all_actions('cps_mailer_sync_automations', array(), 'cps-bloom-mailer');
        } else {
            $timestamp = wp_next_scheduled('cps_mailer_process_queue');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'cps_mailer_process_queue');
            }
        }
    }

    public static function add_cron_interval($schedules)
    {
        $schedules['every_five_minutes'] = array(
            'interval' => 300,
            'display'  => __('Every 5 minutes', 'cps-bloom-mailer'),
        );
        return $schedules;
    }
}
