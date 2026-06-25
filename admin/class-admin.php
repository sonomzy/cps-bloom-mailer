<?php

namespace ChicpixiesBloomMailer;

use WP_Block_Editor_Context;

if (! defined('ABSPATH')) {
	exit;
}

class Admin
{
	public function __construct()
	{
		add_action('admin_menu', array($this, 'register_menus'));
		add_action('init', [$this, 'register_blocks']);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
		add_action('wp_ajax_cps_mailer_get_stats', array($this, 'ajax_get_stats'));
		add_action('wp_ajax_cps_mailer_get_overview', array($this, 'ajax_get_overview'));
		add_action('in_admin_header', array($this, 'hide_notice'), 20);
		add_filter('block_type_metadata', array($this, 'disable_advanced_tab'));
		add_filter('wp_feed_cache_transient_lifetime', fn() => HOUR_IN_SECONDS * 6);
	}

	public function hide_notice()
	{
		if (!function_exists('get_current_screen')) {
			return false;
		}

		$current_screen = get_current_screen();
		if (strpos($current_screen->id, 'cps-bloom-mailer') !== false) {
			remove_all_actions('admin_notices');
			remove_all_actions('all_admin_notices');
		}
	}

	public function register_menus()
	{
		add_menu_page(
			__('Bloom Mailer', 'cps-bloom-mailer'),
			__('Bloom Mailer', 'cps-bloom-mailer'),
			'manage_options',
			'cps-bloom-mailer',
			array($this, 'page_campaigns'),
			'dashicons-email-alt',
			30
		);

		add_submenu_page(
			'cps-bloom-mailer',
			__('Campaigns', 'cps-bloom-mailer'),
			__('Campaigns', 'cps-bloom-mailer'),
			'manage_options',
			'cps-bloom-mailer',
			array($this, 'page_campaigns')
		);

		add_submenu_page(
			'cps-bloom-mailer',
			__('Settings', 'cps-bloom-mailer'),
			__('Settings', 'cps-bloom-mailer'),
			'manage_options',
			'cps-bloom-mailer-settings',
			array($this, 'page_settings')
		);
	}

