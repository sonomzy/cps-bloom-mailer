<?php

/**
 * Default Email Templates
 * 
 */

namespace ChicpixiesBloomMailer;

use WP_Error;

class Templates
{
    const TABLE = 'cps_mailer_templates';
    /**
     * Get default template by ID
     * 
     * @param string $key Template identifier
     * @return array|null Template configuration or null if not found
     */
    public static function get_template($key)
    {
        $templates = self::load();
        return $templates[$key] ?? null;
    }

    private static function load()
    {
        $templates = array(
            'welcome'      => self::welcome(),
            'new_post'     => self::new_post(),
            'new_products' => self::new_products(),
            're_engagement' => self::re_engagement(),
            'newsletter'   => self::newsletter(),
            'promotion'    => self::promotion(),
            'announcement' => self::announcement(),
            'sale_announcement'         => self::sale_announcement(),
            'abandoned_cart'            => self::abandoned_cart(),
            'subscriber_confirmed'      => self::subscriber_confirmed(),
            'unsubscribe_confirmation'  => self::unsubscribe_confirmation(),
        );

        $templates = apply_filters('cps_mailer_default_templates', $templates);
        return Sanitize::campaigns($templates, true);
    }

    /**
     * Improved: New Blog Post notification
     */
    private static function new_post()
    {
        $blocks = Blocks::new_post();

        return self::prepare_data(
            'New Blog Post',
            'New Post: {{post_title}}',
            __('Notify subscribers when a new blog post is published.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * Welcome email — sent on subscription
     */
    private static function welcome()
    {
        $blocks = Blocks::welcome();
        return self::prepare_data(
            'Welcome Email',
            'Welcome to {{site_name}}!',
            __('Sent to new subscribers right after they confirm their subscription.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * Re-engagement — for inactive subscribers
     */
    private static function re_engagement()
    {
        $blocks = Blocks::re_engagement();

        return self::prepare_data(
            'Re-engagement',
            '{{first_name}}, are you still with us?',
            __('Sent to subscribers who have been inactive for a set period of time.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * New Products — WooCommerce product highlights
     */
    private static function new_products()
    {
        $blocks = Blocks::new_post();

        return self::prepare_data(
            'New Products',
            'New arrivals just dropped at {{site_name}}',
            __('Announces newly added WooCommerce products to subscribers.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * Sale Announcement — urgency-focused
     */
    private static function sale_announcement()
    {
        $blocks = Blocks::sale_announcement();

        return self::prepare_data(
            'Sale Announcement',
            'Sale is live — shop now at {{site_name}}',
            __('Announces a WooCommerce sale, showing only sale-priced products.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * Abandoned Cart — generic reminder
     */
    private static function abandoned_cart()
    {
        $blocks = Blocks::abandoned_cart();

        return self::prepare_data(
            'Abandoned Cart',
            '{{first_name}}, you left something in your cart',
            __('Reminds a subscriber they have an incomplete order in their cart.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * Subscriber Confirmed — double opt-in confirmation
     */
    private static function subscriber_confirmed()
    {
        $blocks = Blocks::subscriber_confirmed();

        return self::prepare_data(
            'Subscriber Confirmed',
            'You\'re now subscribed to {{site_name}}',
            __('Sent after a subscriber confirms their email via double opt-in.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * Unsubscribe Confirmation — confirms removal from list
     */
    private static function unsubscribe_confirmation()
    {
        $blocks = Blocks::unsubscribe_confirmation();

        return self::prepare_data(
            'Unsubscribe Confirmation',
            'You\'ve been removed from {{site_name}}',
            __('Confirms to the subscriber that they have been removed from the mailing list.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    // -------------------------------------------------------------------------
    // 4. WEEKLY / MONTHLY NEWSLETTER
    // -------------------------------------------------------------------------
    private static function newsletter()
    {
        $blocks = Blocks::newsletter();

        return self::prepare_data(
            'Weekly Newsletter',
            'Your {{site_name}} digest for {{current_date}}',
            __('Periodic newsletter digest with articles, stats, and featured products', 'cps-bloom-mailer'),
            $blocks
        );
    }

    // -------------------------------------------------------------------------
    // 5. PROMOTIONAL / SALE
    // -------------------------------------------------------------------------
    private static function promotion()
    {
        $blocks = Blocks::promotion();

        return self::prepare_data(
            'Promotional Sale',
            '🔥 Exclusive sale for subscribers — up to 40% off at {{site_name}}',
            __('Flash sale or promotional email with urgency and discount code', 'cps-bloom-mailer'),
            $blocks
        );
    }

    // -------------------------------------------------------------------------
    // 7. ANNOUNCEMENT
    // -------------------------------------------------------------------------
    private static function announcement()
    {
        $blocks = Blocks::announcement();

        return self::prepare_data(
            'Announcement',
            '🎉 Big news from {{site_name}}: {{announcement_title}}',
            __('General-purpose announcement for launches, milestones, and events', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * Get default design settings
     */
    public static function design()
    {
        return array(
            'bodyBg'          => '#f5f5f5',
            'containerWidth'  => 600,
            'containerBg'     => '#ffffff',
            'padding'         => ['top' => '30px', 'left' => '30px', 'right' => '30px', 'bottom' => '30px'],
            'borderRadius'    => 8,
            'fontFamily'      => 'Arial, sans-serif',
            'fontSize'        => 16,
            'textColor'       => '#333333',
            'buttonTextColor' => '#ffffff',
        );
    }

    public static function header()
    {
        return array(
            'enabled'     => true,
            'title'       => ['html' => '{{site_name}}'],
            'description' => ['html' => ''],
            'logo'        => false,
            'logoUrl'     => '',
            'logoWidth'   => 60,
            'settings'    => array(
                'alignment'       => 'center',
                'textColor'       => '#333333',
                'background'         => '',
                'titleSize'       => '28px',
                'fontSize'        => '14px',
                'showDescription' => false,
                'padding'         => ['top' => '20px', 'left' => '40px', 'right' => '40px', 'bottom' => '30px'],
            ),
        );
    }

    public static function footer()
    {
        return array(
            'enabled' => true,
            'content' => [
                'html' => '<p style="margin:0 0 6px;">© {{current_year}} <a href="{{site_url}}" style="color:#0073aa;text-decoration:none;">{{site_name}}</a>. All rights reserved.</p>'
                    . '<p style="margin:0;">You\'re receiving this because you subscribed at <a href="{{site_url}}" style="color:#0073aa;text-decoration:none;">{{site_name}}</a>. '
                    . '<a href="{{unsubscribe_url}}" style="color:#aaaaaa;text-decoration:none;">Unsubscribe</a> &nbsp;·&nbsp; '
            ],
            'settings' => array(
                'alignment' => 'center',
                'textColor' => '#666666',
                'fontSize'  => '12px',
                'background'   => '',
                'padding'   => ['top' => '20px', 'left' => '40px', 'right' => '40px', 'bottom' => '20px'],
            ),
        );
    }

    private static function prepare_data($name, $subject, $description, $blocks)
    {
        return array(
            'title'       => $name,
            'subject'     => $subject,
            'description' => $description,
            'design'      => self::design(),
            'header'      => self::header(),
            'footer'      => self::footer(),
            'blocks'      => $blocks,
        );
    }

    public static function create_with_key($key, $data)
    {
        global $wpdb;
        $data['template_key'] = $key;
        $data['is_default'] = 1;
        $table = $wpdb->prefix .  self::TABLE;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE template_key = %s",
                $key
            )
        );

        if (empty($exists)) {
            return Campaign::create($data, true);
        }
        return $exists;
    }

    /**
     * Get all templates
     * 
     * @return array Array of all active templates
     */
    public static function get_all($args = [])
    {
        return Campaign::get_all($args, true);
    }

    /**
     * Import default templates
     * Only imports if template doesn't already exist
     */
    public static function import_defaults()
    {
        $default_templates = self::load();

        $existing = self::get_all(['is_default' => 1]);
        foreach ($default_templates as $key => $template) {
            if (!isset($existing[$key])) {
                self::create_with_key($key, $template);
            }
        }
    }

    /**
     * Reset email templates to defaults (helper method)
     * 
     * @return array|WP_Error Templates array or error
     */
    public static function reset()
    {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE;

        $deleted = $wpdb->query("DELETE FROM {$table} WHERE is_default = 1");
        if ($deleted === false) {
            return new WP_Error('db_error', 'Failed to reset templates. Please check the logs for details.');
        }

        // Re-import defaults
        self::import_defaults();
        return self::get_all(['is_default' => 1]);
    }

    public static function delete($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }
}
