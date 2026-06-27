<?php


namespace ChicpixiesBloomMailer;

use WP_Theme_JSON_Resolver;

if (! defined('ABSPATH')) {
    exit;
}
class Helpers
{
    /**
     * Currency
     * 
     * @param string|null $code 'code'
     * @return string woocommerce currency code or symbol
     */
    public static function currency($code = null)
    {
        $currency_code = get_woocommerce_currency();

        return $code === 'code' ? $currency_code : get_woocommerce_currency_symbol($code);
    }

    public static function formats($data)
    {
        $formats = [];
        foreach ($data as $value) {
            $formats[] = match (true) {
                is_int($value) => '%d',
                is_float($value) => '%f',
                default => '%s',
            };
        }
        return $formats;
    }

    /**
     * Build the merged font family map: system presets, overridden by any
     * theme.json `typography.fontFamilies` entries with matching slugs.
     * Shared by resolve_font_family() and get_font_family_options().
     */
    public static function build_font_family_map()
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }

        $theme_fonts = [];

        if (function_exists('wp_get_global_settings')) {
            $settings = wp_get_global_settings();
            if (!empty($settings['typography']['fontFamilies'])) {
                foreach ($settings['typography']['fontFamilies'] as $family) {
                    if (isset($family['slug']) && isset($family['fontFamily'])) {
                        $theme_fonts[$family['slug']] = [
                            'label' => $family['name'] ?? $family['slug'],
                            'stack' => $family['fontFamily'],
                        ];
                    }
                }
            }
        }

        if (empty($theme_fonts) && class_exists('WP_Theme_JSON_Resolver')) {
            $theme_json = WP_Theme_JSON_Resolver::get_merged_data();
            if ($theme_json) {
                $settings = $theme_json->get_settings();
                if (!empty($settings['typography']['fontFamilies'])) {
                    foreach ($settings['typography']['fontFamilies'] as $family) {
                        if (isset($family['slug']) && isset($family['fontFamily'])) {
                            $theme_fonts[$family['slug']] = [
                                'label' => $family['name'] ?? $family['slug'],
                                'stack' => $family['fontFamily'],
                            ];
                        }
                    }
                }
            }
        }

        $map = [
            'theme'  => $theme_fonts,            // slug => ['label' => ..., 'stack' => ...]
            'system' => self::font_families(),   // slug => stack (your existing static list, untouched)
        ];

        return $map;
    }

    private static function font_families()
    {
        return [
            'system-ui'  => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
            'system'     => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
            'arial'      => 'Arial, Helvetica, sans-serif',
            'helvetica'  => "'Helvetica Neue', Helvetica, Arial, sans-serif",
            'times'      => "'Times New Roman', Times, serif",
            'times-new-roman' => "'Times New Roman', Times, serif",
            'georgia'    => 'Georgia, serif',
            'courier'    => "'Courier New', Courier, monospace",
            'courier-new' => "'Courier New', Courier, monospace",
            'verdana'    => 'Verdana, Geneva, sans-serif',
            'tahoma'     => 'Tahoma, Geneva, sans-serif',
            'trebuchet'  => "'Trebuchet MS', Helvetica, sans-serif",
            'trebuchet-ms' => "'Trebuchet MS', Helvetica, sans-serif",
            'palatino'   => "'Palatino Linotype', 'Book Antiqua', Palatino, serif",
            'garamond'   => 'Garamond, serif',
            'impact'     => 'Impact, Charcoal, sans-serif',
            'comic-sans' => "'Comic Sans MS', cursive, sans-serif",
            'comic-sans-ms' => "'Comic Sans MS', cursive, sans-serif",
            'monospace'  => "Monaco, 'Lucida Console', Courier, monospace",
        ];
    }

    /**
     * Editor-facing dropdown options. Theme fonts surface first under their
     * real names; the static list is collapsed to one canonical entry per
     * stack (skips 'times' once 'times-new-roman' is listed, etc).
     */
    public static function get_font_family_options()
    {
        $built = self::build_font_family_map();
        $options = [];

        foreach ($built['theme'] as $slug => $font) {
            $options[] = [
                'value' => $slug,
                'label' => $font['label'],
                'stack' => $font['stack'],
            ];
        }

        $canonical = [
            'system-ui'        => __('System Default', 'cps-bloom-mailer'),
            'arial'            => __('Arial', 'cps-bloom-mailer'),
            'helvetica'        => __('Helvetica', 'cps-bloom-mailer'),
            'times-new-roman'  => __('Times New Roman', 'cps-bloom-mailer'),
            'georgia'          => __('Georgia', 'cps-bloom-mailer'),
            'courier-new'      => __('Courier New', 'cps-bloom-mailer'),
            'verdana'          => __('Verdana', 'cps-bloom-mailer'),
            'tahoma'           => __('Tahoma', 'cps-bloom-mailer'),
            'trebuchet-ms'     => __('Trebuchet MS', 'cps-bloom-mailer'),
            'palatino'         => __('Palatino', 'cps-bloom-mailer'),
            'garamond'         => __('Garamond', 'cps-bloom-mailer'),
            'impact'           => __('Impact', 'cps-bloom-mailer'),
            'comic-sans-ms'    => __('Comic Sans MS', 'cps-bloom-mailer'),
            'monospace'        => __('Monospace', 'cps-bloom-mailer'),
        ];

        foreach ($canonical as $slug => $label) {
            if (isset($built['theme'][$slug])) {
                continue; // a theme font already claimed this slug name
            }
            $options[] = [
                'value' => $slug,
                'label' => $label,
                'stack' => $built['system'][$slug] ?? null,
            ];
        }

        return $options;
    }

    public static function get_events()
    {
        $data  = [
            [
                'value' => 'new_subscriber',
                'label' => __('New Subscriber', 'cps-bloom-mailer'),
                'description' => __('Fires when someone subscribes — ideal for welcome emails.', 'cps-bloom-mailer'),
            ],
            [
                'value' => 'new_unsubscriber',
                'label' => __('New Unsubscriber', 'cps-bloom-mailer'),
                'description' => __('Fires when someone unsubscribes — ideal for farewell emails.', 'cps-bloom-mailer'),
            ],
            [
                'value' => 'new_post',
                'label' => __('New Post', 'cps-bloom-mailer'),
                'description' => __('Fires when a new post is published.', 'cps-bloom-mailer'),
            ],
            [
                'value' => 'new_product',
                'label' => __('New Product', 'cps-bloom-mailer'),
                'description' => __('Fires when a WooCommerce product is published.', 'cps-bloom-mailer'),
            ]
        ];
        return apply_filters('cps_mailer_event_types', $data);
    }

    /**
     * Get all available merge tags
     * 
     * @return array List of merge tags with descriptions
     */
    public static function get_placeholders()
    {
        $data = array(
            // Site Information
            array(
                'value' => '{{site_name}}',
                'label' => 'Site Name',
                'description' => 'The name of your website',
            ),
            array(
                'value' => '{{site_url}}',
                'label' => 'Site URL',
                'description' => 'Your website URL',
            ),
            array(
                'value' => '{{site_logo}}',
                'label' => 'Site Logo',
                'description' => 'Your website logo image',
            ),
            array(
                'value' => '{{first_name}}',
                'label' => 'First Name',
                'description' => 'The subscriber\'s first name',
            ),
            array(
                'value' => '{{last_name}}',
                'label' => 'Last Name',
                'description' => 'The subscriber\'s last name',
            ),
            array(
                'value' => '{{email}}',
                'label' => 'Email',
                'description' => 'The subscriber\'s email address',
            ),
            array(
                'value' => '{{current_year}}',
                'label' => 'Current Year',
                'description' => 'The current year (e.g. 2024)',
            ),
            array(
                'value' => '{{unsubscribe_url}}',
                'label' => 'Unsubscribe URL',
                'description' => 'A unique link for the subscriber to unsubscribe from emails',
            )
        );
        return apply_filters('cps_mailer_placeholders', $data);
    }

    /**
     * Returns default spacing for a given section type.
     *
     * @param string $type  block | header | footer | button | space
     * @return array{ top: int, right: int, bottom: int, left: int}
     */
    public static function default_spacing(string $type = 'block'): array
    {
        $map = [
            'separator' => ['top' => '10px', 'bottom' => '10px'],
            'block' => ['top' => '15px', 'right' => '10px', 'bottom' => '15px', 'left' => '10px'],
            'blockMargin' => ['bottom' => '20px'],
            'button' => ['top' => '12px', 'right' => '30px', 'bottom' => '12px', 'left' => '30px'],
            'space' => ['top' => '30px', 'bottom' => '30px'],
            'content' => ['top' => '30px', 'right' => '30px', 'bottom' => '30px', 'left' => '30px'],
            'header' => ['top' => '20px', 'right' => '40px', 'bottom' => '30px', 'left' => '40px'],
            'footer' => ['top' => '20px', 'right' => '40px', 'bottom' => '20px', 'left' => '40px'],
        ];
        return $map[$type] ?? $map['block'];
    }


    /**
     * Replace merge tags with actual values
     * 
     * @param string $content Content with merge tags
     * @param array $data Data to replace tags with
     * @return string Content with tags replaced
     */
    public static function replace_tags($content, $data)
    {
        if (empty($content)) {
            return '';
        }

        // Site information
        $replacements = [
            '{{site_name}}' => esc_html(get_bloginfo('name')),
            '{{site_url}}' => esc_url(get_bloginfo('url')),
            '{{current_year}}' => wp_date('Y'),
            '{{current_date}}' => wp_date('Y-m-d')
        ];

        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
            $logo_html = $logo_url
                ? sprintf(
                    '<img src="%s" alt="%s" style="max-width: 150px; height: auto;">',
                    esc_url($logo_url),
                    esc_attr(get_bloginfo('name'))
                )
                : '';
            $replacements['{{site_logo}}'] = $logo_html;
        } else {
            $replacements['{{site_logo}}'] = '';
        }

        // Replace custom data tags
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $tag = '{{' . $key . '}}';
                // Don't escape if it's already HTML (like site_logo)
                // Only escape plain text values
                $safe_value = is_string($value) && !preg_match('/<[^>]+>/', $value)
                    ? esc_html($value)
                    : $value;
                $replacements[$tag] = $safe_value;
            }
        }

        // Perform all replacements
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        // Remove any remaining unreplaced tags (optional)
        // $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);

        return $content;
    }

    public static function is_rtl()
    {
        /**
         * @return bool True if you want to render emails in RTL mode, false otherwise.
         */
        return apply_filters('cps_mailer_is_rtl', is_rtl());
    }
}
