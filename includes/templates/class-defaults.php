<?php

/**
 * Default Email Templates
 */

namespace ChicpixiesBloomMailer;

use WP_Error;

class Defaults
{

    /**
     * Get default email templates
     *
     * @return array Default templates configuration
     */
    public static function templates()
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
        $blocks = array(
            array(
                'id'      => 'new-post-heading-1',
                'type'    => 'heading',
                'content' => ['html' => 'Fresh from the blog'],
                'settings' => array(
                    'level'     => 2,
                    'alignment' => 'left',
                    'textColor' => '#111111',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '4px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'new-post-text-1',
                'type'    => 'text',
                'content' => ['html' => '<p>Hi {{first_name}},</p><p>We just published <strong>{{post_title}}</strong> and thought you\'d enjoy it. Here\'s a quick look at what\'s new.</p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#444444',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '16px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'new-post-post-1',
                'type'    => 'post',
                'settings' => array(
                    'ids'         => [],
                    'count'       => 1,
                    'columns'     => 1,
                    'orderBy'     => 'newest',
                    'showExcerpt' => true,
                    'categories'  => [],
                    'showButton'  => false,
                    'showImage'   => true,
                    'buttonText'  => 'Read More',
                    'bgColor'     => '#f9f9f9',
                    'textColor'   => '#333333',
                    'padding'     => ['top' => '16px', 'right' => '16px', 'bottom' => '16px', 'left' => '16px'],
                ),
            ),
            array(
                'id'      => 'new-post-button-1',
                'type'    => 'button',
                'content' => ['html' => 'Read the Post'],
                'settings' => array(
                    'href'         => '{{post_url}}',
                    'alignment'    => 'left',
                    'bgColor'      => '#111111',
                    'textColor'    => '#ffffff',
                    'borderRadius' => '4px',
                    'fontSize'     => '15px',
                    'padding'      => ['top' => '12px', 'right' => '28px', 'bottom' => '12px', 'left' => '28px'],
                    'spacing'      => ['top' => '24px', 'bottom' => '24px'],
                ),
            ),
            array(
                'id'      => 'new-post-divider-1',
                'type'    => 'divider',
                'settings' => array(
                    'color'   => '#e8e8e8',
                    'height'  => '1px',
                    'spacing' => ['top' => '8px', 'bottom' => '24px'],
                ),
            ),
            array(
                'id'      => 'new-post-text-2',
                'type'    => 'text',
                'content' => ['html' => '<p>Thanks for reading,<br><strong>The {{site_name}} Team</strong></p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#666666',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
        );

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
        $blocks = array(
            array(
                'id'      => 'welcome-heading-1',
                'type'    => 'heading',
                'content' => ['html' => 'Welcome aboard, {{first_name}}!'],
                'settings' => array(
                    'level'     => 2,
                    'alignment' => 'center',
                    'textColor' => '#111111',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '8px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'welcome-text-1',
                'type'    => 'text',
                'content' => ['html' => '<p style="text-align:center;">You\'re now on the {{site_name}} list. We\'ll send you new posts, updates, and the occasional good read — nothing spammy, ever.</p><p style="text-align:center;">In the meantime, here are a few posts to get you started.</p>'],
                'settings' => array(
                    'alignment' => 'center',
                    'textColor' => '#444444',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '24px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'welcome-post-1',
                'type'    => 'post',
                'settings' => array(
                    'ids'         => [],
                    'count'       => 3,
                    'columns'     => 3,
                    'orderBy'     => 'newest',
                    'showExcerpt' => false,
                    'categories'  => [],
                    'showButton'  => true,
                    'showImage'   => true,
                    'buttonText'  => 'Read Now',
                    'bgColor'     => '#ffffff',
                    'textColor'   => '#333333',
                    'padding'     => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'welcome-spacer-1',
                'type'    => 'spacer',
                'height'  => '24px',
            ),
            array(
                'id'      => 'welcome-divider-1',
                'type'    => 'divider',
                'settings' => array(
                    'color'   => '#e8e8e8',
                    'height'  => '1px',
                    'spacing' => ['top' => '0px', 'bottom' => '24px'],
                ),
            ),
            array(
                'id'      => 'welcome-text-2',
                'type'    => 'text',
                'content' => ['html' => '<p style="text-align:center;">Happy reading,<br><strong>{{site_name}}</strong></p>'],
                'settings' => array(
                    'alignment' => 'center',
                    'textColor' => '#666666',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
        );

        return self::prepare_data(
            'Welcome Email',
            'Welcome to {{site_name}}!',
            __('Sent to new subscribers right after they confirm their subscription.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * Weekly digest — roundup of recent posts
     */
    private static function weekly_digest()
    {
        $blocks = array(
            array(
                'id'      => 'digest-heading-1',
                'type'    => 'heading',
                'content' => ['html' => 'Your weekly roundup'],
                'settings' => array(
                    'level'     => 2,
                    'alignment' => 'left',
                    'textColor' => '#111111',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '4px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'digest-text-1',
                'type'    => 'text',
                'content' => ['html' => '<p>Hi {{first_name}}, here\'s everything we published this week. Grab a coffee and dig in.</p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#444444',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '24px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'digest-post-1',
                'type'    => 'post',
                'settings' => array(
                    'ids'         => [],
                    'count'       => 5,
                    'columns'     => 1,
                    'orderBy'     => 'newest',
                    'showExcerpt' => true,
                    'categories'  => [],
                    'showButton'  => true,
                    'showImage'   => true,
                    'buttonText'  => 'Read More',
                    'bgColor'     => '#ffffff',
                    'textColor'   => '#333333',
                    'padding'     => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'digest-divider-1',
                'type'    => 'divider',
                'settings' => array(
                    'color'   => '#e8e8e8',
                    'height'  => '1px',
                    'spacing' => ['top' => '32px', 'bottom' => '24px'],
                ),
            ),
            array(
                'id'      => 'digest-text-2',
                'type'    => 'text',
                'content' => ['html' => '<p>See you next week,<br><strong>The {{site_name}} Team</strong></p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#666666',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
        );

        return self::prepare_data(
            'Weekly Digest',
            'Your weekly reads from {{site_name}}',
            __('Weekly roundup of the most recent posts on the site.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    /**
     * Re-engagement — for inactive subscribers
     */
    private static function re_engagement()
    {
        $blocks = array(
            array(
                'id'      => 'reeng-heading-1',
                'type'    => 'heading',
                'content' => ['html' => 'Still there, {{first_name}}?'],
                'settings' => array(
                    'level'     => 2,
                    'alignment' => 'center',
                    'textColor' => '#111111',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '8px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'reeng-text-1',
                'type'    => 'text',
                'content' => ['html' => '<p style="text-align:center;">We noticed you haven\'t opened our emails in a while. No hard feelings — we know inboxes get busy.</p><p style="text-align:center;">If you still want to hear from us, just click the button below. Otherwise, we\'ll quietly remove you from the list in the next few days.</p>'],
                'settings' => array(
                    'alignment' => 'center',
                    'textColor' => '#444444',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'reeng-button-1',
                'type'    => 'button',
                'content' => ['html' => 'Yes, keep me subscribed'],
                'settings' => array(
                    'href'         => '{{resubscribe_url}}',
                    'alignment'    => 'center',
                    'bgColor'      => '#111111',
                    'textColor'    => '#ffffff',
                    'borderRadius' => '4px',
                    'fontSize'     => '15px',
                    'padding'      => ['top' => '13px', 'right' => '32px', 'bottom' => '13px', 'left' => '32px'],
                    'spacing'      => ['top' => '28px', 'bottom' => '28px'],
                ),
            ),
            array(
                'id'      => 'reeng-divider-1',
                'type'    => 'divider',
                'settings' => array(
                    'color'   => '#e8e8e8',
                    'height'  => '1px',
                    'spacing' => ['top' => '8px', 'bottom' => '20px'],
                ),
            ),
            array(
                'id'      => 'reeng-text-2',
                'type'    => 'text',
                'content' => ['html' => '<p style="text-align:center;font-size:13px;">Don\'t want to stay? <a href="{{unsubscribe_url}}" style="color:#888888;">Unsubscribe here</a>.</p>'],
                'settings' => array(
                    'alignment' => 'center',
                    'textColor' => '#888888',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
        );

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
        $blocks = array(
            array(
                'id'      => 'newprod-heading-1',
                'type'    => 'heading',
                'content' => ['html' => 'New in the shop'],
                'settings' => array(
                    'level'     => 2,
                    'alignment' => 'left',
                    'textColor' => '#111111',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '4px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'newprod-text-1',
                'type'    => 'text',
                'content' => ['html' => '<p>Hi {{first_name}}, we just added new products to the shop. Here\'s a look at what\'s fresh.</p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#444444',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '24px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'newprod-product-1',
                'type'    => 'product',
                'settings' => array(
                    'ids'         => [],
                    'count'       => 4,
                    'columns'     => 2,
                    'orderBy'     => 'date',
                    'order'       => 'desc',
                    'saleOnly'    => false,
                    'categories'  => [],
                    'showButton'  => true,
                    'showImage'   => true,
                    'buttonText'  => 'Shop Now',
                    'bgColor'     => '#ffffff',
                    'textColor'   => '#333333',
                    'padding'     => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'newprod-spacer-1',
                'type'    => 'spacer',
                'height'  => '24px',
            ),
            array(
                'id'      => 'newprod-button-1',
                'type'    => 'button',
                'content' => ['html' => 'Browse the Shop'],
                'settings' => array(
                    'href'         => '{{shop_url}}',
                    'alignment'    => 'center',
                    'bgColor'      => '#111111',
                    'textColor'    => '#ffffff',
                    'borderRadius' => '4px',
                    'fontSize'     => '15px',
                    'padding'      => ['top' => '12px', 'right' => '32px', 'bottom' => '12px', 'left' => '32px'],
                    'spacing'      => ['top' => '8px', 'bottom' => '8px'],
                ),
            ),
            array(
                'id'      => 'newprod-divider-1',
                'type'    => 'divider',
                'settings' => array(
                    'color'   => '#e8e8e8',
                    'height'  => '1px',
                    'spacing' => ['top' => '28px', 'bottom' => '24px'],
                ),
            ),
            array(
                'id'      => 'newprod-text-2',
                'type'    => 'text',
                'content' => ['html' => '<p>Happy shopping,<br><strong>The {{site_name}} Team</strong></p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#666666',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
        );

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
        $blocks = array(
            array(
                'id'      => 'sale-heading-1',
                'type'    => 'heading',
                'content' => ['html' => 'The sale is on'],
                'settings' => array(
                    'level'     => 2,
                    'alignment' => 'center',
                    'textColor' => '#ffffff',
                    'bgColor'   => '#c0392b',
                    'padding'   => ['top' => '24px', 'right' => '24px', 'bottom' => '24px', 'left' => '24px'],
                ),
            ),
            array(
                'id'      => 'sale-text-1',
                'type'    => 'text',
                'content' => ['html' => '<p>Hi {{first_name}}, a selection of our products are on sale right now. Don\'t wait too long — these prices won\'t last.</p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#444444',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '24px', 'right' => '0px', 'bottom' => '24px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'sale-product-1',
                'type'    => 'product',
                'settings' => array(
                    'ids'         => [],
                    'count'       => 4,
                    'columns'     => 2,
                    'orderBy'     => 'date',
                    'order'       => 'desc',
                    'saleOnly'    => true,
                    'categories'  => [],
                    'showButton'  => true,
                    'showImage'   => true,
                    'buttonText'  => 'Get the Deal',
                    'bgColor'     => '#ffffff',
                    'textColor'   => '#333333',
                    'padding'     => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'sale-button-1',
                'type'    => 'button',
                'content' => ['html' => 'Shop the Sale'],
                'settings' => array(
                    'href'         => '{{shop_url}}',
                    'alignment'    => 'center',
                    'bgColor'      => '#c0392b',
                    'textColor'    => '#ffffff',
                    'borderRadius' => '4px',
                    'fontSize'     => '16px',
                    'padding'      => ['top' => '13px', 'right' => '36px', 'bottom' => '13px', 'left' => '36px'],
                    'spacing'      => ['top' => '28px', 'bottom' => '8px'],
                ),
            ),
            array(
                'id'      => 'sale-divider-1',
                'type'    => 'divider',
                'settings' => array(
                    'color'   => '#e8e8e8',
                    'height'  => '1px',
                    'spacing' => ['top' => '28px', 'bottom' => '24px'],
                ),
            ),
            array(
                'id'      => 'sale-text-2',
                'type'    => 'text',
                'content' => ['html' => '<p>Good luck out there,<br><strong>The {{site_name}} Team</strong></p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#666666',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
        );

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
        $blocks = array(
            array(
                'id'      => 'cart-heading-1',
                'type'    => 'heading',
                'content' => ['html' => 'You left something behind'],
                'settings' => array(
                    'level'     => 2,
                    'alignment' => 'left',
                    'textColor' => '#111111',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '8px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'cart-text-1',
                'type'    => 'text',
                'content' => ['html' => '<p>Hi {{first_name}},</p><p>You added items to your cart but didn\'t complete your order. Your cart has been saved — head back whenever you\'re ready.</p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#444444',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'cart-button-1',
                'type'    => 'button',
                'content' => ['html' => 'Return to Your Cart'],
                'settings' => array(
                    'href'         => '{{cart_url}}',
                    'alignment'    => 'left',
                    'bgColor'      => '#111111',
                    'textColor'    => '#ffffff',
                    'borderRadius' => '4px',
                    'fontSize'     => '15px',
                    'padding'      => ['top' => '12px', 'right' => '28px', 'bottom' => '12px', 'left' => '28px'],
                    'spacing'      => ['top' => '28px', 'bottom' => '28px'],
                ),
            ),
            array(
                'id'      => 'cart-divider-1',
                'type'    => 'divider',
                'settings' => array(
                    'color'   => '#e8e8e8',
                    'height'  => '1px',
                    'spacing' => ['top' => '8px', 'bottom' => '20px'],
                ),
            ),
            array(
                'id'      => 'cart-text-2',
                'type'    => 'text',
                'content' => ['html' => '<p>Questions before you buy? Just reply to this email and we\'ll help.</p><p>— <strong>The {{site_name}} Team</strong></p>'],
                'settings' => array(
                    'alignment' => 'left',
                    'textColor' => '#666666',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
        );

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
        $blocks = array(
            array(
                'id'      => 'confirmed-heading-1',
                'type'    => 'heading',
                'content' => ['html' => 'You\'re confirmed!'],
                'settings' => array(
                    'level'     => 2,
                    'alignment' => 'center',
                    'textColor' => '#111111',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '8px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'confirmed-text-1',
                'type'    => 'text',
                'content' => ['html' => '<p style="text-align:center;">Hi {{first_name}}, your subscription to <strong>{{site_name}}</strong> is now active.</p><p style="text-align:center;">You\'ll start getting emails from us very soon. We\'re glad you\'re here.</p>'],
                'settings' => array(
                    'alignment' => 'center',
                    'textColor' => '#444444',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'confirmed-button-1',
                'type'    => 'button',
                'content' => ['html' => 'Visit {{site_name}}'],
                'settings' => array(
                    'href'         => '{{site_url}}',
                    'alignment'    => 'center',
                    'bgColor'      => '#111111',
                    'textColor'    => '#ffffff',
                    'borderRadius' => '4px',
                    'fontSize'     => '15px',
                    'padding'      => ['top' => '12px', 'right' => '28px', 'bottom' => '12px', 'left' => '28px'],
                    'spacing'      => ['top' => '28px', 'bottom' => '28px'],
                ),
            ),
            array(
                'id'      => 'confirmed-divider-1',
                'type'    => 'divider',
                'settings' => array(
                    'color'   => '#e8e8e8',
                    'height'  => '1px',
                    'spacing' => ['top' => '8px', 'bottom' => '20px'],
                ),
            ),
            array(
                'id'      => 'confirmed-text-2',
                'type'    => 'text',
                'content' => ['html' => '<p style="text-align:center;font-size:13px;">If you didn\'t subscribe to {{site_name}}, you can safely ignore this email. No action is needed.</p>'],
                'settings' => array(
                    'alignment' => 'center',
                    'textColor' => '#999999',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
        );

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
        $blocks = array(
            array(
                'id'      => 'unsub-heading-1',
                'type'    => 'heading',
                'content' => ['html' => 'You\'ve been unsubscribed'],
                'settings' => array(
                    'level'     => 2,
                    'alignment' => 'center',
                    'textColor' => '#111111',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '8px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'unsub-text-1',
                'type'    => 'text',
                'content' => ['html' => '<p style="text-align:center;">Hi {{first_name}}, you\'ve been removed from the <strong>{{site_name}}</strong> mailing list. You won\'t receive any more emails from us.</p><p style="text-align:center;">If you removed yourself by mistake, you can always resubscribe below.</p>'],
                'settings' => array(
                    'alignment' => 'center',
                    'textColor' => '#444444',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
            array(
                'id'      => 'unsub-button-1',
                'type'    => 'button',
                'content' => ['html' => 'Resubscribe'],
                'settings' => array(
                    'href'         => '{{resubscribe_url}}',
                    'alignment'    => 'center',
                    'bgColor'      => '#ffffff',
                    'textColor'    => '#111111',
                    'borderRadius' => '4px',
                    'fontSize'     => '15px',
                    'padding'      => ['top' => '11px', 'right' => '28px', 'bottom' => '11px', 'left' => '28px'],
                    'spacing'      => ['top' => '24px', 'bottom' => '24px'],
                ),
            ),
            array(
                'id'      => 'unsub-divider-1',
                'type'    => 'divider',
                'settings' => array(
                    'color'   => '#e8e8e8',
                    'height'  => '1px',
                    'spacing' => ['top' => '8px', 'bottom' => '20px'],
                ),
            ),
            array(
                'id'      => 'unsub-text-2',
                'type'    => 'text',
                'content' => ['html' => '<p style="text-align:center;">Thanks for being a subscriber. We hope to see you again someday.</p><p style="text-align:center;">— <strong>{{site_name}}</strong></p>'],
                'settings' => array(
                    'alignment' => 'center',
                    'textColor' => '#888888',
                    'bgColor'   => '#ffffff',
                    'padding'   => ['top' => '0px', 'right' => '0px', 'bottom' => '0px', 'left' => '0px'],
                ),
            ),
        );

        return self::prepare_data(
            'Unsubscribe Confirmation',
            'You\'ve been removed from {{site_name}}',
            __('Confirms to the subscriber that they have been removed from the mailing list.', 'cps-bloom-mailer'),
            $blocks
        );
    }

    // // -------------------------------------------------------------------------
    // // 1. WELCOME EMAIL
    // // -------------------------------------------------------------------------
    // private static function welcome()
    // {
    //     $blocks = array(

    //         // Hero heading
    //         array(
    //             'id'   => 'welcome-heading-1',
    //             'type' => 'heading',
    //             'content' => [
    //                 'html' => '<h1 style="margin:0;font-size:32px;font-weight:800;letter-spacing:-0.5px;">Welcome aboard, {{first_name}} 👋</h1>',
    //             ],
    //             'settings' => array(
    //                 'level'     => 1,
    //                 'bgColor'   => '#0073aa',
    //                 'textColor' => '#ffffff',
    //                 'alignment' => 'center',
    //                 'padding'   => ['top' => '40px', 'right' => '40px', 'bottom' => '40px', 'left' => '40px'],
    //             ),
    //         ),

    //         // Warm intro
    //         array(
    //             'id'   => 'welcome-text-1',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '
    //                                     <p style="font-size:17px;line-height:1.7;margin:0 0 16px;">
    //                                     Hi <strong>{{first_name}}</strong>,
    //                                     </p>
    //                                     <p style="font-size:17px;line-height:1.7;margin:0 0 16px;">
    //                                     We\'re genuinely thrilled to have you here. You\'ve just joined a community of curious, motivated people who care about staying in the loop — and we promise to make every email worth your time.
    //                                     </p>
    //                                     <p style="font-size:17px;line-height:1.7;margin:0;">
    //                                     Here\'s a quick look at what to expect from us:
    //                                     </p>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#333333',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '36px', 'right' => '40px', 'bottom' => '20px', 'left' => '40px'],
    //             ),
    //         ),

    //         // What to expect — columns
    //         array(
    //             'id'      => 'welcome-columns-1',
    //             'type'    => 'columns',
    //             'content' => [
    //                 // Column 1
    //                 [
    //                     [
    //                         'type'    => 'text',
    //                         'id'   => 'welcome-columns-text-1',
    //                         'content' => [
    //                             'html' => '
    //             <div style="text-align:center;padding:24px 16px;background:#f0f8ff;border-radius:10px;">
    //             <p style="font-weight:700;font-size:15px;color:#0073aa;margin:0 0 8px;">Fresh Content</p>
    //             <p style="font-size:14px;color:#555555;line-height:1.6;margin:0;">Hand-picked articles and updates delivered straight to your inbox.</p>
    //             </div>',
    //                         ],
    //                     ],
    //                 ],
    //                 // Column 2
    //                 [
    //                     [
    //                         'type'    => 'text',
    //                         'id'   => 'welcome-columns-text-2',
    //                         'content' => [
    //                             'html' => '
    //             <div style="text-align:center;padding:24px 16px;background:#f0fff4;border-radius:10px;">
    //             <p style="font-weight:700;font-size:15px;color:#0073aa;margin:0 0 8px;">Exclusive Tips</p>
    //             <p style="font-size:14px;color:#555555;line-height:1.6;margin:0;">Subscriber-only insights you won\'t find anywhere else.</p>
    //             </div>',
    //                         ],
    //                     ],
    //                 ],
    //                 // Column 3
    //                 [
    //                     [
    //                         'type'    => 'text',
    //                         'id'   => 'welcome-columns-text-3',
    //                         'content' => [
    //                             'html' => '
    //             <div style="text-align:center;padding:24px 16px;background:#fff8f0;border-radius:10px;">
    //             <p style="font-weight:700;font-size:15px;color:#0073aa;margin:0 0 8px;">Early Access</p>
    //             <p style="font-size:14px;color:#555555;line-height:1.6;margin:0;">Be the first to hear about new launches, features, and events.</p>
    //             </div>',
    //                         ],
    //                     ],
    //                 ],
    //             ],
    //             'settings' => array(
    //                 'count'   => 3,
    //                 'gap'     => '16px',
    //                 'padding' => ['top' => '16px', 'right' => '40px', 'bottom' => '16px', 'left' => '40px'],
    //             ),
    //         ),

    //         // Divider
    //         array(
    //             'id'   => 'welcome-divider-1',
    //             'type' => 'divider',
    //             'settings' => array(
    //                 'color'   => '#e8e8e8',
    //                 'height'  => '1px',
    //                 'spacing' => ['top' => '30px', 'bottom' => '30px'],
    //             ),
    //         ),

    //         // Explore latest posts
    //         array(
    //             'id'   => 'welcome-text-2',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '<h2 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#111111;">Start exploring</h2>
    //             <p style="font-size:15px;color:#666666;margin:0;">Here are our most recent posts to get you started.</p>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#333333',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '16px', 'left' => '40px'],
    //             ),
    //         ),

    //         array(
    //             'id'   => 'welcome-post-1',
    //             'type' => 'post',
    //             'settings' => [
    //                 'count'       => 2,
    //                 'columns'     => 2,
    //                 'orderBy'     => 'newest',
    //                 'showExcerpt' => true,
    //                 'categories'  => [],
    //                 'showButton'  => true,
    //                 'showImage'   => true,
    //                 'buttonText'  => 'Read Article →',
    //                 'bgColor'     => '#fafafa',
    //                 'textColor'   => '#333333',
    //                 'padding'     => ['top' => '0px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
    //             ],
    //         ),

    //         // CTA
    //         array(
    //             'id'   => 'welcome-button-1',
    //             'type' => 'button',
    //             'content' => ['html' => 'Visit {{site_name}} →'],
    //             'settings' => array(
    //                 'href'         => '{{site_url}}',
    //                 'alignment'    => 'center',
    //                 'bgColor'      => '#0073aa',
    //                 'textColor'    => '#ffffff',
    //                 'borderRadius' => '6px',
    //                 'fontSize'     => '16px',
    //                 'padding'      => ['top' => '14px', 'right' => '36px', 'bottom' => '14px', 'left' => '36px'],
    //                 'spacing'      => ['top' => '30px', 'bottom' => '30px'],
    //             ),
    //         ),

    //         // Sign-off
    //         array(
    //             'id'   => 'welcome-text-3',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '<p style="font-size:16px;line-height:1.7;margin:0;">
    //             With love,<br>
    //             <strong>The {{site_name}} Team</strong>
    //             </p>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#333333',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '40px', 'left' => '40px'],
    //             ),
    //         ),
    //     );

    //     return self::prepare_data(
    //         'Welcome Email',
    //         'Welcome to {{site_name}}, {{first_name}}!',
    //         __('Sent immediately after a new subscriber signs up', 'cps-bloom-mailer'),
    //         $blocks
    //     );
    // }

    // // -------------------------------------------------------------------------
    // // 2. NEW BLOG POST
    // // -------------------------------------------------------------------------
    // private static function new_post()
    // {
    //     $blocks = array(

    //         // Label badge + title
    //         array(
    //             'id'   => 'post-heading-1',
    //             'type' => 'heading',
    //             'content' => [
    //                 'html' => '
    //             <p style="display:inline-block;background:#e8f4fd;color:#0073aa;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:4px 12px;border-radius:20px;margin:0 0 16px;">New Post</p>
    //             <h1 style="margin:0;font-size:28px;font-weight:800;color:#111111;line-height:1.3;">{{post_title}}</h1>',
    //             ],
    //             'settings' => array(
    //                 'level'     => 1,
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#111111',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '36px', 'right' => '40px', 'bottom' => '20px', 'left' => '40px'],
    //             ),
    //         ),

    //         // Intro paragraph
    //         array(
    //             'id'   => 'post-text-1',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '<p style="font-size:17px;line-height:1.75;color:#444444;margin:0 0 12px;">
    //             Hi <strong>{{first_name}}</strong>,
    //             </p>
    //             <p style="font-size:17px;line-height:1.75;color:#444444;margin:0;">
    //             We just hit publish on something we\'re really proud of. Take a few minutes to read it — we think you\'ll find it genuinely useful.
    //             </p>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#444444',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '28px', 'left' => '40px'],
    //             ),
    //         ),

    //         // Featured post block
    //         array(
    //             'id'   => 'post-post-1',
    //             'type' => 'post',
    //             'settings' => [
    //                 'count'       => 1,
    //                 'columns'     => 1,
    //                 'orderBy'     => 'newest',
    //                 'showExcerpt' => true,
    //                 'categories'  => [],
    //                 'showButton'  => true,
    //                 'showImage'   => true,
    //                 'buttonText'  => 'Read the Full Post →',
    //                 'bgColor'     => '#f7f9fb',
    //                 'textColor'   => '#333333',
    //                 'padding'     => ['top' => '0px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
    //             ],
    //         ),

    //         // Spacer
    //         array(
    //             'id'     => 'post-spacer-1',
    //             'type'   => 'spacer',
    //             'height' => '28px',
    //         ),

    //         // Divider
    //         array(
    //             'id'   => 'post-divider-1',
    //             'type' => 'divider',
    //             'settings' => array(
    //                 'color'   => '#eeeeee',
    //                 'height'  => '1px',
    //                 'spacing' => ['top' => '0px', 'bottom' => '28px'],
    //             ),
    //         ),

    //         // You might also like
    //         array(
    //             'id'   => 'post-text-2',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '<h2 style="margin:0 0 4px;font-size:18px;font-weight:700;color:#111111;">You might also like</h2>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#111111',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '16px', 'left' => '40px'],
    //             ),
    //         ),

    //         array(
    //             'id'   => 'post-post-2',
    //             'type' => 'post',
    //             'settings' => [
    //                 'count'       => 2,
    //                 'columns'     => 2,
    //                 'orderBy'     => 'newest',
    //                 'showExcerpt' => false,
    //                 'categories'  => [],
    //                 'showButton'  => true,
    //                 'showImage'   => true,
    //                 'buttonText'  => 'Read More →',
    //                 'bgColor'     => '#ffffff',
    //                 'textColor'   => '#333333',
    //                 'padding'     => ['top' => '0px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
    //             ],
    //         ),

    //         // Sign-off
    //         array(
    //             'id'   => 'post-text-3',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '<p style="font-size:16px;line-height:1.7;margin:0;">
    //             Happy reading,<br>
    //             <strong>The {{site_name}} Team</strong>
    //             </p>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#333333',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '30px', 'right' => '40px', 'bottom' => '36px', 'left' => '40px'],
    //             ),
    //         ),
    //     );

    //     return self::prepare_data(
    //         'New Blog Post',
    //         'New Post: {{post_title}}',
    //         __('Notify subscribers when a new blog post is published', 'cps-bloom-mailer'),
    //         $blocks
    //     );
    // }

    // // -------------------------------------------------------------------------
    // // 3. NEW PRODUCTS (WooCommerce)
    // // -------------------------------------------------------------------------
    // private static function new_products()
    // {
    //     $blocks = array(

    //         // Hero banner heading
    //         array(
    //             'id'   => 'np-heading-1',
    //             'type' => 'heading',
    //             'content' => [
    //                 'html' => '
    //             <p style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.75);margin:0 0 10px;">Just Arrived</p>
    //             <h1 style="margin:0 0 10px;font-size:30px;font-weight:800;color:#ffffff;line-height:1.25;">Fresh Picks, Just for You</h1>
    //             <p style="font-size:15px;color:rgba(255,255,255,0.85);margin:0;">New arrivals are in. Don\'t wait — your favourites sell out fast.</p>',
    //             ],
    //             'settings' => array(
    //                 'level'     => 1,
    //                 'bgColor'   => '#1a1a2e',
    //                 'textColor' => '#ffffff',
    //                 'alignment' => 'center',
    //                 'padding'   => ['top' => '48px', 'right' => '40px', 'bottom' => '48px', 'left' => '40px'],
    //             ),
    //         ),

    //         // Intro
    //         array(
    //             'id'   => 'np-text-1',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '<p style="font-size:17px;line-height:1.75;margin:0;">
    //             Hi <strong>{{first_name}}</strong>,
    //             </p>
    //             <p style="font-size:17px;line-height:1.75;margin:12px 0 0;">
    //             Our team has been busy curating the latest additions to the store. Below are some items we think you\'ll absolutely love — hand-picked based on what\'s trending right now.
    //             </p>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#333333',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '36px', 'right' => '40px', 'bottom' => '28px', 'left' => '40px'],
    //             ),
    //         ),

    //         // Products grid
    //         array(
    //             'id'   => 'np-product-1',
    //             'type' => 'product',
    //             'settings' => [
    //                 'ids'         => [],
    //                 'count'       => 4,
    //                 'columns'     => 2,
    //                 'orderBy'     => 'date',
    //                 'order'       => 'desc',
    //                 'saleOnly'    => false,
    //                 'categories'  => [],
    //                 'showButton'  => true,
    //                 'showImage'   => true,
    //                 'buttonText'  => 'Shop Now →',
    //                 'bgColor'     => '#f9f9f9',
    //                 'textColor'   => '#222222',
    //                 'padding'     => ['top' => '0px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
    //             ],
    //         ),

    //         // CTA
    //         array(
    //             'id'   => 'np-button-1',
    //             'type' => 'button',
    //             'content' => ['html' => 'Browse the Full Store →'],
    //             'settings' => array(
    //                 'href'         => '{{site_url}}/shop',
    //                 'alignment'    => 'center',
    //                 'bgColor'      => '#1a1a2e',
    //                 'textColor'    => '#ffffff',
    //                 'borderRadius' => '6px',
    //                 'fontSize'     => '16px',
    //                 'padding'      => ['top' => '14px', 'right' => '40px', 'bottom' => '14px', 'left' => '40px'],
    //                 'spacing'      => ['top' => '32px', 'bottom' => '16px'],
    //             ),
    //         ),

    //         // Trust strip
    //         array(
    //             'id'   => 'np-columns-trust',
    //             'type' => 'columns',
    //             'content' => [
    //                 [
    //                     [
    //                         'type'    => 'text',
    //                         'id'   => 'np-columns-trust-text-1',
    //                         'content' => ['html' => '<p style="text-align:center;font-size:13px;color:#888888;margin:0;"><strong>Free Shipping</strong><br>On orders over a threshold</p>'],
    //                     ],
    //                 ],
    //                 [
    //                     [
    //                         'type'    => 'text',
    //                         'id'   => 'np-columns-trust-text-2',
    //                         'content' => ['html' => '<p style="text-align:center;font-size:13px;color:#888888;margin:0;"><strong>Easy Returns</strong><br>30-day hassle-free returns</p>'],
    //                     ],
    //                 ],
    //                 [
    //                     [
    //                         'type'    => 'text',
    //                         'id'   => 'np-columns-trust-text-3',
    //                         'content' => ['html' => '<p style="text-align:center;font-size:13px;color:#888888;margin:0;"><strong>Secure Checkout</strong><br>SSL encrypted payments</p>'],
    //                     ],
    //                 ],
    //             ],
    //             'settings' => array(
    //                 'count'   => 3,
    //                 'gap'     => '10px',
    //                 'padding' => ['top' => '24px', 'right' => '40px', 'bottom' => '36px', 'left' => '40px'],
    //             ),
    //         ),

    //         // Sign-off
    //         array(
    //             'id'   => 'np-text-2',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '<p style="font-size:16px;line-height:1.7;margin:0;">
    //             Happy shopping,<br>
    //             <strong>The {{site_name}} Team</strong>
    //             </p>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#333333',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '36px', 'left' => '40px'],
    //             ),
    //         ),
    //     );

    //     return self::prepare_data(
    //         'New Products',
    //         'New arrivals just dropped at {{site_name}}',
    //         __('Showcase new WooCommerce product arrivals to subscribers', 'cps-bloom-mailer'),
    //         $blocks
    //     );
    // }


    // // -------------------------------------------------------------------------
    // // 6. RE-ENGAGEMENT
    // // -------------------------------------------------------------------------
    // private static function re_engagement()
    // {
    //     $blocks = array(

    //         // Emotional hero
    //         array(
    //             'id'   => 're-heading-1',
    //             'type' => 'heading',
    //             'content' => [
    //                 'html' => '
    //             <div style="font-size:52px;margin:0 0 16px;">😔</div>
    //             <h1 style="margin:0 0 10px;font-size:28px;font-weight:800;color:#111111;line-height:1.3;">We miss you, {{first_name}}</h1>
    //             <p style="font-size:16px;color:#666666;margin:0;">It\'s been a while. We wanted to reach out.</p>',
    //             ],
    //             'settings' => array(
    //                 'level'     => 1,
    //                 'bgColor'   => '#fafafa',
    //                 'textColor' => '#111111',
    //                 'alignment' => 'center',
    //                 'padding'   => ['top' => '52px', 'right' => '40px', 'bottom' => '40px', 'left' => '40px'],
    //             ),
    //         ),

    //         // Warm personal message
    //         array(
    //             'id'   => 're-text-1',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '
    //             <p style="font-size:17px;line-height:1.85;color:#333333;margin:0 0 16px;">
    //             Hi <strong>{{first_name}}</strong>,
    //             </p>
    //             <p style="font-size:17px;line-height:1.85;color:#333333;margin:0 0 16px;">
    //             We noticed you haven\'t opened our emails in a while, and we completely understand — inboxes get busy. But before you go, we wanted to share what\'s new and ask: <strong>is there anything we can do better?</strong>
    //             </p>
    //             <p style="font-size:17px;line-height:1.85;color:#333333;margin:0;">
    //             We\'ve been creating some of our best content yet, and we\'d love for you to see it.
    //             </p>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#333333',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '36px', 'right' => '40px', 'bottom' => '28px', 'left' => '40px'],
    //             ),
    //         ),

    //         // What you've missed section
    //         array(
    //             'id'   => 're-text-2',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '<h2 style="font-size:17px;font-weight:700;color:#111111;margin:0 0 4px;">Here\'s what you\'ve missed</h2>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#111111',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '16px', 'left' => '40px'],
    //             ),
    //         ),

    //         array(
    //             'id'   => 're-post-1',
    //             'type' => 'post',
    //             'settings' => [
    //                 'count'       => 3,
    //                 'columns'     => 1,
    //                 'orderBy'     => 'popular',
    //                 'showExcerpt' => true,
    //                 'categories'  => [],
    //                 'showButton'  => true,
    //                 'showImage'   => false,
    //                 'buttonText'  => 'Read Now →',
    //                 'bgColor'     => '#f7f9fb',
    //                 'textColor'   => '#333333',
    //                 'padding'     => ['top' => '0px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
    //             ],
    //         ),

    //         // Divider
    //         array(
    //             'id'   => 're-divider-1',
    //             'type' => 'divider',
    //             'settings' => array(
    //                 'color'   => '#eeeeee',
    //                 'height'  => '1px',
    //                 'spacing' => ['top' => '32px', 'bottom' => '32px'],
    //             ),
    //         ),

    //         // Special offer
    //         array(
    //             'id'   => 're-text-3',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '
    //             <div style="background:#fff8e6;border-left:4px solid #f4a800;border-radius:6px;padding:20px 24px;">
    //             <p style="font-size:15px;font-weight:700;color:#cc8800;margin:0 0 8px;">A little something for you</p>
    //             <p style="font-size:15px;color:#555555;line-height:1.7;margin:0;">
    //                 As a thank-you for being part of our community, use code <strong style="color:#cc8800;font-size:16px;">WELCOMEBACK</strong> for 15% off your next order.
    //             </p>
    //             </div>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#333333',
    //                 'alignment' => 'left',
    //                 'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '28px', 'left' => '40px'],
    //             ),
    //         ),

    //         // Two CTAs: Stay or go
    //         array(
    //             'id'   => 're-button-stay',
    //             'type' => 'button',
    //             'content' => ['html' => 'Yes, Keep Me Subscribed!'],
    //             'settings' => array(
    //                 'href'         => '{{confirm_subscription_url}}',
    //                 'alignment'    => 'center',
    //                 'bgColor'      => '#0073aa',
    //                 'textColor'    => '#ffffff',
    //                 'borderRadius' => '6px',
    //                 'fontSize'     => '16px',
    //                 'padding'      => ['top' => '14px', 'right' => '36px', 'bottom' => '14px', 'left' => '36px'],
    //                 'spacing'      => ['top' => '0px', 'bottom' => '12px'],
    //             ),
    //         ),

    //         array(
    //             'id'   => 're-text-4',
    //             'type' => 'text',
    //             'content' => [
    //                 'html' => '<p style="text-align:center;font-size:13px;color:#aaaaaa;margin:0;">
    //             Not interested anymore? <a href="{{unsubscribe_url}}" style="color:#aaaaaa;">Unsubscribe here</a> — no hard feelings.
    //             </p>',
    //             ],
    //             'settings' => array(
    //                 'bgColor'   => '#ffffff',
    //                 'textColor' => '#aaaaaa',
    //                 'alignment' => 'center',
    //                 'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '40px', 'left' => '40px'],
    //             ),
    //         ),
    //     );

    //     return self::prepare_data(
    //         'Re-engagement',
    //         'We miss you, {{first_name}} — come back?',
    //         __('Win back inactive subscribers with a warm personal message and incentive', 'cps-bloom-mailer'),
    //         $blocks
    //     );
    // }

    // -------------------------------------------------------------------------
    // 4. WEEKLY / MONTHLY NEWSLETTER
    // -------------------------------------------------------------------------
    private static function newsletter()
    {
        $blocks = array(

            // Issue header
            array(
                'id'   => 'nl-heading-1',
                'type' => 'heading',
                'content' => [
                    'html' => '
                <p style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.65);margin:0 0 8px;">{{site_name}} Newsletter</p>
                <h1 style="margin:0 0 6px;font-size:26px;font-weight:800;color:#ffffff;">Your weekly digest</h1>
                <p style="font-size:13px;color:rgba(255,255,255,0.7);margin:0;">{{current_date}} &nbsp;·&nbsp; Issue #{{issue_number}}</p>',
                ],
                'settings' => array(
                    'level'     => 1,
                    'bgColor'   => '#0f4c75',
                    'textColor' => '#ffffff',
                    'alignment' => 'center',
                    'padding'   => ['top' => '40px', 'right' => '40px', 'bottom' => '40px', 'left' => '40px'],
                ),
            ),

            // Editor's note
            array(
                'id'   => 'nl-text-1',
                'type' => 'text',
                'content' => [
                    'html' => '
                <p style="font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#0f4c75;margin:0 0 10px;">Editor\'s Note</p>
                <p style="font-size:17px;line-height:1.8;color:#333333;margin:0;">
                Hi <strong>{{first_name}}</strong>, hope your week is going well! We\'ve rounded up the best content, news, and resources from the past week. Grab a coffee and enjoy.
                </p>',
                ],
                'settings' => array(
                    'bgColor'   => '#f7f9fc',
                    'textColor' => '#333333',
                    'alignment' => 'left',
                    'padding'   => ['top' => '28px', 'right' => '40px', 'bottom' => '28px', 'left' => '40px'],
                ),
            ),

            // Section: Latest articles
            array(
                'id'   => 'nl-text-2',
                'type' => 'text',
                'content' => [
                    'html' => '
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td style="border-left:4px solid #0f4c75;padding-left:14px;">
                    <p style="font-size:18px;font-weight:800;color:#111111;margin:0;">This Week\'s Top Articles</p>
                    </td>
                </tr>
                </table>',
                ],
                'settings' => array(
                    'bgColor'   => '#ffffff',
                    'textColor' => '#111111',
                    'alignment' => 'left',
                    'padding'   => ['top' => '32px', 'right' => '40px', 'bottom' => '20px', 'left' => '40px'],
                ),
            ),

            // Two-column posts
            array(
                'id'   => 'nl-post-1',
                'type' => 'post',
                'settings' => [
                    'count'       => 4,
                    'columns'     => 2,
                    'orderBy'     => 'newest',
                    'showExcerpt' => true,
                    'categories'  => [],
                    'showButton'  => true,
                    'showImage'   => true,
                    'buttonText'  => 'Read →',
                    'bgColor'     => '#ffffff',
                    'textColor'   => '#333333',
                    'padding'     => ['top' => '0px', 'right' => '10px', 'bottom' => '0px', 'left' => '10px'],
                ],
            ),

            // Divider
            array(
                'id'   => 'nl-divider-1',
                'type' => 'divider',
                'settings' => array(
                    'color'   => '#e0e0e0',
                    'height'  => '1px',
                    'spacing' => ['top' => '36px', 'bottom' => '36px'],
                ),
            ),

            // Quick stats / highlights table
            array(
                'id'   => 'nl-text-3',
                'type' => 'text',
                'content' => [
                    'html' => '
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td style="border-left:4px solid #0f4c75;padding-left:14px;">
                    <p style="font-size:18px;font-weight:800;color:#111111;margin:0;">By the Numbers</p>
                    </td>
                </tr>
                </table>',
                ],
                'settings' => array(
                    'bgColor'   => '#ffffff',
                    'textColor' => '#111111',
                    'alignment' => 'left',
                    'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '20px', 'left' => '40px'],
                ),
            ),

            array(
                'id'   => 'nl-table-1',
                'type' => 'table',
                'content' => [
                    ['Posts Published',   '{{posts_this_week}}'],
                    ['Total Readers',     '{{total_readers}}'],
                    ['Top Category',      '{{top_category}}'],
                    ['Trending Post',     '{{trending_post}}'],
                ],
                'settings' => array(
                    'borderColor'  => '#e8edf2',
                    'thBgColor'    => '#0f4c75',
                    'thTextColor'  => '#ffffff',
                    'bgColor'      => '#ffffff',
                    'textColor'    => '#333333',
                    'padding'      => ['top' => '0px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
                    'alignment'    => 'left',
                ),
            ),

            // Divider
            array(
                'id'   => 'nl-divider-2',
                'type' => 'divider',
                'settings' => array(
                    'color'   => '#e0e0e0',
                    'height'  => '1px',
                    'spacing' => ['top' => '36px', 'bottom' => '36px'],
                ),
            ),

            // Products from store
            array(
                'id'   => 'nl-text-4',
                'type' => 'text',
                'content' => [
                    'html' => '
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td style="border-left:4px solid #0f4c75;padding-left:14px;">
                    <p style="font-size:18px;font-weight:800;color:#111111;margin:0;">Featured This Week</p>
                    </td>
                </tr>
                </table>',
                ],
                'settings' => array(
                    'bgColor'   => '#ffffff',
                    'textColor' => '#111111',
                    'alignment' => 'left',
                    'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '20px', 'left' => '40px'],
                ),
            ),

            array(
                'id'   => 'nl-product-1',
                'type' => 'product',
                'settings' => [
                    'ids'         => [],
                    'count'       => 2,
                    'columns'     => 2,
                    'orderBy'     => 'date',
                    'order'       => 'desc',
                    'saleOnly'    => false,
                    'categories'  => [],
                    'showButton'  => true,
                    'showImage'   => true,
                    'buttonText'  => 'View Product →',
                    'bgColor'     => '#f9f9f9',
                    'textColor'   => '#222222',
                    'padding'     => ['top' => '0px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
                ],
            ),

            // CTA
            array(
                'id'   => 'nl-button-1',
                'type' => 'button',
                'content' => ['html' => 'Read All Articles on {{site_name}} →'],
                'settings' => array(
                    'href'         => '{{site_url}}',
                    'alignment'    => 'center',
                    'bgColor'      => '#0f4c75',
                    'textColor'    => '#ffffff',
                    'borderRadius' => '6px',
                    'fontSize'     => '15px',
                    'padding'      => ['top' => '14px', 'right' => '36px', 'bottom' => '14px', 'left' => '36px'],
                    'spacing'      => ['top' => '36px', 'bottom' => '36px'],
                ),
            ),
        );

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
        $blocks = array(

            // Urgency hero
            array(
                'id'   => 'promo-heading-1',
                'type' => 'heading',
                'content' => [
                    'html' => '
                <p style="font-size:12px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#ffe066;margin:0 0 12px;">Limited Time Offer</p>
                <h1 style="margin:0 0 14px;font-size:42px;font-weight:900;color:#ffffff;line-height:1.1;">UP TO 40% OFF</h1>
                <p style="font-size:17px;color:rgba(255,255,255,0.9);margin:0 0 20px;">Exclusive sale for subscribers only. Ends soon — don\'t miss out.</p>
                <p style="display:inline-block;background:#ffe066;color:#cc3300;font-size:13px;font-weight:800;letter-spacing:1px;padding:6px 18px;border-radius:20px;margin:0;">Use code: <span style="font-size:16px;">SUBSCRIBER40</span></p>',
                ],
                'settings' => array(
                    'level'     => 1,
                    'bgColor'   => '#cc3300',
                    'textColor' => '#ffffff',
                    'alignment' => 'center',
                    'padding'   => ['top' => '52px', 'right' => '40px', 'bottom' => '52px', 'left' => '40px'],
                ),
            ),

            // Sale products — prominent 2-col grid
            array(
                'id'   => 'promo-product-1',
                'type' => 'product',
                'settings' => [
                    'ids'         => [],
                    'count'       => 4,
                    'columns'     => 2,
                    'orderBy'     => 'date',
                    'order'       => 'desc',
                    'saleOnly'    => true,
                    'categories'  => [],
                    'showButton'  => true,
                    'showImage'   => true,
                    'buttonText'  => 'Grab the Deal →',
                    'bgColor'     => '#fff9f9',
                    'textColor'   => '#222222',
                    'padding'     => ['top' => '32px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
                ],
            ),

            // Big CTA
            array(
                'id'   => 'promo-button-1',
                'type' => 'button',
                'content' => ['html' => 'Shop the Sale — Ends Soon'],
                'settings' => array(
                    'href'         => '{{site_url}}/sale',
                    'alignment'    => 'center',
                    'bgColor'      => '#cc3300',
                    'textColor'    => '#ffffff',
                    'borderRadius' => '6px',
                    'fontSize'     => '17px',
                    'padding'      => ['top' => '16px', 'right' => '44px', 'bottom' => '16px', 'left' => '44px'],
                    'spacing'      => ['top' => '32px', 'bottom' => '12px'],
                ),
            ),

            // Urgency note
            array(
                'id'   => 'promo-text-1',
                'type' => 'text',
                'content' => [
                    'html' => '<p style="font-size:13px;color:#999999;text-align:center;margin:0;">
                ⏳ Sale ends {{sale_end_date}}. While stocks last. Discount applied at checkout with code <strong>SUBSCRIBER40</strong>.
                </p>',
                ],
                'settings' => array(
                    'bgColor'   => '#ffffff',
                    'textColor' => '#999999',
                    'alignment' => 'center',
                    'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '16px', 'left' => '40px'],
                ),
            ),

            // Divider
            array(
                'id'   => 'promo-divider-1',
                'type' => 'divider',
                'settings' => array(
                    'color'   => '#f0f0f0',
                    'height'  => '1px',
                    'spacing' => ['top' => '20px', 'bottom' => '28px'],
                ),
            ),

            // Trust columns
            array(
                'id'   => 'promo-columns-trust',
                'type' => 'columns',
                'content' => [
                    [
                        [
                            'type'    => 'text',
                            'id'   => 'promo-columns-trust-text-1',
                            'content' => ['html' => '<p style="text-align:center;font-size:13px;color:#666666;margin:0;"><strong>Quality Guaranteed</strong><br>Every item is top-tier</p>'],
                        ],
                    ],
                    [
                        [
                            'type'    => 'text',
                            'id'   => 'promo-columns-trust-text-2',
                            'content' => ['html' => '<p style="text-align:center;font-size:13px;color:#666666;margin:0;"><strong>Fast Delivery</strong><br>Dispatched within 24h</p>'],
                        ],
                    ],
                    [
                        [
                            'type'    => 'text',
                            'id'   => 'promo-columns-trust-text-3',
                            'content' => ['html' => '<p style="text-align:center;font-size:13px;color:#666666;margin:0;"><strong>Safe Payments</strong><br>256-bit SSL secured</p>'],
                        ],
                    ],
                ],
                'settings' => array(
                    'count'   => 3,
                    'gap'     => '10px',
                    'padding' => ['top' => '0px', 'right' => '40px', 'bottom' => '36px', 'left' => '40px'],
                ),
            ),
        );

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
        $blocks = array(

            // Bold announcement header
            array(
                'id'   => 'ann-heading-1',
                'type' => 'heading',
                'content' => [
                    'html' => '
                <p style="font-size:11px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,0.7);margin:0 0 12px;">Big News</p>
                <h1 style="margin:0 0 12px;font-size:32px;font-weight:900;color:#ffffff;line-height:1.2;">{{announcement_title}} 🎉</h1>
                <p style="font-size:16px;color:rgba(255,255,255,0.85);margin:0;">{{announcement_subtitle}}</p>',
                ],
                'settings' => array(
                    'level'     => 1,
                    'bgColor'   => '#2d2d2d',
                    'textColor' => '#ffffff',
                    'alignment' => 'center',
                    'padding'   => ['top' => '52px', 'right' => '40px', 'bottom' => '52px', 'left' => '40px'],
                ),
            ),

            // Body copy
            array(
                'id'   => 'ann-text-1',
                'type' => 'text',
                'content' => [
                    'html' => '
                <p style="font-size:17px;line-height:1.8;color:#333333;margin:0 0 16px;">
                Hi <strong>{{first_name}}</strong>,
                </p>
                <p style="font-size:17px;line-height:1.8;color:#333333;margin:0 0 16px;">
                We have some exciting news to share with you. {{announcement_body}}
                </p>
                <p style="font-size:17px;line-height:1.8;color:#333333;margin:0;">
                We\'re incredibly proud of this milestone and wanted you — our subscribers — to be the first to know.
                </p>',
                ],
                'settings' => array(
                    'bgColor'   => '#ffffff',
                    'textColor' => '#333333',
                    'alignment' => 'left',
                    'padding'   => ['top' => '36px', 'right' => '40px', 'bottom' => '28px', 'left' => '40px'],
                ),
            ),

            // Key highlights
            array(
                'id'   => 'ann-text-2',
                'type' => 'text',
                'content' => [
                    'html' => '<h2 style="font-size:17px;font-weight:700;color:#111111;margin:0 0 16px;">What this means for you:</h2>
                <ul style="margin:0;padding:0 0 0 0;list-style:none;">
                <li style="display:flex;gap:10px;margin-bottom:14px;font-size:16px;line-height:1.6;color:#444444;">
                    <span style="color:#0073aa;font-weight:700;flex-shrink:0;">→</span>
                    <span>{{highlight_1}}</span>
                </li>
                <li style="display:flex;gap:10px;margin-bottom:14px;font-size:16px;line-height:1.6;color:#444444;">
                    <span style="color:#0073aa;font-weight:700;flex-shrink:0;">→</span>
                    <span>{{highlight_2}}</span>
                </li>
                <li style="display:flex;gap:10px;font-size:16px;line-height:1.6;color:#444444;">
                    <span style="color:#0073aa;font-weight:700;flex-shrink:0;">→</span>
                    <span>{{highlight_3}}</span>
                </li>
                </ul>',
                ],
                'settings' => array(
                    'bgColor'   => '#f7f9fc',
                    'textColor' => '#444444',
                    'alignment' => 'left',
                    'padding'   => ['top' => '28px', 'right' => '40px', 'bottom' => '28px', 'left' => '40px'],
                ),
            ),

            // Visual spacer
            array(
                'id'     => 'ann-spacer-1',
                'type'   => 'spacer',
                'height' => '20px',
            ),

            // Related posts
            array(
                'id'   => 'ann-text-3',
                'type' => 'text',
                'content' => [
                    'html' => '<h2 style="font-size:17px;font-weight:700;color:#111111;margin:0;">Read more about it</h2>',
                ],
                'settings' => array(
                    'bgColor'   => '#ffffff',
                    'textColor' => '#111111',
                    'alignment' => 'left',
                    'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '16px', 'left' => '40px'],
                ),
            ),

            array(
                'id'   => 'ann-post-1',
                'type' => 'post',
                'settings' => [
                    'count'       => 2,
                    'columns'     => 2,
                    'orderBy'     => 'newest',
                    'showExcerpt' => false,
                    'categories'  => [],
                    'showButton'  => true,
                    'showImage'   => true,
                    'buttonText'  => 'Read More →',
                    'bgColor'     => '#ffffff',
                    'textColor'   => '#333333',
                    'padding'     => ['top' => '0px', 'right' => '40px', 'bottom' => '0px', 'left' => '40px'],
                ],
            ),

            // Primary CTA
            array(
                'id'   => 'ann-button-1',
                'type' => 'button',
                'content' => ['html' => 'Learn More About {{announcement_title}} →'],
                'settings' => array(
                    'href'         => '{{announcement_url}}',
                    'alignment'    => 'center',
                    'bgColor'      => '#2d2d2d',
                    'textColor'    => '#ffffff',
                    'borderRadius' => '6px',
                    'fontSize'     => '16px',
                    'padding'      => ['top' => '14px', 'right' => '36px', 'bottom' => '14px', 'left' => '36px'],
                    'spacing'      => ['top' => '32px', 'bottom' => '16px'],
                ),
            ),

            // Social nudge
            array(
                'id'   => 'ann-text-4',
                'type' => 'text',
                'content' => [
                    'html' => '
                <div style="background:#f9f9f9;border-radius:8px;padding:20px 24px;text-align:center;">
                <p style="font-size:14px;color:#888888;margin:0 0 6px;">Love this news? Spread the word</p>
                <p style="font-size:14px;margin:0;">
                    <a href="{{twitter_share_url}}" style="color:#1da1f2;text-decoration:none;font-weight:600;margin-right:16px;">Share on X</a>
                    <a href="{{facebook_share_url}}" style="color:#1877f2;text-decoration:none;font-weight:600;margin-right:16px;">Share on Facebook</a>
                    <a href="{{linkedin_share_url}}" style="color:#0077b5;text-decoration:none;font-weight:600;">Share on LinkedIn</a>
                </p>
                </div>',
                ],
                'settings' => array(
                    'bgColor'   => '#ffffff',
                    'textColor' => '#888888',
                    'alignment' => 'center',
                    'padding'   => ['top' => '0px', 'right' => '40px', 'bottom' => '20px', 'left' => '40px'],
                ),
            ),

            // Sign-off
            array(
                'id'   => 'ann-text-5',
                'type' => 'text',
                'content' => [
                    'html' => '<p style="font-size:16px;line-height:1.7;margin:0;">
                With excitement,<br>
                <strong>The {{site_name}} Team</strong>
                </p>',
                ],
                'settings' => array(
                    'bgColor'   => '#ffffff',
                    'textColor' => '#333333',
                    'alignment' => 'left',
                    'padding'   => ['top' => '20px', 'right' => '40px', 'bottom' => '36px', 'left' => '40px'],
                ),
            ),
        );

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
                'bgColor'         => 'transparent',
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
                'bgColor'   => 'transparent',
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
}
