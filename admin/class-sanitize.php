<?php

namespace ChicpixiesBloomMailer;

if (! defined('ABSPATH')) {
    exit;
}

class Sanitize
{
    /** 
     * Sanitize email campaigns before saving
     * @param array  $campaigns array of campaign data to sanitize
     * @param boolean $isTemplate 
     */
    public static function campaigns($campaigns, $isTemplate = false)
    {
        $sanitized = array();
        foreach ($campaigns as $campaign_id => $campaign) {
            $data = array(
                'title' => ucfirst(sanitize_text_field($campaign['title'] ?? '')),
                'subject' => sanitize_text_field($campaign['subject'] ?? ''),
                'preview_text' => sanitize_text_field($campaign['preview_text'] ?? ''),
                'blocks' => self::gutenberg($campaign['blocks'] ?? ''),
                'design' => self::design($campaign['design'] ?? []),
                'header' => self::header($campaign['header'] ?? []),
                'footer' => self::footer($campaign['footer'] ?? []),
            );

            if ($isTemplate) {
                $data['template_key'] = sanitize_text_field($campaign['template_key'] ?? uniqid('template_'));
                $data['description'] = sanitize_text_field($campaign['description'] ?? '');
                $data['is_default'] = absint($campaign['is_default'] ?? 0);
            } else {
                $data['from_name'] = sanitize_text_field($campaign['from_name'] ?? '');
                $data['from_email'] = sanitize_email($campaign['from_email'] ?? '');
                $data['reply_to'] = sanitize_email($campaign['reply_to'] ?? '');
                //$data['html'] =  wp_kses_post($campaign['html'] ?? '');
                $data['status'] = sanitize_text_field($campaign['status'] ?? 'draft');
            }

            $sanitized[$campaign_id] = $data;
        }
        return $sanitized;
    }