	public function enqueue_assets($hook)
	{
		if (!isset($_GET['page']) || strpos($hook, 'cps-bloom-mailer') === false) {
			return;
		}

		if (!function_exists('get_current_screen')) {
			return false;
		}

		if ($_GET['page'] !== 'cps-bloom-mailer-settings' && $_GET['page'] !== 'cps-bloom-mailer') {
			return;
		}

		if ($_GET['page'] === 'cps-bloom-mailer') {
			$current_screen = get_current_screen();
			$current_screen->is_block_editor(true);

			// Block editor styles
			wp_enqueue_style('wp-block-editor');
			wp_enqueue_style('wp-edit-blocks');
			wp_enqueue_style('wp-block-library');
			wp_enqueue_style('wp-components');
			wp_enqueue_style('wp-block-library-theme');
			wp_enqueue_style('wp-reset-editor');
			do_action('enqueue_block_assets');
			if (function_exists('wp_enqueue_media')) {
				wp_enqueue_media();
			}
		}

		$asset_file = include CPS_BLOOM_MAILER_DIR . 'assets/build/index.asset.php';
		wp_enqueue_style('cps-bloom-mailer-editor', CPS_BLOOM_MAILER_URL . 'assets/build/index.css', ['wp-components', 'wp-edit-blocks', 'wp-block-library'], $asset_file['version']);
		wp_enqueue_script('cps-bloom-mailer-editor', CPS_BLOOM_MAILER_URL . 'assets/build/index.js', $asset_file['dependencies'], $asset_file['version'], true);
		wp_add_inline_style('cps-bloom-mailer-editor', wp_get_global_stylesheet());

		$localize_data = [];

		if ($_GET['page'] === 'cps-bloom-mailer-settings') {
			$localize_data = [
				'settings' => Settings::get_all(),
				'hook' => home_url('/cps-bloom-mailer/ses-webhook/')
			];
		} else {
			$localize_data = array(
				'placeholders'	=> Helpers::get_placeholders(),
				'editor_settings' => $this->block_editor_settings(),
				'fontFamilies' => Helpers::get_font_family_options(),
				'default' => self::default(),
				'events' => Helpers::get_events(),
				'id' => $_REQUEST['c'] ?? 0,
				'restUrl' => rest_url('cps/v1/mailer'),
				'nonce'   => wp_create_nonce('wp_rest'),
			);
		}

		wp_localize_script('cps-bloom-mailer-editor', 'cbmData', apply_filters('cps_mailer_localize_campaign_data', $localize_data));
		// Block editor needs this global
		wp_add_inline_script(
			'wp-blocks',
			'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode(get_block_editor_server_block_settings()) . ');'
		);
	}

	public function page_campaigns()
	{
		if (isset($_REQUEST['c'])) {
			echo $this->campaign_edit();
			return;
		}

		echo $this->campaigns_page();
	}

	private function campaign_edit()
	{
		return '<div id="cps-campaign-edit" class="wrap block-editor"></div>';
	}

	private function campaigns_page()
	{
		return '<div id="cps-bloom-mailer-admin" class="block-editor"></div>';
	}

	public function page_settings()
	{
		echo '<div id="cps-bloom-mailer-settings"></div>';
	}

	// public function page_stats()
	// {
	// 	$campaign_id = intval($_GET['campaign_id'] ?? 0);
	// 	include CPS_BLOOM_MAILER_DIR . 'admin/views/stats.php';
	// }

	public function register_blocks(): void
	{
		foreach (['product', 'post', 'socials', 'social'] as $block) {
			register_block_type_from_metadata(
				CPS_BLOOM_MAILER_DIR . "src/gutenberg/blocks/{$block}"
			);
		}
	}

	private function block_editor_settings()
	{
		$context  = new WP_Block_Editor_Context(['name' => 'cps-bloom-mailer/email-editor']);

		$settings = get_block_editor_settings([], $context);
		$settings['allowedBlockTypes'] = $this->getAllowedBlocks();
		$settings['allowedMimeTypes'] = [
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
		];
		$settings['__experimentalDiscussionSettings'] = false;
		$settings['__unstableResolvedAssets'] = $this->getResolvedAssets();
		$settings['gradients'] = false;
		$settings['__experimentalFeatures']['appearanceTools'] = true;
		$settings['__experimentalFeatures']['custom'] = false;

		// Disable Gutenberg color presets
		$settings['__experimentalFeatures']['color']['defaultGradients'] = false;
		$settings['__experimentalFeatures']['color']['customDuotone'] = false;
		$settings['__experimentalFeatures']['color']['defaultDuotone'] = false;
		$settings['__experimentalFeatures']['color']['duotone'] = false;
		$settings['__experimentalFeatures']['color']['gradients'] = false;
		$settings['__experimentalFeatures']['color']['defaultPalette'] = true;

		//dimensions
		$settings['__experimentalFeatures']['dimensions'] = false;

		//shadow
		$settings['__experimentalFeatures']['shadow']['presets']['theme'] = false;

		//$settings['disableLayoutStyles'] = true;
		$settings['__experimentalFeatures']['background'] = false;
		$settings['__experimentalFeatures']['position'] = false;
		$settings['__experimentalFeatures']['lightbox'] = false;
		$settings['enableCustomUnits'] = false;

		//blocks
		$settings['__experimentalFeatures']['blocks'] = $this->blocks();

		//spacing
		$settings['spacingSizes'] = $this->spacingSizes();
		$settings['__experimentalFeatures']['spacing']['spacingSizes'] = [
			'default' => $this->spacingSizes()
		];
		$settings['__experimentalFeatures']['spacing']['spacingScale'] = [
			'default' => ['operator' => '*', 'increment' => 1.5, 'steps' => 7, 'mediumStep' => 24, 'unit' => 'px',]
		];

		//typography
		$settings['fontSizes'] = $this->fontSizes();
		$settings['__experimentalFeatures']['typography']['fontSizes'] = [
			'default' => $this->fontSizes()
		];
		$settings['__experimentalFeatures']['typography']['fontFamilies'] = [
			'default' => $this->fontFamilies()
		];
		$settings['__experimentalFeatures']['typography']['textIndent'] = false;
		$settings['__experimentalFeatures']['typography']['textColumns'] = false;
		$settings['__experimentalFeatures']['typography']['writingMode'] = false;
		$settings['__experimentalFeatures']['typography']['fluid'] = false;
		$settings['__experimentalFeatures']['typography']['units'] = ['px'];
		$settings['__experimentalFeatures']['typography']['dropCap'] = false;


		// Layout configurations
		$settings['__experimentalFeatures']['layout'] = [
			'contentSize' => '600px',
			'wideSize' => '700px',
		];

		return $settings;
	}

	private function blocks()
	{
		return [
			'core/image' => false,

			'core/buttons'   => [
				'border'      => [
					'radius' => false,
				],
				'spacing'     => [
					'blockGap' => false,
				],
				'layout'      => false,
				'contentRole' => false
			],
			'core/social-links'   => [
				'spacing'     => [
					'blockGap' => false,
					'margin' => false,
				],
				'layout'      => false,
				'contentRole' => false,
			],
		];
	}

	private function spacingSizes()
	{
		return [
			['name' => '2X-Small', 'slug' => '20', 'size' => '4px'],
			['name' => 'X-Small',  'slug' => '30', 'size' => '8px'],
			['name' => 'Small',    'slug' => '40', 'size' => '16px'],
			['name' => 'Medium',   'slug' => '50', 'size' => '24px'],
			['name' => 'Large',    'slug' => '60', 'size' => '32px'],
			['name' => 'X-Large',  'slug' => '70', 'size' => '48px'],
			['name' => '2X-Large', 'slug' => '80', 'size' => '64px'],
		];
	}

	private function fontSizes()
	{
		return [
			['name' => 'Small', 'slug' => 'cm-small', 'size' => '13px'],
			['name' => 'Regular',  'slug' => 'cm-regular', 'size' => '16px'],
			['name' => 'Medium',  'slug' => 'cm-medium', 'size' => '20px'],
			['name' => 'Large',    'slug' => 'cm-large', 'size' => '36px'],
			['name' => 'Extra Large',   'slug' => 'cm-x-large', 'size' => '42px'],
		];
	}

	private function fontFamilies()
	{
		$f = fn($n, $s, $ff) => ['name' => $n, 'slug' => $s, 'fontFamily' => $ff];

		return [
			$f('System UI',       'system-ui',      "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'"),
			$f('Arial',           'arial',          "Arial, 'Helvetica Neue', Helvetica, sans-serif"),
			$f('Georgia',         'georgia',        "Georgia, Times, 'Times New Roman', serif"),
			$f('Helvetica',       'helvetica',      "Helvetica, Arial, Verdana, sans-serif"),
			$f('Courier New',     'courier-new',    "'Courier New', Courier, 'Lucida Sans Typewriter', monospace"),
			$f('Times New Roman', 'times-new-roman', "'Times New Roman', Times, Baskerville, Georgia, serif"),
			$f('Trebuchet MS',    'trebuchet-ms',   "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', Tahoma, sans-serif"),
			$f('Verdana',         'verdana',        "Verdana, Geneva, sans-serif"),
		];
	}
	private function getAllowedBlocks()
	{
		$allowedBlocks = [
			'core/paragraph',
			'core/heading',
			'core/image',
			'core/buttons',
			'core/button',
			'core/spacer',
			'core/separator',
			'core/columns',
			'core/column',
			'core/table',
			'core/group',
			'core/quote',
			'core/pullquote',
			'core/list',
			'core/rss',
			'core/html',
			'core/list-item',
			'core/footnotes',
			'cps-bloom-mailer/post',
			'cps-bloom-mailer/social',
			'cps-bloom-mailer/socials',
			'cps-bloom-mailer/product',
		];

		$allowedBlocks = apply_filters('cps_mailer_allowed_block_types', $allowedBlocks);
		$allowedBlocks = array_values(array_unique($allowedBlocks));

		return $allowedBlocks;
	}

	private function getResolvedAssets()
	{
		$resolvedStyles = [
			'wp-components-css'           => includes_url('/css/dist/components/style.min.css'),
			'wp-preferences-css'          => includes_url('/css/dist/preferences/style.min.css'),
			'wp-block-editor-css'         => includes_url('/css/dist/block-editor/style.min.css'),
			'wp-reusable-blocks-css'      => includes_url('/css/dist/reusable-blocks/style.min.css'),
			'wp-patterns-css'             => includes_url('/css/dist/patterns/style.min.css'),
			'wp-editor-css'               => includes_url('/css/dist/editor/style.min.css'),
			'wp-block-library-css'        => includes_url('/css/dist/block-library/style.min.css'),
			'wp-block-editor-content-css' => includes_url('/css/dist/block-editor/content.min.css'),
			'wp-edit-blocks-css'          => includes_url('/css/dist/block-library/editor.min.css'),
		];

		global $wp_version;
		$cssFiles = '';
		foreach ($resolvedStyles as $name => $file) {
			$cssFiles .= "<link rel='stylesheet' id='{$name}' href='{$file}?ver={$wp_version}' media='all' />\n";
		}

		return [
			'scripts' => '<script src="' . includes_url('/js/dist/vendor/wp-polyfill.min.js?ver=3.15.0') . '" id="wp-polyfill-js"></script>',
			'styles'  => $cssFiles
		];
	}

	public function disable_advanced_tab($metadata)
	{
		// Disable for all blocks
		if (isset($metadata['supports'])) {
			//$metadata['supports']['customClassName'] = false;
			$metadata['supports']['anchor'] = false;
			$metadata['supports']['html'] = false;
			$metadata['supports']['customCSS'] = false;
			$metadata['supports']['allowedBlocks'] = false;
			$metadata['supports']['html'] = false;
		}

		return $metadata;
	}


	public function ajax_get_stats()
	{
		check_ajax_referer('cps_mailer_stats', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
		}

		$campaign_id = intval($_POST['campaign_id'] ?? 0);

		if (! $campaign_id) {
			wp_send_json_error('No campaign ID.');
		}

		$campaign = Campaign::get($campaign_id);
		$stats    = Stats::get_campaign_stats($campaign_id);

		wp_send_json_success(array(
			'campaign' => $campaign,
			'stats'    => $stats,
		));
	}

	public function ajax_get_overview()
	{
		check_ajax_referer('cps_mailer_stats', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
		}

		wp_send_json_success(Stats::get_overview());
	}

	public static function default()
	{
		static $data = null;

		if ($data !== null) {
			return $data;
		}

		$data = [
			'design'     => Templates::design(),
			'header'     => Templates::header(),
			'footer'     => Templates::footer(),
			'from_name'  => Settings::get('from_name'),
			'from_email' => Settings::get('from_email'),
			'reply_to'   => Settings::get('reply_to', ''),
		];

		return $data;
	}
}
new Admin();
