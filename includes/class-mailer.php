<?php

namespace ChicpixiesBloomMailer;

if (! defined('ABSPATH')) {
	exit;
}

class Mailer
{

	private static $instance = null;

	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}

	private function init()
	{
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies()
	{
		$dir = CPS_BLOOM_MAILER_DIR;

		$files = [
			// Core
			'includes/class-helpers.php',
			'includes/class-installer.php',
			'includes/class-settings.php',

			// Templates
			'includes/templates/class-blocks.php',
			'includes/templates/class-templates.php',

			// Stats & tracking
			'includes/class-stats.php',
			'includes/class-tracker.php',

			// Campaigns
			'includes/campaigns/class-campaign.php',
			'includes/campaigns/class-parser.php',
			'includes/campaigns/class-sender.php',
			'includes/campaigns/class-automation.php',
			'includes/campaigns/class-queue.php',
			'includes/campaigns/class-duplicator.php',
			'includes/campaigns/class-resend.php',

			// Mailers
			'includes/mailers/class-mailer-base.php',
			'includes/mailers/class-mailer-smtp.php',
			'includes/mailers/class-mailer-ses.php',
			'includes/mailers/class-mailer-factory.php',
			'includes/mailers/class-bounce.php',
			'includes/mailers/class-suppression.php',

			// Subscribers
			'includes/subscribers/class-bloom-rest.php',
			'includes/subscribers/class-bloom-bridge.php',
			'includes/subscribers/class-unsubscribe.php',

			// Admin
			'admin/class-admin.php',
			'admin/class-sanitize.php',
			'admin/class-rest.php',
		];

		foreach ($files as $file) {
			require_once $dir . $file;
		}

		if (! class_exists('ActionScheduler')) {
			require_once $dir . 'libraries/action-scheduler/action-scheduler.php';
		}
	}

	private function init_hooks()
	{
		add_filter('query_vars', [$this, 'add_query_vars'], 0);
		add_action('template_redirect', array($this, 'handle_public_endpoints'));
		add_action('init', ['ChicpixiesBloomMailer\\Installer', 'maybe_upgrade']);

		// Queue cron
		add_action('cps_mailer_process_queue', array('ChicpixiesBloomMailer\\Queue', 'process'));
		add_action('cps_mailer_process_queue', ['ChicpixiesBloomMailer\\Bounce', 'check_smtp_repeat_failures'], 20);
	}

	private static function register_rewrite_rules()
	{
		add_rewrite_rule('^cps-bloom-mailer/open/?$', 'index.php?cps_mailer_action=open', 'top');
		add_rewrite_rule('^cps-bloom-mailer/click/?$', 'index.php?cps_mailer_action=click', 'top');
		add_rewrite_rule('^cps-bloom-mailer/unsubscribe/?$', 'index.php?cps_mailer_action=unsubscribe', 'top');
		add_rewrite_rule('^cps-bloom-mailer/ses-webhook/?$', 'index.php?cps_mailer_action=ses_webhook', 'top');
		add_rewrite_tag('%cps_mailer_action%', '([^&]+)');
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
				Unsubscribe::handle();
				break;
			case 'ses_webhook':
				Bounce::handle_ses_webhook();
		}

		exit;
	}

	public static function activate()
	{
		Installer::run();
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