    /**
     * Sanitize design settings
     * @param array $design array
     */
    public static function design($design)
    {
        $sanitize = array(
            'bodyBg' => sanitize_text_field($design['bodyBg'] ?? '#f5f5f5'),
            'containerWidth' => absint($design['containerWidth'] ?? 600),
            'containerBg' => sanitize_text_field($design['containerBg'] ?? '#ffffff'),
            'padding' => self::spacing($design['padding'] ?? ['top' => '30px', 'right' => '30px', 'bottom' => '30px', 'left' => '30px']),
            'borderRadius' => absint($design['borderRadius'] ?? 8),
            'fontFamily' => sanitize_text_field($design['fontFamily'] ?? 'system'),
            'lineHeight' => absint($design['lineHeight'] ?? 1.2),
            'fontSize' => absint($design['fontSize'] ?? 16),
            'textColor' => sanitize_text_field($design['textColor'] ?? '#333333'),
            'linkColor' => sanitize_text_field($design['linkColor'] ?? '#0073aa')
        );
        return wp_json_encode($sanitize, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Sanitize header data
     */
    public static function header($header)
    {
        $sanitize = array(
            'enabled' => isset($header['enabled']) ? (bool)$header['enabled'] : true,
            'title' => self::html_content($header['title'] ?? []),
            'description' => self::html_content($header['description'] ?? []),
            'logo' => isset($header['logo']) ? (bool)$header['logo'] : false,
            'logoUrl' => esc_url_raw($header['logoUrl'] ?? ''),
            'logoWidth' => absint($header['logoWidth'] ?? 60),
            'settings' => array(
                'alignment' => in_array($header['settings']['alignment'] ?? '', ['left', 'center', 'right', 'justify'])
                    ? $header['settings']['alignment']
                    : 'center',
                'fontFamily' => sanitize_text_field($header['settings']['fontFamily'] ?? 'system'),
                'textColor' => sanitize_text_field($header['settings']['textColor'] ?? '#333333'),
                'background' => sanitize_text_field($header['settings']['background'] ?? ''),
                'titleSize' => sanitize_text_field($header['settings']['titleSize'] ?? '28px'),
                'fontSize' => sanitize_text_field($header['settings']['fontSize'] ?? '14px'),
                'showDescription' => isset($header['settings']['showDescription'])
                    ? (bool)$header['settings']['showDescription']
                    : false,
            ),
        );

        return wp_json_encode($sanitize, JSON_UNESCAPED_UNICODE);
    }

    public static function gutenberg($raw_blocks)
    {
        $raw_blocks = wp_unslash($raw_blocks);
        return wp_kses_post($raw_blocks);
    }

    /**
     * Sanitize body blocks
     */
    public static function blocks($blocks)
    {
        $sanitized = array();
        foreach ($blocks as $block) {
            $type = $block['type'] ?? 'text';

            switch ($type) {
                case 'heading':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('heading_')),
                        'type' => 'heading',
                        'content' => self::html_content($block['content'] ?? []),
                        'settings' => array(
                            'level' => in_array($block['settings']['level'] ?? 0, [1, 2, 3, 4, 5, 6])
                                ? (int)$block['settings']['level']
                                : 2,
                            'background' => sanitize_text_field($block['settings']['background'] ?? '#ffffff'),
                            'textColor' => sanitize_text_field($block['settings']['textColor'] ?? '#333333'),
                            'padding' => self::spacing($block['settings']['padding'] ?? []),
                            'alignment' => in_array($block['settings']['alignment'] ?? '', ['left', 'center', 'right'])
                                ? $block['settings']['alignment']
                                : 'left',
                        ),
                    );
                    break;

                case 'text':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('text_')),
                        'type' => 'text',
                        'content' => self::html_content($block['content'] ?? []),
                        'settings' => array(
                            'background' => sanitize_text_field($block['settings']['background'] ?? '#ffffff'),
                            'textColor' => sanitize_text_field($block['settings']['textColor'] ?? '#333333'),
                            'padding' => self::spacing($block['settings']['padding'] ?? []),
                            'alignment' => in_array($block['settings']['alignment'] ?? '', ['left', 'center', 'right'])
                                ? $block['settings']['alignment']
                                : 'left',
                        ),
                    );
                    break;

                case 'button':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('button_')),
                        'type' => 'button',
                        'content' => self::html_content($block['content'] ?? []),
                        'settings' => array(
                            'href' => sanitize_text_field($block['settings']['href'] ?? '#'),
                            'background' => sanitize_text_field($block['settings']['background'] ?? '#0073aa'),
                            'textColor' => sanitize_text_field($block['settings']['textColor'] ?? '#ffffff'),
                            'borderRadius' => sanitize_text_field($block['settings']['borderRadius'] ?? '5px'),
                            'padding' => self::spacing($block['settings']['padding'] ?? [], ['top' => '12px', 'right' => '30px', 'bottom' => '12px', 'left' => '30px']),
                            'spacing' => self::spacing($block['settings']['spacing'] ?? ['top' => '30px', 'bottom' => '30px']),
                            'fontSize' => sanitize_text_field($block['settings']['fontSize'] ?? '16px'),
                            'alignment' => in_array($block['settings']['alignment'] ?? '', ['left', 'center', 'right'])
                                ? $block['settings']['alignment']
                                : 'left',
                        ),
                    );
                    break;

                case 'divider':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('divider_')),
                        'type' => 'divider',
                        'settings' => array(
                            'color' => sanitize_text_field($block['settings']['color'] ?? '#e0e0e0'),
                            'height' => sanitize_text_field($block['settings']['height'] ?? '1px'),
                            'spacing' => self::spacing($block['settings']['spacing'] ?? [], ['top' => '20px', 'bottom' => '20px']),
                        ),
                    );
                    break;

                case 'spacer':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('spacer_')),
                        'type' => 'spacer',
                        'height' => sanitize_text_field($block['height'] ?? '20px'),
                    );
                    break;

                case 'image':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('image_')),
                        'type' => 'image',
                        'settings' => [
                            'src' => esc_url_raw($block['settings']['src'] ?? ''),
                            'alt' => sanitize_text_field($block['settings']['alt'] ?? ''),
                            'width' => absint($block['settings']['width'] ?? 0),
                            'alignment' => in_array($block['settings']['alignment'] ?? '', ['left', 'center', 'right'])
                                ? $block['settings']['alignment']
                                : 'left',
                            'padding' => self::spacing($block['settings']['padding'] ?? []),
                        ],
                    );
                    break;

                case 'post':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('post_')),
                        'type' => 'post',
                        'content' => $block['content'] ?? [],
                        'settings' => array(
                            'ids' => !empty($block['settings']['ids']) && is_array($block['settings']['ids']) ? array_map('absint', $block['settings']['ids']) : [],
                            'count' => absint($block['settings']['count'] ?? 2),
                            'columns' => absint($block['settings']['columns'] ?? 2),
                            'orderBy' => sanitize_text_field($block['settings']['orderBy'] ?? 'newest'),
                            'showExcerpt' => isset($block['settings']['showExcerpt']) ? (bool)$block['settings']['showExcerpt'] : false,
                            'categories' => self::categories($block['settings']['categories'] ?? []),
                            'showButton' => isset($block['settings']['showButton']) ? (bool)$block['settings']['showButton'] : true,
                            'showImage' => isset($block['settings']['showImage']) ? (bool)$block['settings']['showImage'] : true,
                            'buttonText' => sanitize_text_field($block['settings']['buttonText'] ?? 'Read More'),
                            'background' => sanitize_text_field($block['settings']['background'] ?? '#ffffff'),
                            'textColor' => sanitize_text_field($block['settings']['textColor'] ?? '#333333'),
                            'padding' => self::spacing($block['settings']['padding'] ?? []),

                        ),
                    );
                    break;

                case 'product':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('product_')),
                        'type' => 'product',
                        'content' => $block['content'] ?? [],
                        'settings' => array(
                            'ids' => !empty($block['settings']['ids']) && is_array($block['settings']['ids']) ? array_map('absint', $block['settings']['ids']) : [],
                            'count' => absint($block['settings']['count'] ?? 2),
                            'columns' => absint($block['settings']['columns'] ?? 2),
                            'orderBy' => sanitize_text_field($block['settings']['orderBy'] ?? 'date'),
                            'order' => sanitize_text_field($block['settings']['order'] ?? 'asc'),
                            'saleOnly' => isset($block['settings']['saleOnly']) ? (bool)$block['settings']['saleOnly'] : false,
                            'categories' => self::categories($block['settings']['categories'] ?? []),
                            'showButton' => isset($block['settings']['showButton']) ? (bool)$block['settings']['showButton'] : true,
                            'showImage' => isset($block['settings']['showImage']) ? (bool)$block['settings']['showImage'] : true,
                            'buttonText' => sanitize_text_field($block['settings']['buttonText'] ?? 'Read More'),
                            'background' => sanitize_text_field($block['settings']['background'] ?? '#ffffff'),
                            'textColor' => sanitize_text_field($block['settings']['textColor'] ?? '#333333'),
                            'padding' => self::spacing($block['settings']['padding'] ?? []),
                        ),
                    );
                    break;

                case 'columns':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('columns_')),
                        'type' => 'columns',
                        'content' => self::columns($block['content'] ?? []),
                        'settings' => array(
                            'count' => absint($block['settings']['count'] ?? 2),
                            'gap' => sanitize_text_field($block['settings']['gap'] ?? '20px'),
                            'padding' => self::spacing($block['settings']['padding'] ?? [])
                        )
                    );
                    break;
                case 'table':
                    $sanitized[] = array(
                        'id' => sanitize_key($block['id'] ?? uniqid('table_')),
                        'type' => 'table',
                        'content' => self::table($block['content'] ?? []),
                        'settings' => array(
                            'borderColor' => sanitize_text_field($block['settings']['borderColor'] ?? '#e0e0e0'),
                            'thbackground' => sanitize_text_field($block['settings']['thbackground'] ?? '#0073aa'),
                            'thTextColor' => sanitize_text_field($block['settings']['thTextColor'] ?? '#ffffff'),
                            'background' => sanitize_text_field($block['settings']['background'] ?? '#ffffff'),
                            'textColor' => sanitize_text_field($block['settings']['textColor'] ?? '#333333'),
                            'padding' => self::spacing($block['settings']['padding'] ?? []),
                            'alignment' => in_array($block['settings']['alignment'] ?? '', ['left', 'center', 'right'])
                                ? $block['settings']['alignment']
                                : 'left',
                        ),
                    );
                    break;
            }
        }

        return wp_json_encode($sanitized, JSON_UNESCAPED_UNICODE);
    }

    private static function categories($categories)
    {
        if (!is_array($categories)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($category) {
            if (is_numeric($category)) {
                return absint($category);
            }

            if (is_string($category)) {
                return sanitize_text_field($category);
            }

            return null;
        }, $categories)));
    }

    public static function html_content($content)
    {
        if (!is_array($content)) {
            return ['html' => '', 'json' => []];
        }

        return [
            'html' => isset($content['html']) ? wp_kses_post($content['html']) : '',
            'json' => isset($content['json']) && is_array($content['json']) ? $content['json'] : []
        ];
    }

    public static function columns($content)
    {
        if (!is_array($content)) {
            return [];
        }

        return array_map(function ($column) {

            if (!is_array($column)) {
                return [];
            }

            return array_map(function ($block) {
                return is_array($block) ? $block : [];
            }, $column);
        }, $content);
    }

    public static function table($content)
    {
        if (!is_array($content)) {
            return [];
        }

        return array_map(function ($row) {
            if (!is_array($row)) {
                return [];
            }

            return array_map(function ($cell) {
                return wp_kses_post($cell);
            }, $row);
        }, $content);
    }

    public static function array($content)
    {
        if (!is_array($content)) return [];

        $array = [];
        foreach ($content as $key => $value) {
            if (is_numeric($value)) {
                $array[$key] = absint($value);
            } elseif (is_string($value)) {
                if (str_contains($value, 'http')) {
                    $array[$key] = esc_url_raw($value ?? '');
                } else {
                    $array[$key] = sanitize_text_field($value);
                }
            }
        }
        return $array;
    }

    public static function spacing($spacing = [], $default = ['top' => '10px', 'right' => '10px', 'bottom' => '10px', 'left' => '10px'])
    {
        if (!is_array($spacing)) return $default;

        $spacing = wp_parse_args($spacing, $default);

        foreach ($spacing as $key => $value) {
            if (is_numeric($value)) {
                $spacing[$key] = intval($value) . 'px';
                continue;
            }

            $value = sanitize_text_field($value);

            // if it’s just a number string like "10"
            if (preg_match('/^\d+$/', $value)) {
                $spacing[$key] = $value . 'px';
            } else {
                $spacing[$key] = $value;
            }
        }

        return $spacing;
    }

    /**
     * Sanitize footer data
     */
    public static function footer($footer)
    {
        $sanitize = array(
            'enabled' => isset($footer['enabled']) ? (bool)$footer['enabled'] : true,
            'content' => self::html_content($footer['content'] ?? []),
            'settings' => array(
                'alignment' => in_array($footer['settings']['alignment'] ?? '', ['left', 'center', 'right'])
                    ? $footer['settings']['alignment']
                    : 'center',
                'linkColor' => sanitize_text_field($footer['linkColor'] ?? '#0073aa'),
                'textColor' => sanitize_text_field($footer['settings']['textColor'] ?? '#666666'),
                'fontSize' => sanitize_text_field($footer['settings']['fontSize'] ?? '12px'),
                'background' => sanitize_text_field($footer['settings']['background'] ?? 'transparent'),
                'padding' => self::spacing($footer['settings']['padding'] ?? ['top' => '20px', 'right' => '40px', 'bottom' => '20px', 'left' => '40px']),
            ),
        );

        return wp_json_encode($sanitize, JSON_UNESCAPED_UNICODE);
    }
}
