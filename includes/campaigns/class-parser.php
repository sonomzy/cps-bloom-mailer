<?php

namespace ChicpixiesBloomMailer;

use WP_Query;

if (! defined('ABSPATH')) exit;

class Parser
{
    private static function parse(string $block_markup)
    {
        if (function_exists('parse_blocks')) {
            return parse_blocks($block_markup);
        } else {
            // Fallback: use custom parser
            return self::manual($block_markup);
        }
    }

    // ── Block list ────────────────────────────────────────────────────────────
    private static function blocks(array $blocks): string
    {
        $output = '';
        foreach ($blocks as $block) {
            if (empty($block['blockName'])) {
                // Raw HTML / whitespace between blocks
                $output .= wp_kses_post($block['innerHTML']);
                continue;
            }
            $output .= self::render_block($block);
        }
        return $output;
    }

    private static function render_block(array $block): string
    {
        return match ($block['blockName']) {
            'core/paragraph'  => self::paragraph($block), //done
            'core/heading'    => self::heading($block), //done
            'core/image'      => self::image($block), //done
            'core/buttons'    => self::buttons($block), //done
            'core/button'     => self::button($block), //done
            'core/columns'    => self::columns($block), //done
            'core/column'     => self::column($block), //done
            'core/group'      => self::group($block), //done
            'core/separator'  => self::separator($block), //done
            'core/spacer'     => self::spacer($block), //done
            'core/quote'      => self::quote($block), //done
            'core/pullquote'  => self::pull_quote($block), //done
            'core/table'      => self::table($block), //done
            'core/rss'        => self::rss($block), //done
            'core/list'       => self::list($block), //done
            'core/list-item'  => self::list_item($block), //done
            'cps-bloom-mailer/socials' => self::social_links($block), //done
            'cps-bloom-mailer/post'    => self::post($block), //done
            'cps-bloom-mailer/product' => self::product($block), //done
            default              => self::generic($block), //works
        };
    }

    // ── Blocks ────────────────────────────────────────────────────────────────
    private static function header(array $header, array $design): string
    {
        if (empty($header['enabled'])) {
            return '';
        }

        $s          = $header['settings'] ?? [];
        $bg_color   = self::resolve_color($s['bgColor'] ?? '#ffffff');
        $padding    = self::spacing_style(['spacing' => $s['padding'] ?? self::default_spacing('header')]);
        $alignment  = $s['alignment'] ?? 'center';
        $font       = $s['fontFamily'] ?? 'sans-serif';
        $text_color = self::resolve_color($s['textColor'] ?? '#333333');
        $logo_url   = $header['logoUrl']   ?? '';
        $logo_width = $header['logoWidth'] ?? 60;

        if (! empty($header['logo']) && empty($logo_url)) {
            $inner = '{{site_logo}}';
        } elseif (! empty($logo_url)) {
            $inner = "<img src=\"{$logo_url}\" alt=\"{{site_name}}\" width=\"{$logo_width}%\" style=\"height:auto;display:block;margin:0 auto;\" />";
        } else {
            $title      = $header['title']['html'] ?? '{{site_name}}';
            $title_size = $s['titleSize'] ?? '28px';
            $inner      = "<h1 style=\"margin:0;color:{$text_color};font-size:{$title_size};font-family:{$font};\">{$title}</h1>";
        }

        $desc_html = '';
        if (! empty($s['showDescription'])) {
            $font_size   = $s['fontSize']   ?? '14px';
            $description = $header['description']['html'] ?? '';
            $desc_html   = "<p style=\"margin:10px 0 0;font-size:{$font_size};color:{$text_color};font-family:{$font};\">{$description}</p>";
        }

        return "<tr>
            <td style=\"background:{$bg_color};padding:{$padding};text-align:{$alignment};\">
				{$inner}
				{$desc_html}
			</td>
        </tr>";
    }

    private static function footer(array $footer, array $design): string
    {
        if (empty($footer['enabled'])) {
            return '';
        }

        $s          = $footer['settings'] ?? [];
        $bg_color   = self::resolve_color($s['bgColor'] ?? '#ffffff');
        $alignment  = $s['alignment'] ?? 'center';
        $padding    = self::spacing_style(['spacing' => $s['padding'] ?? self::default_spacing('footer')]);
        $font_size  = $s['fontSize']  ?? '12';
        $text_color = self::resolve_color($s['textcolor'] ?? '#666666');
        $content    = $footer['content']['html'] ?? '';

        return " <tr>
            <td style=\"background:{$bg_color};text-align:{$alignment};padding:{$padding};border-top:1px solid #e0e0e0;font-size:{$font_size};color:{$text_color};\">
                {$content}
            </td>
        </tr>";
    }

    private static function paragraph(array $block): string
    {
        $attrs   = $block['attrs'] ?? [];
        $content = $block['innerHTML'];
        $style   = self::resolveStyles($attrs, ['type' => 'block']);
        $inlinStyle = !empty($style) ? " style=\"$style\"" : '';
        $content = self::inner_html($content, 'p');
        $margin = self::spacing_style($attrs, ['prop' => 'margin', 'type' => 'blockMargin']);
        return "<tr><td style=\"$margin\"><span{$inlinStyle}>{$content}</span></td></tr>";
    }

    private static function heading(array $block): string
    {
        $attrs   = $block['attrs'] ?? [];
        $level   = $attrs['level'] ?? 2;
        $tag     = "h{$level}";

        $innerContent = self::inner_html($block['innerHTML'], $tag);
        $style  = self::resolveStyles($attrs, ['type' => 'block']);
        $inlinStyle = !empty($style) ? " style=\"$style\"" : '';
        $content = "<$tag{$inlinStyle}>{$innerContent}</$tag>";
        $margin = self::spacing_style($attrs, ['prop' => 'margin', 'type' => 'blockMargin']);

        return "<tr><td style=\"$margin\">{$content}</td></tr>";
    }

    private static function image(array $block): string
    {
        $attrs  = $block['attrs'] ?? [];
        $align  = $attrs['align'] ?? 'center';
        $style  = self::spacing_style($attrs);

        $innerHTML = $block['innerHTML'];
        if (preg_match('/<figure[^>]*>(.*?)<\/figure>/s', $innerHTML, $matches)) {
            $innerHTML = $matches[1];
        }

        // Extract and strip figcaption
        $caption = '';
        if (preg_match('/<figcaption[^>]*>(.*?)<\/figcaption>/s', $innerHTML, $cm)) {
            $caption   = $cm[1];
            $innerHTML = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/s', '', $innerHTML);
        }

        $imgStyle = 'display:block;width:100%;max-width:100%;height:auto;';

        $innerHTML = preg_replace_callback(
            '/<img([^>]*?)>/s',
            function ($m) use ($imgStyle) {
                $attrs = $m[1];
                $attrs = preg_replace('/\s*class=(["\'])(.*?)\1/i', '', $attrs);
                if (preg_match('/style="([^"]*)"/i', $attrs, $sm)) {
                    $attrs = preg_replace('/style="[^"]*"/i', "style=\"$imgStyle\"", $attrs);
                } else {
                    $attrs .= "style=\"$imgStyle\"";
                }
                return '<img' . $attrs . '>';
            },
            $innerHTML
        );


        $tdStyle = self::border_style($attrs);
        $inlinStyle = !empty($style) ? " style=\"$style\"" : '';
        $captionHtml = $caption ? "<tr>
            <td style=\"text-align:center;font-size:13px;color:#666666;padding:4px 10px 0;\">
                {$caption}
            </td>
        </tr>" : '';

        return "<tr>
            <td align=\"{$align}\"{$inlinStyle}>
                <table role=\"presentation\" width=\"100%\" style=\"width:100%;border:0;border-collapse:separate;\">
                    <tr>
                        <td style=\"overflow:hidden;$tdStyle\">
                            {$innerHTML}
                        </td>
                    </tr>
                    {$captionHtml}
                </table>
            </td>
        </tr>";
    }

    private static function buttons(array $block): string
    {
        $attrs        = $block['attrs'] ?? [];
        $layout       = $attrs['layout'] ?? [];
        $style        = self::resolveStyles($attrs);
        $inner_blocks = $block['innerBlocks'] ?? [];
        $count        = count($inner_blocks);

        if (!$count) return '';

        $orientation = strtolower($layout['orientation'] ?? 'horizontal');
        $gap         = self::resolve_block_gap($attrs['style']['spacing']['blockGap'] ?? '');
        $padding     = !empty($gap) ? "padding:{$gap};" : 'padding:4px;';

        if ($orientation === 'vertical') {
            // Stack buttons in separate rows
            $rows = '';
            foreach ($inner_blocks as $button) {
                $width    = !empty($button['attrs']['width']) ? "{$button['attrs']['width']}%" : 'auto';
                $rows .= "<tr>
                <td align=\"center\" style=\"{$padding}\">
                    <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
                        <tr>
                            <td style=\"width:{$width};\">
                                " . self::button($button) . "
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>";
            }

            return "<tr>
                <td style=\"{$style}\">
                    <table role=\"presentation\" style=\"width:100%;border:0;border-collapse:collapse;\">
                        {$rows}
                    </table>
                </td>
            </tr>";
        }

        // Horizontal — buttons side by side
        $btns = '';
        foreach ($inner_blocks as $button) {
            $width = !empty($button['attrs']['width']) ? "width:{$button['attrs']['width']}%;" : '';
            $btns .= "<td class=\"email-column\" style=\"{$padding}{$width}\">
            " . self::button($button) . "
        </td>";
        }

        return "<tr>
            <td style=\"{$style}\">
                <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;border:0;border-collapse:collapse;\">
                    <tr>{$btns}</tr>
                </table>
            </td>
        </tr>";
    }

    private static function button(array $block): string
    {
        $attrs = $block['attrs'] ?? [];
        $text  = wp_strip_all_tags($block['innerHTML']);
        $url   = esc_url($attrs['url'] ?? '#');
        $style = self::resolveStyles($attrs, ['type' => 'button']);
        $width = !empty($attrs['width']) ? " style=\"width:100%;border:0;border-collapse:separate;\"" : '';

        return "<table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"$width>
            <tr>
                <td style=\"{$style}\">
                    <a href=\"{$url}\" style=\"display:block;text-decoration:none;color:inherit;\">{$text}</a>
                </td>
            </tr>
        </table>";
    }

    private static function list(array $block): string
    {
        $attrs        = $block['attrs'] ?? [];
        $inner_blocks = $block['innerBlocks'] ?? [];
        $style        = self::resolveStyles($attrs);
        $count        = count($inner_blocks);

        if (!$count) return '';
        $listItems = '';
        foreach ($inner_blocks as $item) {
            $listItems .= self::list_item($item);
        }

        $tag = 'ul';
        $type = ' disc';
        if (!empty($attrs['ordered'])) {
            $type = ' decimal';
            $tag = '0l';
        }

        $margin = self::spacing_style($attrs, ['prop' => 'margin', 'type' => 'blockMargin']);
        return "<tr>
            <td style=\"$margin\">
                <{$tag} style=\"margin:0;list-style:inside{$type};{$style}\">{$listItems}</{$tag}>
            </td>
        </tr>";
    }

    private static function list_item(array $block): string
    {
        $content = $block['innerHTML'];
        $attrs   = $block['attrs'] ?? [];

        if (!empty($attrs)) {
            $style = self::resolveStyles($attrs);

            $content = preg_replace_callback('/<li\b([^>]*)>/i', function ($matches) use ($style) {
                $liAttrs = $matches[1];
                // Strip class
                $liAttrs = preg_replace('/\s*class=(["\'])(.*?)\1/i', '', $liAttrs);

                if (preg_match('/style=(["\'])(.*?)\1/i', $liAttrs, $styleMatch)) {
                    $merged  = rtrim(trim($styleMatch[2]), ';') . ';' . $style;
                    $liAttrs = preg_replace('/style=(["\'])(.*?)\1/i', 'style="' . esc_attr($style) . '"', $liAttrs);
                    return '<li' . $liAttrs . '>';
                }

                return '<li' . $liAttrs . ' style="' . esc_attr($style) . '">';
            }, $content, 1);
        }

        if (!empty($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as $nested) {
                if ($nested['blockName'] === 'core/list') {
                    $nestedItems = '';
                    foreach ($nested['innerBlocks'] as $nestedItem) {
                        $nestedItems .= self::list_item($nestedItem);
                    }
                    $tag = !empty($nested['attrs']['ordered']) ? 'ol' : 'ul';
                    $content = preg_replace('/<\/li>$/', "<{$tag} style=\"margin:0;padding-left:20px;\">{$nestedItems}</{$tag}></li>", $content);
                }
            }
        }

        return $content;
    }

    private static function columns(array $block): string
    {
        $attrs        = $block['attrs'] ?? [];
        $inner_blocks = $block['innerBlocks'] ?? [];
        $style        = self::resolveStyles($attrs);
        $count        = count($inner_blocks);

        if (!$count) return '';

        $gap  = self::resolve_block_gap($attrs['style']['spacing']['blockGap'] ?? '');
        $cols = '';

        $fallback_pct = floor(100 / $count);

        foreach ($inner_blocks as $column) {
            $colStyle = self::nested_style([
                'count' => $count,
                'width' => $column['attrs']['width'] ?? null,
            ]);

            $pct_width = !empty($column['attrs']['width']) ? (int)$column['attrs']['width'] : $fallback_pct;

            $items = self::column($column);
            $items = self::resolve_nested_child($items, $gap);

            $cols .= "<td class=\"email-column\" width=\"{$pct_width}%\" valign=\"top\" style=\"width:{$pct_width}%; {$colStyle} vertical-align:top;\">
                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"width:100%;border:0;border-collapse:separate;\">
                    {$items}
                </table>
            </td>";
        }

        $margin = self::spacing_style($attrs, ['prop' => 'margin', 'type' => 'blockMargin']);
        return "<tr>
            <td style=\"$margin\">
                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"{$style}width:100%;border:0;border-collapse:collapse;\">
                    <tr>{$cols}</tr>
                </table>
            </td>
        </tr>";
    }

    private static function column(array $block): string
    {
        $style   = self::resolveStyles($block['attrs']);
        $content = '';

        foreach ($block['innerBlocks'] as $inner) {
            $content .= self::blocks([0 => $inner]);
        }

        return "<tr>
            <td style=\"{$style}width:100%;\" width=\"100%\">
                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"width:100%;border:0;border-collapse:collapse;\">
                    {$content}
                </table>
            </td>
        </tr>";
    }

    private static function group(array $block): string
    {
        $attrs  = $block['attrs'] ?? [];
        $style  = self::resolveStyles($attrs);
        $bgurl  = self::background_image($attrs)['url'] ?? '';
        $bgImg  = $bgurl ? " background=\"{$bgurl}\"" : '';
        $inner  = self::group_inner($block['innerBlocks'] ?? [], $attrs);
        $inlinStyle = !empty($style) ? " style=\"{$style}\"" : '';

        return "<tr>
            <td{$bgImg}{$inlinStyle}>" .
            ($bgurl ? "
                <!--[if gte mso 9]>
                <v:rect xmlns:v=\"urn:schemas-microsoft-com:vml\" fill=\"true\" stroke=\"false\" style=\"width:600px; v-text-anchor:top;\">
                    <v:fill type=\"tile\" src=\"{$bgurl}\" color=\"#ffffff\" />
                    <v:textbox inset=\"0,0,0,0\">
                <![endif]-->" : '') .
            "<table role=\"presentation\" style=\"width:100%;border:0;border-collapse:collapse;\">
                {$inner}
            </table>" .
            ($bgurl ? "
                <!--[if gte mso 9]>
                    </v:textbox>
                </v:rect>
                <![endif]-->" : '') .
            "</td>
        </tr>";
    }

    private static function group_inner($blocks, $attrs): string
    {
        $layout  = $attrs['layout'] ?? [];
        $type    = $layout['type'] ?? 'constrained';
        $setting = [
            'type'  => $type,
            'count' => 1,
        ];

        if ($type === 'grid') {
            $setting['count'] = min($layout['columnCount'] ?? 2, 2);
        }

        if ($type === 'flex') {
            $setting['wrap'] = $layout['flexWrap'] ?? 'wrap';
            if ($setting['wrap'] === 'nowrap') {
                $setting['count'] = min(count($blocks), 2);
            }
        }

        $gap   = self::resolve_block_gap($attrs['style']['spacing']['blockGap'] ?? '');
        $items = [];

        foreach ($blocks as $inner) {
            $spanAttrs   = $inner['attrs']['style']['layout'] ?? null;
            $colspan     = ($type === 'grid' && !empty($spanAttrs['columnSpan']))
                ? min((int)$spanAttrs['columnSpan'], $setting['count'])
                : 1;
            $colStyle    = self::nested_style($setting, $colspan);
            $item        = self::blocks([0 => $inner]);
            $item        = self::resolve_nested_child($item, $gap);
            $colspanAttr = $colspan > 1 ? "colspan=\"{$colspan}\"" : '';

            // FIXED: Explicitly spaced out variables to avoid attribute merging typos
            $items[] = [
                'html'    => "<td {$colspanAttr} class=\"email-column\" style=\"{$colStyle}\">
            <table role=\"presentation\" style=\"width:100%;border:0;border-collapse:collapse;\">
                {$item}
            </table>
        </td>",
                'colspan' => $colspan,
            ];
        }

        return self::render_nested_rows($items, $setting['count'], self::nested_style($setting, 1));
    }

    private static function render_nested_rows(array $items, int $cols, string $style): string
    {
        $rows       = [];
        $currentRow = [];
        $colsUsed   = 0;

        foreach ($items as $item) {
            $span = $item['colspan'];

            if ($colsUsed + $span > $cols) {
                while ($colsUsed < $cols) {
                    $currentRow[] = "<td class=\"email-column\" style=\"{$style}\"></td>";
                    $colsUsed++;
                }
                $rows[]     = '<tr>' . implode('', $currentRow) . '</tr>';
                $currentRow = [];
                $colsUsed   = 0;
            }

            $currentRow[] = $item['html'];
            $colsUsed    += $span;

            if ($colsUsed === $cols) {
                $rows[]     = '<tr>' . implode('', $currentRow) . '</tr>';
                $currentRow = [];
                $colsUsed   = 0;
            }
        }

        if (!empty($currentRow)) {
            while ($colsUsed < $cols) {
                $currentRow[] = "<td class=\"email-column\" style=\"{$style}\"></td>";
                $colsUsed++;
            }
            $rows[] = '<tr>' . implode('', $currentRow) . '</tr>';
        }

        return implode('', $rows);
    }

    private static function separator(array $block): string
    {
        $attrs = $block['attrs'] ?? [];
        $color = self::resolve_color($attrs['style']['color']['background'] ?? '#808080');
        $space = self::spacing_style($attrs, ['prop' => 'margin', 'type' => 'separator']);
        $class = $attrs['className'] ?? '';

        $separator = '';
        if (strpos($class, 'is-style-dots') !== false) {
            $separator = "<div style=\"text-align:center; line-height:24px; font-size:24px; color:{$color}; mso-line-height-rule:exactly;\">&middot;&nbsp;&nbsp;&middot;&nbsp;&nbsp;&middot;</div>";
        } else {
            $hasWideAlignmentClass = strpos($class, 'alignwide') !== false || strpos($class, 'alignfull') !== false;
            $isWide = strpos($class, 'is-style-wide') !== false || $hasWideAlignmentClass || in_array($attrs['align'] ?? '', ['wide', 'full'], true);

            if ($isWide) {
                $separator = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"width:100%;\">\n" .
                    "  <tr><td height=\"2\" bgcolor=\"{$color}\" style=\"font-size:2px; line-height:2px; height:2px; background-color:{$color};\">&nbsp;</td></tr>\n" .
                    "</table>";
            } else {
                $separator = "<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100\" style=\"width:100px; margin:0 auto;\">\n" .
                    "  <tr><td height=\"2\" bgcolor=\"{$color}\" style=\"font-size:2px; line-height:2px; height:2px; background-color:{$color};\">&nbsp;</td></tr>\n" .
                    "</table>";
            }
        }

        return "<tr>
            <td align=\"center\" style=\"width:100%; text-align:center; {$space}\">
                {$separator}
            </td>
        </tr>";
    }

    private static function spacer(array $block): string
    {
        $attrs = $block['attrs'] ?? [];
        $height = '24px';

        if (!empty($attrs['height'])) {
            $height = $attrs['height'];
        } elseif (preg_match('/height:\s*([^;"]+)/i', $block['innerHTML'], $m)) {
            $height = trim($m[1]);
        }

        $numeric_height = (int) filter_var($height, FILTER_SANITIZE_NUMBER_INT);
        if (!$numeric_height) {
            $numeric_height = 24; // Fallback default integer value
        }

        $style = self::spacing_style($attrs);
        $style .= "height:{$height}; font-size:{$height}; line-height:{$height}; mso-line-height-rule:exactly;";

        return "<tr>
            <td height=\"{$numeric_height}\" style=\"{$style}\" valign=\"top\">&nbsp;</td>
        </tr>";
    }

    private static function quote(array $block): string
    {
        $attrs  = $block['attrs'] ?? [];
        $style  = self::resolveStyles($attrs);
        $gap  = self::resolve_block_gap($attrs['style']['spacing']['blockGap'] ?? '');

        $content = '';
        foreach ($block['innerBlocks'] as $inner) {
            $items = self::blocks([0 => $inner]);
            $content .= self::resolve_nested_child($items, $gap);
        }

        $innerContent = $block['innerContent'] ?? [];
        $cite = end($innerContent);
        if (strpos($cite, '<cite>', 0) !== false) {
            if (preg_match('/<cite[^>]*>(.*?)<\/cite>/s', $cite, $matches)) {
                $cite = '<tr><td class=\"email-column\" style="font-size: 80%;">' . $matches[1] . '</td></tr>';
            }
        }

        return "<tr>
            <td style=\"{$style}width:100%;\">
                <table role=\"presentation\" style=\"width:100%;border:0;border-collapse:collapse;\">
                    {$content}
                    {$cite}
                </table>
            </td>
        </tr>";
    }

    private static function pull_quote(array $block): string
    {
        $attrs     = $block['attrs'] ?? [];
        $innerHTML =  '';
        $margin = self::spacing_style($attrs, ['prop' => 'margin']);
        $style   = self::resolveStyles($block['attrs']);
        $inlinStyle = !empty($style) ? " style=\"$style\"" : '';

        if (preg_match('/<blockquote[^>]*>(.*?)<\/blockquote>/s', $block['innerHTML'], $matches)) {
            $innerHTML = $matches[1];
        }

        if (!$innerHTML) {
            return '';
        }

        // Extract quote and cite from innerHTML
        $quote = '';
        $cite  = '';
        if (preg_match('/<p>(.*?)<\/p>/s', $innerHTML, $qm)) {
            $quote = wp_kses($qm[1], ['br' => [], 'strong' => [], 'em' => []]);
        }
        if (preg_match('/<cite>(.*?)<\/cite>/s', $innerHTML, $cm)) {
            $cite = wp_strip_all_tags($cm[1]);
        }

        $citeHtml = $cite ? "<tr>
            <td style=\"font-size:13px;opacity:0.8;padding-top:8px;\">
                &mdash; {$cite}
            </td>
        </tr>" : '';

        return "<tr>
            <td style=\"{$margin}\">
                <table role=\"presentation\" width=\"100%\" style=\"width:100%;border:0;\">
                    <tr>
                        <td{$inlinStyle}>
                            <table role=\"presentation\" width=\"100%\" style=\"width:100%;border:0;border-collapse:collapse;\">
                                <tr>
                                    <td>
                                        &ldquo;{$quote}&rdquo;
                                    </td>
                                </tr>
                                {$citeHtml}
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>";
    }

    private static function social_links(array $block): string
    {
        $attrs      = $block['attrs'] ?? [];
        $inner_blocks   = $block['innerBlocks'] ?? [];
        
        if (empty($inner_blocks)) {
            return '';
        }

        $bg         = $attrs['iconBackgroundColorValue'] ?? '';
        $color      = $attrs['iconColorValue'] ?? '';
        $align      = $attrs['layout']['justifyContent'] ?? 'center';
        $vertical   = ($attrs['layout']['orientation'] ?? 'horizontal') === 'vertical';
        $type       = $attrs['className'] ?? '';
        $size       = 16;
        $fontSize   = 11;

        if (!empty($attrs['size'])) {
            $sizes = ['small' => 12, 'normal' => 16, 'large' => 24, 'huge' => 32];
            foreach ($sizes as $key => $resize) {
                if ($attrs['size'] === $key) {
                    $size = $resize;
                    if ($resize === 24 || $resize === 32) {
                        $fontSize = 14;
                    }
                }
            }
        }

        $style   = self::resolveStyles($block['attrs'], ['base' => $fontSize]);
        $isLogosOnly = str_contains($type, 'is-style-logos-only');
        $isPillShape = str_contains($type, 'is-style-pill-shape');

        // Base style shared across all icons (not per-icon brand colors)
        $baseStyle = '';
        if (!empty($color)) {
            $baseStyle .= 'color:' . (self::resolve_color($color) ?: '') . ';';
        }
        if (!$isLogosOnly && !empty($bg)) {
            $baseStyle .= 'background-color:' . (self::resolve_color($bg) ?: '') . ';';
        }
        if ($isPillShape) {
            $baseStyle .= 'min-width:40px;';
        }

        $args = [
            'size' => $size,
            'logoOnly' => $isLogosOnly,
            'style' => $baseStyle,
            'color' => $attrs['iconColor'] ?? 'black',
            'bgVal' => $bg,
            'colorVal' => $color,
            'showLabel' => !empty($attrs['showLabels']),
            'vertical' =>  $vertical,
            'padding' => self::setPadding($align, $vertical)
        ];

        $icons = '';
        foreach ($inner_blocks as $link) {
            $icons .= self::social_link($link, $args);
        }

        $ulStyle = $vertical ? 'display:block;' : "display:flex;align-items:center;flex-wrap: wrap;justify-content:{$align};";
        if (!empty($attrs['background'])) {
            $ulStyle .= 'background-color:' . (self::resolve_color($attrs['background']) ?: '') . ';';
        }
        
        return "<tr>
            <td style=\"{$style}letter-spacing:1px;text-transform:uppercase;line-height:1;\">
                <ul style=\"margin:0;list-style:none;font-size:{$fontSize}px;{$ulStyle}\">
                    {$icons}
                </ul>
            </td>
        </tr>";
    }

    private static function social_link(array $block, array $args): string
    {
        $attrs = $block['attrs'] ?? [];
        $service   = $attrs['service'] ?? '';
        $url       = esc_url($attrs['url'] ?? '#');
        $item      = '';
        $size = $args['size'];
        $style = $args['style'];
        $display = 'display:' . ($args['vertical'] ? 'block' : 'inline-block') . ';';

        if (empty($args['colorVal']) && empty($args['bgVal'])) {
            $args['fix'] = true;
            $style .= self::brand_colors($service, $args['logoOnly']);
        }

        $iconUrl   = self::get_social_icon_url($service, $args);
        if (!empty($iconUrl)) {
            $style .= 'line-height:0;';
            $item  = "<img src=\"{$iconUrl}\" width=\"{$size}\" height=\"{$size}\" alt=\"{$service}\" style=\"display:inline-block;width:{$size}px;height:{$size}px;\">";
        }

        $labelStyle = ($args['showLabel'] && !empty($iconUrl)) ? ' style="display:inline-block;padding-left:4px;"' : '';
        $item .= !empty($attrs['label']) && ($args['showLabel'] || empty($iconUrl)) ? "<span{$labelStyle}>{$attrs['label']}</span>" : '';
        return "<li style=\"vertical-align:bottom;{$display}{$args['padding']}\">
                <a href=\"{$url}\" style=\"min-height:{$size}px;border-radius:20px;text-align:center;padding:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;{$style}\">
                    {$item}
                </a>
            </li>";
    }

    private static function get_social_icon_url(string $service, $args): string
    {
        $color = $args['color'] ?? 'black';

        $available = ['facebook', 'x', 'instagram', 'linkedin', 'youtube', 'pinterest', 'tiktok', 'twitch', 'whatsapp', 'discord', 'snapchat', 'threads', 'telegram', 'etsy', 'goodreads', 'medium', 'wordpress', 'twitter', 'vimeo', 'github',];

        if (!in_array($service, $available, true)) {
            return '';
        }

        // if ($color === 'black' && !empty($args['color']) && !in_array($service, ['x', 'threads','medium', 'github', 'tiktok'], true)) {
        //     $color = 'white';
        // }

        if (empty($args['logoOnly']) && !empty($args['fix']) && $service !== 'goodreads') {
            $color = 'white';
        }

        return CPS_BLOOM_MAILER_URL . "assets/socials/{$color}/{$service}.png";
    }

    private static function brand_colors(string $service, $inverse = false): string
    {
        $map = [
            'amazon'        => '#f90',
            'bandcamp'      => '#1ea0c3',
            'behance'       => '#0757fe',
            'bluesky'       => '#0a7aff',
            'codepen'       => '#1e1f26',
            'deviantart'    => '#02e49b',
            'discord'       => '#5865f2',
            'dribbble'      => '#e94c89',
            'dropbox'       => '#4280ff',
            'etsy'          => '#f45800',
            'facebook'      => '#0866ff',
            'fivehundredpx' => '#000000',
            'flickr'        => '#0461dd',
            'foursquare'    => '#e65678',
            'github'        => '#24292d',
            'goodreads'     => '#eceadd',
            'google'        => '#ea4434',
            'gravatar'      => '#1d4fc4',
            'instagram'     => '#f00075',
            'lastfm'        => '#e21b24',
            'linkedin'      => '#0d66c2',
            'mastodon'      => '#3288d4',
            'medium'        => '#000000',
            'meetup'        => '#f6405f',
            'patreon'       => '#000000',
            'pinterest'     => '#e60122',
            'pocket'        => '#ef4155',
            'reddit'        => '#ff4500',
            'skype'         => '#0478d7',
            'snapchat'      => '#fefc00',
            'soundcloud'    => '#ff5600',
            'spotify'       => '#1bd760',
            'telegram'      => '#2aabee',
            'threads'       => '#000000',
            'tiktok'        => '#000000',
            'tumblr'        => '#011835',
            'twitch'        => '#6440a4',
            'twitter'       => '#1da1f2',
            'vimeo'         => '#1eb7ea',
            'vk'            => '#4680c2',
            'whatsapp'      => '#25d366',
            'wordpress'     => '#3499cd',
            'x'             => '#000000',
            'yelp'          => '#d32422',
            'youtube'       => '#f00000',
        ];

        if (!$inverse) {
            $color = $service === 'goodreads' ? '#382110' : '#ffffff';
            $bg = $map[$service] ?? '#000000';
            return "background-color:{$bg};color:{$color};";
        }

        $color = $service === 'snapchat' ? '#000000' : $map[$service] ?? '#000000';
        return "color:{$color};";
    }

    private static function rss(array $block): string
    {
        $attrs         = $block['attrs'] ?? [];
        $feedURL       = $attrs['feedURL'] ?? '';

        if (empty($feedURL)) return '';

        $itemsToShow   = (int)($attrs['itemsToShow'] ?? 5);
        $excerptLength = (int)($attrs['excerptLength'] ?? 24);
        $style         = self::resolveStyles($attrs);
        $linkColor = self::resolve_color($attrs['style']['elements']['link']['color']['text'] ?? '') ?? 'inherit';

        // Fetch the feed
        $feed = fetch_feed($feedURL);
        if (is_wp_error($feed)) return '';

        $items   = $feed->get_items(0, $itemsToShow);
        $content = '';

        foreach ($items as $item) {
            $title   = esc_html($item->get_title());
            $link    = esc_url($item->get_permalink());
            $date    = !empty($attrs['displayDate']) ? $item->get_date('F j, Y') : '';
            $author  = !empty($attrs['displayAuthor']) ? $item->get_author() : '';
            $authorName = $author ? esc_html($author->get_name()) : '';
            $excerpt  = $meta = '';

            if ($date || $authorName) {
                $spacer =  $date && $authorName ? '&nbsp;&middot;&nbsp;' : '';
                $meta =  "<span style=\"display:inline-block;width:100%;padding-bottom:5px;font-size:11px;\">{$date}{$spacer}{$authorName}</span>";
            }

            if (!empty($attrs['displayExcerpt'])) {
                $excerpt = wp_trim_words(wp_strip_all_tags($item->get_description()), $excerptLength);
                $excerpt = $excerpt ? "<span style=\"display:inline-block;width:100%;font-size:14px;\">{$excerpt}</span>" : '';
            }

            $content .= "<span style=\"display:inline-block;width:100%;padding:0 0 16px 0;\">
                <a href=\"{$link}\" style=\"font-size:16px;font-weight:bold;color:{$linkColor};text-decoration:none;\">{$title}</a>
                {$meta}
                {$excerpt}
            </span>";
        }

        return "<tr>
            <td style=\"{$style}\">
                    {$content}
            </td>
        </tr>";
    }

    private static function post(array $block): string
    {
        $attrs        = $block['attrs'] ?? [];
        $ids          = $attrs['ids'] ?? [];
        $count        = $attrs['count'] ?? 2;
        $cols         = max(1, (int) ($attrs['columns'] ?? 2));
        $order_by     = $attrs['orderBy'] ?? 'newest';
        $show_image   = $attrs['showImage'] ?? true;
        $show_excerpt = $attrs['showExcerpt'] ?? false;
        $show_button  = $attrs['showButton'] ?? true;
        $button_text  = $attrs['buttonText'] ?? 'Read More';
        $bg_color     = $attrs['bgColor'] ?? '#ffffff';
        $text_color   = $attrs['textColor'] ?? '#333333';
        $padding      = self::spacing_style(['spacing' => $attrs['padding']], ['type' => 'block']);

        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'no_found_rows'  => true,
        ];

        if (! empty($ids)) {
            $args['post__in']       = $ids;
            $args['orderby']        = 'post__in';
            $args['posts_per_page'] = count($ids);
        } else {
            $args['orderby'] = $order_by === 'oldest' ? 'date' : ($order_by === 'newest' ? 'date' : $order_by);
            $args['order']   = $order_by === 'oldest' ? 'ASC' : 'DESC';

            if (! empty($attrs['categories'])) {
                $args['category__in'] = array_map('absint', (array) $attrs['categories']);
            }
        }

        $query = new WP_Query($args);

        if (! $query->have_posts()) {
            return '';
        }

        $col_width = round(100 / $cols) . '%';
        $cards     = [];

        foreach ($query->posts as $post) {
            $title     = get_the_title($post);
            $permalink = get_permalink($post);
            $excerpt   = $show_excerpt ? wp_trim_words(get_the_excerpt($post), 20) : '';
            $thumb_url = $show_image ? get_the_post_thumbnail_url($post, 'medium') : '';

            $inner = '';

            if ($show_image && $thumb_url) {
                $inner .= '<a href="' . esc_url($permalink) . '" style="display:block;margin-bottom:10px;">';
                $inner .= '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($title) . '" style="width:100%;display:block;border:0;max-width:100%;" />';
                $inner .= '</a>';
            }

            $inner .= '<h3 style="margin:0 0 8px;font-size:15px;line-height:1.4;color:' . esc_attr($text_color) . ';">';
            $inner .= '<a href="' . esc_url($permalink) . '" style="color:' . esc_attr($text_color) . ';text-decoration:none;">' . esc_html($title) . '</a>';
            $inner .= '</h3>';

            if ($show_excerpt && $excerpt) {
                $inner .= '<p style="margin:0 0 10px;font-size:14px;line-height:1.6;">' . esc_html($excerpt) . '</p>';
            }

            if ($show_button) {
                $inner .= '<a href="' . esc_url($permalink) . '" style="display:inline-block;padding:6px 14px;background-color:' . esc_attr($text_color) . ';color:' . esc_attr($bg_color) . ';text-decoration:none;font-size:13px;">' . esc_html($button_text) . '</a>';
            }

            // One <td> per post — render_card_rows handles the chunking into rows
            $cards[] = "<td class=\"email-column\" style=\"padding:8px;text-align:center;width:{$col_width};vertical-align:top;\">{$inner}</td>";
        }

        wp_reset_postdata();

        return self::render_card_rows($cards, $cols, $col_width, $padding, $bg_color);
    }

    private static function product(array $block): string
    {
        $attrs        = $block['attrs'] ?? [];
        $ids          = $attrs['ids'] ?? [];
        $count        = $attrs['count'] ?? 2;
        $cols         = max(1, (int) ($attrs['columns'] ?? 2));
        $order_by     = $attrs['orderBy'] ?? 'date';
        $order        = strtoupper($attrs['order'] ?? 'ASC');
        $sale_only    = $attrs['saleOnly'] ?? false;
        $show_image   = $attrs['showImage'] ?? true;
        $show_button  = $attrs['showButton'] ?? true;
        $button_text  = $attrs['buttonText'] ?? 'Shop Now';
        $bg_color     = $attrs['bgColor'] ?? '#ffffff';
        $text_color   = $attrs['textColor'] ?? '#333333';
        $padding      = self::spacing_style(['spacing' => $attrs['padding']], ['type' => 'block']);

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'no_found_rows'  => true,
            'orderby'        => $order_by,
            'order'          => $order,
        ];

        if (! empty($ids)) {
            $args['post__in']       = $ids;
            $args['orderby']        = 'post__in';
            $args['posts_per_page'] = count($ids);
        } else {
            if (! empty($attrs['categories'])) {
                $args['tax_query'] = [[
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => array_map('absint', (array) $attrs['categories']),
                ]];
            }

            if ($sale_only) {
                $sale_ids = array_merge([0], wc_get_product_ids_on_sale());
                // If categories are also set, intersect so both conditions apply
                if (! empty($args['post__in'])) {
                    $args['post__in'] = array_intersect($args['post__in'], $sale_ids);
                } else {
                    $args['post__in'] = $sale_ids;
                }
            }
        }

        $query = new WP_Query($args);

        if (! $query->have_posts()) {
            return '';
        }

        $col_width = round(100 / $cols) . '%';
        $cards     = [];

        foreach ($query->posts as $post) {
            $product   = wc_get_product($post->ID);
            $title     = $product->get_name();
            $permalink = get_permalink($post->ID);
            $thumb_url = $show_image ? get_the_post_thumbnail_url($post->ID, 'medium') : '';
            $inner = '';

            if ($show_image && $thumb_url) {
                $inner .= '<a href="' . esc_url($permalink) . '" style="display:block;margin-bottom:10px;">';
                $inner .= '<table style="width:100%;border:0;border-collapse:collapse;position:relative;">';
                $inner .= '<tr><td style="position:relative;padding:0;">';
                $inner .= '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($title) . '" style="width:100%;display:block;border:0;max-width:100%;" />';

                if ($product->is_on_sale()) {
                    $inner .= '<div style="position:absolute;top:8px;left:8px;background:#000;color:#ffffff;font-size:12px;padding: 10px;letter-spacing: 1px;line-height: 1;">SALE</div>';
                }
                $inner .= '</td></tr>';
                $inner .= '</table>';
                $inner .= '</a>';
            }

            $inner .= '<h3 style="margin:0 0 4px;font-weight:600;font-size:15px;color:' . esc_attr($text_color) . ';">';
            $inner .= '<a href="' . esc_url($permalink) . '" style="color:' . esc_attr($text_color) . ';text-decoration:none;">' . esc_html($title) . '</a>';
            $inner .= '</h3>';

            if ($product->is_on_sale()) {
                $regular = esc_html(wp_strip_all_tags(wc_price($product->get_regular_price())));
                $sale    = esc_html(wp_strip_all_tags(wc_price($product->get_sale_price())));
                $price_html = '<span style="text-decoration:line-through;opacity:0.6;margin-right:6px;">' . $regular . '</span>'
                    . '<span style="font-weight:bold;">' . $sale . '</span>';
            } else {
                $price_html = '<span>' . esc_html(wp_strip_all_tags($product->get_price_html())) . '</span>';
            }

            if ($price_html) {
                $inner .= '<p style="margin:0 0 10px;font-size:14px;color:' . esc_attr($text_color) . ';">' . $price_html . '</p>';
            }

            $rating       = (float) $product->get_average_rating();
            $review_count = (int) $product->get_review_count();
            if ($rating > 0) {
                $inner .= '<p style="margin:0 0 6px;font-size:13px;color:#f5a623;letter-spacing:2px;">';
                $inner .= '<span>' . esc_html(self::render_stars($rating)) . '</span>';
                if ($review_count > 0) {
                    $inner .= '<span style="color:' . esc_attr($text_color) . ';font-size:12px;letter-spacing:0;margin-left:4px;opacity:0.7;">(' . $review_count . ')</span>';
                }
                $inner .= '</p>';
            }

            if ($show_button) {
                $inner .= '<a href="' . esc_url($permalink) . '" style="display:inline-block;padding:6px 14px;background-color:' . esc_attr($text_color) . ';color:' . esc_attr($bg_color) . ';text-decoration:none;font-size:13px;">' . esc_html($button_text) . '</a>';
            }

            $cards[] = "<td class=\"email-column\" style=\"padding:8px;text-align:center;width:{$col_width};vertical-align:top;\">{$inner}</td>";
        }

        wp_reset_postdata();

        return self::render_card_rows($cards, $cols, $col_width, $padding, $bg_color);
    }

    private static function table(array $block): string
    {
        $attrs = $block['attrs'];
        $html = wp_kses_post($block['innerHTML']);
        $style = self::resolveStyles($attrs);

        $cellStyles = "padding: 6px 10px;";
        $cellStyles .= self::border_style($attrs);
        $tableContent = '';
        if (preg_match('/<table[^>]*>(.*?)<\/table>/s', $html, $matches)) {
            $tableContent = $matches[1];
            $isStripes    = !empty($attrs['className']) && strpos($attrs['className'], 'is-style-stripes') !== false;

            // Style th cells
            $tableContent = preg_replace('/<th([^>]*)>/', '<th$1 style="' . $cellStyles . 'font-weight:bold;">', $tableContent);

            // Style tr rows with stripe detection
            $rowIndex     = 0;
            $tableContent = preg_replace_callback('/<tr([^>]*)>(.*?)<\/tr>/s', function ($m) use ($cellStyles, $isStripes, &$rowIndex) {
                $rowIndex++;
                $rowStyle = '';

                if ($isStripes && $rowIndex % 2 === 0) {
                    $rowStyle = ' style="background-color:#f5f5f5;"';
                }

                // Style td cells inside this row
                $rowContent = preg_replace_callback('/<td([^>]*)>/s', function ($tdm) use ($cellStyles) {
                    $tdAttrs = $tdm[1];

                    // Extract data-align and convert to align
                    $align = '';
                    if (preg_match('/data-align="([^"]*)"/i', $tdAttrs, $am)) {
                        $align   = $am[1];
                        $tdAttrs = preg_replace('/\s*data-align="[^"]*"/i', '', $tdAttrs);
                    }

                    // Strip class
                    $tdAttrs = preg_replace('/\s*class="[^"]*"/i', '', $tdAttrs);

                    // Merge existing style with cellStyles
                    if (preg_match('/style="([^"]*)"/i', $tdAttrs, $sm)) {
                        $merged  = rtrim($sm[1], ';') . ';' . $cellStyles;
                        $tdAttrs = preg_replace('/style="[^"]*"/i', 'style="' . $merged . '"', $tdAttrs);
                    } else {
                        $tdAttrs .= ' style="' . $cellStyles . '"';
                    }

                    $alignAttr = $align ? " align=\"{$align}\"" : '';

                    return "<td{$tdAttrs}{$alignAttr}>";
                }, $m[2]);

                return "<tr{$m[1]}{$rowStyle}>{$rowContent}</tr>";
            }, $tableContent);
        } else {
            return '';
        }

        return "<tr>
            <td>
            <table style=\"width:100%; border-collapse: collapse;$style\">
                {$tableContent}
            </table>
            </td>
        </tr>";
    }

    private static function generic(array $block): string
    {
        $attrs = $block['attrs'] ?? [];
        $content = trim($block['innerHTML']);
        if (empty($content)) {
            $content = render_block($block);
            if (empty($content)) {
                return '';
            }
        }

        $style = self::resolveStyles($attrs);
        $rendered = strip_tags($content, '<a><p><h1><h2><h3><h4><img><strong><em><br><span>');

        return "<tr>
            <td style=\"padding:5px;{$style}\">
                {$rendered}
            </td>
        </tr>";
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    /**
     * Shared helper: chunks card <td>s into <tr> rows and wraps in the outer structure.
     *
     * @param string[] $cards
     * @param int      $cols
     * @param int      $col_width
     * @param string   $padding
     * @return string
     */
    private static function render_card_rows(array $cards, int $cols, string $col_width, string $padding, string $bg_color): string
    {
        $rows = [];
        for ($i = 0; $i < count($cards); $i += $cols) {
            $row = array_slice($cards, $i, $cols);
            while (count($row) < $cols) {
                $row[] = "<td class=\"email-column\" style=\"width:{$col_width}\"></td>";
            }
            $rows[] = '<tr>' . implode('', $row) . '</tr>';
        }

        $rows_html = implode('', $rows);

        return "<tr>
			<td style=\"padding:{$padding};background:{$bg_color};\">
				<table role=\"presentation\" style=\"width:100%;border:0;border-collapse:collapse;\">
					{$rows_html}
				</table>
			</td>
		</tr>";
    }

    private static function resolveStyles(array $attrs, $args = null)
    {
        $style = '';
        $attrStyle = $attrs['style'] ?? [];

        $style .= self::spacing_style($attrs, $args);
        $style .= self::color_style($attrs, $args['type'] ?? null);

        if (!empty($attrStyle['border'])) {
            $style .= self::border_style($attrs);
        }

        if (!empty($attrStyle['typography'])) {
            $style .= self::typography_style($attrs);
        }

        if (!empty($attrStyle['background']['backgroundImage'])) {
            $style .= self::background_image($attrs)['css'];
        }

        if (!empty($attrs['layout']['justifyContent'])) {
            $style .= self::getAlign($attrs['layout']['justifyContent']);
        }

        return $style;
    }

    private static function nested_style(array $setting, int $colspan = 1): string
    {
        $width = '100%';

        if (!empty($setting['count']) && $setting['count'] > 1) {
            if (!empty($setting['width'])) {
                $width = $setting['width'];
            } else {
                $colWidth = round(100 / $setting['count'], 4);
                $width    = round($colWidth * $colspan, 4) . '%';
            }
        }

        return "width:{$width};vertical-align:top;";
    }

    private static function resolve_nested_child(string $child, string $gap): string
    {
        if (empty($gap)) {
            return $child;
        }

        return "<tr>
            <td style=\"padding:{$gap};\">
                <table role=\"presentation\" style=\"width:100%;border:0;\">
                    {$child}
                </table>
            </td>
        </tr>";
    }

    private static function getAlign(string $toMap, $default = 'left')
    {
        $alignMap = [
            'left'          => 'left',
            'center'        => 'center',
            'right'         => 'right',
            'space-between' => 'center',
            'space-around'  => 'center',
            'space-evenly'  => 'center',
        ];

        $align = strtolower(trim($alignMap[$toMap]));
        if ($align === '') {
            $align = strtolower($default);
        }

        if ($align === 'start') {
            $align = 'left';
        } else if ($align === 'end') {
            $align = 'right';
        }

        if (!in_array($align, ['left', 'center', 'right', 'justify'], true)) {
            $align = strtolower($default);
        }

        if (Helpers::is_rtl()) {
            if ($align === 'left') {
                return 'right';
            }

            if ($align === 'right') {
                return 'left';
            }
        }

        return "text-align:$align;";
    }

    private static function setPadding(string $align, $verical = false, $default = 6, $value = 20)
    {
        switch ($align) {
            case 'space-between':
            case 'space-around':
            case 'space-evenly':
                return 'padding:' . ($verical ? ($value ?? ($default ?: 6)) . 'px ' : '') . ($value ?? ($default ?: 6)) . 'px;';
            default:
                return 'padding:' . ($verical ? ($default ?: 6) . 'px ' : '') .  ($default ?: 6) . 'px;';
        }
    }

    private static function resolve_block_gap(mixed $gaps): string
    {
        if (empty($gaps)) return '';

        if (is_array($gaps)) {
            $vertical   = self::resolve_spacing_value($gaps['top'] ?? '');
            $horizontal = self::resolve_spacing_value($gaps['left'] ?? '');
        } else {
            $vertical   = self::resolve_spacing_value($gaps);
            $horizontal = '';
        }

        $gap = [];
        foreach (['vertical' => $vertical, 'horizontal' => $horizontal] as $val) {
            if (empty($val)) continue;

            preg_match('/^(\d+\.?\d*)(px|em|rem|vh|vw|%)$/', $val, $m);
            if (empty($m)) continue;

            $inPx = in_array($m[2], ['em', 'rem']) ? (float)$m[1] * 16 : (float)$m[1];
            if ($inPx <= 0) continue;

            $gap[] = ($inPx / 2) . 'px';
        }

        return implode(' ', $gap);
    }

    private static function spacing_style(array $attrs, $args = []): string
    {
        $spacing = [];
        if (isset($args['prop']) && in_array($args['prop'], ['padding', 'margin'])) {
            $spacing = $attrs['style']['spacing'][$args['prop']] ?? [];
        } else if (isset($attrs['spacing'])) {
            $spacing = $attrs['spacing'] ?? [];
        } else if (empty($spacing) && !empty($args['type'])) {
            $spacing = self::default_spacing($args['type'] ?? 'block');
        } else {
            return '';
        }

        $css = '';

        foreach ($spacing as $side => $value) {
            $resolved = self::resolve_spacing_value($value);
            if (!$resolved) {
                continue;
            }

            preg_match('/^(\d+\.?\d*)(px|em|rem|vh|vw|%)$/', $resolved, $m);
            if (empty($m)) {
                continue;
            }

            $valueNum = (float) $m[1];
            $unit     = $m[2];

            $base = $args['base'] ?? 16;

            $px = in_array($unit, ['em', 'rem', '%'])
                ? $valueNum * $base
                : $valueNum;

            $px = round($px);

            $css .= 'padding-' . $side . ': ' . $px . "px;";
        }

        return $css;
    }

    private static function color_style(array $attrs, $type = false): string
    {
        $css   = '';
        $style = $attrs['style'] ?? [];
        $color = $style['color'] ?? [];

        if (! empty($color['text'])) {
            $css .= 'color:' . self::resolve_color($color['text']) . ';';
        } elseif ($type === 'button') {
            $css .= 'color:#ffffff;';
        }

        if (! empty($color['background'])) {
            $css .= 'background-color:' . self::resolve_color($color['background']) . ';';
        } elseif ($type === 'button') {
            $css .= 'background-color:#333333;';
        }

        // Named preset colors
        if (! empty($attrs['textColor'])) {
            $css .= 'color:' . self::resolve_preset_color($attrs['textColor']) . ';';
        }
        if (! empty($attrs['backgroundColor'])) {
            $css .= 'background-color:' . self::resolve_preset_color($attrs['backgroundColor']) . ';';
        }
        return $css;
    }

    private static function border_style(array $attrs): string
    {
        $css   = '';
        $style = $attrs['style'] ?? [];
        $border = $style['border'] ?? [];
        $rad = $border['radius'] ?? null;

        if (! empty($rad)) {
            if (!is_array($rad)) {
                $css .= 'border-radius:' . (is_numeric($rad) ? "{$rad}px" : $rad) . ';';
            } else {
                foreach ($rad as $key => $radius) {
                    $css .=  'border-' . self::cameloKebab($key) . '-radius:' . (is_numeric($radius) ? "{$radius}px" : $radius) . ';';
                }
            }
        }

        if (!empty($border['width'])) {
            $color = '';
            if (!empty($border['color'])) {
                $color = ' ' . self::resolve_color($border['color']);
            } else if (!empty($attrs['borderColor'])) {
                $color = ' ' . self::resolve_color($attrs['borderColor']);
            }

            $css .= "border: {$border['width']} " . ($border['style'] ?? 'solid') . $color . ';';
        }

        return $css;
    }

    private static function background_image(array $attrs): array
    {
        $bg = $attrs['style']['background']['backgroundImage'] ?? [];
        $url = $bg['url'] ?? '';
        if (empty($url)) {
            return [];
        }

        $repeat = $bg['backgroundRepeat'] ?? 'no-repeat';
        $size = $bg['backgroundSize'] ?? 'cover';
        $position = $bg['backgroundPosition'] ?? 'center';
        $attachment = $bg['backgroundAttachment'] ?? 'fixed';

        return [
            'css' => "background-image: url({$url});background-size: {$size};background-position: {$position};background-repeat: {$repeat};color: white;",
            'url' => $url
        ];
    }

    private static function typography_style(array $attrs): string
    {
        $css   = '';
        $style = $attrs['style'] ?? [];
        $typo  = $style['typography'] ?? [];
        $textAlign = $attrs['textAlign'] ?? $typo['textAlign'] ?? '';

        if ($textAlign) $css .= self::getAlign($textAlign);
        if (! empty($typo['lineHeight']))   $css .= "line-height:{$typo['lineHeight']};";

        $fontStyle = $attrs['fontStyle'] ?? $typo['fontStyle'] ?? '';
        if (! empty($fontStyle)) {
            $fontStyle = self::resolve_font_style($fontStyle);
            $css .= "font-style:{$fontStyle};";
        }

        $fontWeight = $attrs['fontWeight'] ?? $typo['fontWeight'] ?? '';
        if (! empty($fontWeight)) {
            $fontWeight = self::resolve_font_weight($fontWeight);
            $css .= "font-weight:{$fontWeight};";
        }

        if (! empty($typo['textDecoration']))   $css .= "text-decoration:{$typo['textDecoration']};";
        if (! empty($typo['textTransform']))   $css .= "text-transform:{$typo['textTransform']};";
        if (! empty($typo['letterSpacing'])) $css .= "letter-spacing:{$typo['letterSpacing']};";

        $fontSize = $attrs['fontSize'] ?? $typo['fontSize'] ?? '';
        if (! empty($fontSize)) {
            $css .= 'font-size:' . self::resolve_preset_font_size($fontSize) . ';';
        } elseif (!empty($attrs['level'])) {
            $sizes   = [1 => '36px', 2 => '28px', 3 => '22px', 4 => '18px', 5 => '16px', 6 => '14px'];
            $css .= 'font-size:' .  $sizes[$attrs['level'] ?? 2];
        } else {
            $css .= 'font-size:16px';
        }

        if (! empty($typo['fontFamily'])) {
            $fontFamily = self::resolve_font_family($typo['fontFamily']);
            $css .= "font-family:{$fontFamily};";
        }

        return $css;
    }

    // Converts "var:preset|spacing|30" → actual pixel/rem value
    private static function resolve_spacing_value(string $value): string
    {
        if (str_starts_with($value, 'var:preset|spacing|')) {
            $slug  = str_replace('var:preset|spacing|', '', $value);
            $settings = wp_get_global_settings(['spacing']);
            $sizes = $settings['spacingSizes']['theme'] ?? $settings['spacingSizes']['default'] ?: [];

            foreach ($sizes as $size) {
                if ($size['slug'] === $slug) return $size['size'];
            }
        }
        return $value;
    }

    // Resolves preset slug → hex or returns the value if already a hex/rgb
    private static function resolve_preset_color(string $slug): string
    {
        $settings = wp_get_global_settings(['color']);
        $palette = array_merge($settings['palette']['theme'] ?? [], $settings['palette']['default'] ?: []);

        foreach ($palette as $color) {
            if ($color['slug'] === $slug) {
                if (preg_match('/var\([^,]+,\s*([^)]+)\)/', $color['color'], $m)) {
                    return trim($m[1]);
                }
                return $color['color'];
            }
        }

        return $slug; // already a hex/rgb value
    }

    private static function resolve_color(string $value): string
    {
        if (strpos($value, 'rgb') !== false || strpos($value, '#') !== false) {
            return $value;
        }

        // CSS var with fallback value — extract the fallback
        if (preg_match('/var\([^,]+,\s*([^)]+)\)/', $value, $m)) {
            return trim($m[1]);
        }

        if (str_starts_with($value, 'var:preset|color|')) {
            $slug = str_replace('var:preset|color|', '', $value);
            return self::resolve_preset_color($slug);
        }

        return self::resolve_preset_color($value);
    }

    private static function resolve_preset_font_size(string $slug): string
    {
        $settings = wp_get_global_settings(['typography']);
        $sizes = $settings['fontSizes']['theme']
            ?? $settings['fontSizes']['default']
            ?: [];
        foreach ($sizes as $size) {
            if ($size['slug'] === $slug) return $size['size'];
        }
        return '16px';
    }

    private static function resolve_font_weight(mixed $value): string
    {
        if (empty($value)) return '';

        // Extract slug from either var format
        $slug = null;
        if (str_starts_with($value, 'var:preset|font-weight|')) {
            $slug = str_replace('var:preset|font-weight|', '', $value);
        } elseif (preg_match('/var\(--wp--preset--font-weight--([a-z0-9-]+)\)/i', $value, $m)) {
            $slug = $m[1];
        }

        if ($slug !== null) {
            // Look up from theme.json registered presets
            $presets = wp_get_global_settings(['typography', 'fontWeight']);
            if (!empty($presets)) {
                foreach ($presets as $preset) {
                    if (($preset['slug'] ?? '') === $slug) {
                        return (string)$preset['fontWeight'];
                    }
                }
            }

            // Fallback to name map if not found in theme data
            $slug  = strtolower(str_replace(['-', '_', ' '], '', $slug));
            $value = $slug;
        }

        $map = [
            'thin'       => '100',
            'extralight' => '200',
            'light'      => '300',
            'normal'     => '400',
            'regular'    => '400',
            'medium'     => '500',
            'semibold'   => '600',
            'bold'       => '700',
            'extrabold'  => '800',
            'black'      => '900',
        ];

        if (is_numeric($value)) return (string)(int)$value;

        $normalized = strtolower(str_replace(['-', '_', ' '], '', $value));

        return $map[$normalized] ?? '';
    }

    private static function resolve_font_style(mixed $value): string
    {
        if (empty($value)) return '';

        // Extract slug from var formats
        $slug = null;

        if (str_starts_with($value, 'var:preset|font-style|')) {
            $slug = str_replace('var:preset|font-style|', '', $value);
        } elseif (preg_match('/var\(--wp--preset--font-style--([a-z0-9-]+)\)/i', $value, $m)) {
            $slug = $m[1];
        }

        if ($slug !== null) {
            $presets = wp_get_global_settings(['typography', 'fontStyle']);
            if (!empty($presets)) {
                foreach ($presets as $preset) {
                    if (($preset['slug'] ?? '') === $slug) {
                        return (string)$preset['fontStyle'];
                    }
                }
            }

            $value = $slug;
        }

        $map = [
            'normal'  => 'normal',
            'italic'  => 'italic',
            'oblique' => 'oblique',
        ];

        $normalized = strtolower(trim($value));

        return $map[$normalized] ?? '';
    }

    /**
     * Get font family from WordPress font family slug or return as-is
     */
    private static function resolve_font_family($fontFamily)
    {
        if (strpos($fontFamily, ',') !== false) {
            return $fontFamily;
        }

        if (str_starts_with($fontFamily, 'var:preset|font-family|')) {
            $fontFamily = str_replace('var:preset|font-family|', '', $fontFamily);
        } elseif (preg_match('/var\(--wp--preset--font-family--([a-z0-9-]+)\)/i', $fontFamily, $m)) {
            $fontFamily = $m[1];
        }

        static $flatMap = null;
        if ($flatMap === null) {
            $built = Helpers::build_font_family_map();
            $theme_stacks = array_map(fn($f) => $f['stack'], $built['theme']);
            // Theme-registered fonts win on a slug collision — same precedence as your original merge
            $flatMap = array_merge($built['system'], $theme_stacks);
        }

        return $flatMap[$fontFamily] ?? $fontFamily;
    }

    // Extracts innerHTML of a given tag
    private static function inner_html(string $html, string $tag): string
    {
        $pattern = '/<' . $tag . '[^>]*>(.*?)<\/' . $tag . '>/si';
        preg_match($pattern, $html, $matches);
        return $matches[1] ?? wp_strip_all_tags($html);
    }

    /**
     * Returns default spacing for a given section type.
     *
     * @param string $type  block | header | footer | button | space
     * @return array{ top: int, right: int, bottom: int, left: int}
     */
    private static function default_spacing(string $type = 'block'): array
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
     * Returns a star string for a numeric rating (0–5).
     *
     * @param float $rating
     * @return string
     */
    // private static function render_stars(float $rating): string
    // {
    //     $full  = (int) floor($rating);
    //     $half  = ($rating - $full) >= 0.5 ? 1 : 0;
    //     $empty = 5 - $full - $half;

    //     return str_repeat('★', $full)
    //         . ($half ? '⯨' : '')   // or just '★' with reduced opacity via a span
    //         . str_repeat('☆', $empty);
    // }

    private static function render_stars(float $rating): string
    {
        $full  = (int) floor($rating);
        $half  = ($rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;

        $html  = str_repeat('<span style="color:#f5a623;">★</span>', $full);
        $html .= $half ? '<span style="color:#f5a623;opacity:0.5;">★</span>' : '';
        $html .= str_repeat('<span style="color:#cccccc;">★</span>', $empty);

        return $html;
    }

    private static function cameloKebab(string $string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }

    /**
     * Manual block parser (fallback if parse_blocks not available)
     */
    private static function manual($content)
    {
        $blocks = [];
        $pattern = '/<!--\s+wp:([a-z][a-z0-9_-]*\/)?([a-z][a-z0-9_-]*)\s+(\{.*?\})?\s+(\/)?-->/';

        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        $lastOffset = 0;
        foreach ($matches[0] as $index => $match) {
            $blockName = ($matches[1][$index][0] ?? '') . $matches[2][$index][0];
            $attrs = $matches[3][$index][0] ?? '{}';
            $isSelfClosing = !empty($matches[4][$index][0]);

            $blockStart = $match[1] + strlen($match[0]);

            // Find closing tag if not self-closing
            if (!$isSelfClosing) {
                $closingPattern = '/<!--\s+\/wp:' . preg_quote($blockName, '/') . '\s+-->/';
                if (preg_match($closingPattern, $content, $closeMatch, PREG_OFFSET_CAPTURE, $blockStart)) {
                    $innerHTML = substr($content, $blockStart, $closeMatch[0][1] - $blockStart);
                    $lastOffset = $closeMatch[0][1] + strlen($closeMatch[0][0]);
                } else {
                    $innerHTML = '';
                }
            } else {
                $innerHTML = '';
            }

            $blocks[] = [
                'blockName'   => $blockName,
                'attrs'       => json_decode($attrs, true) ?? [],
                'innerHTML'   => trim($innerHTML),
                'innerBlocks' => []
            ];
        }

        return $blocks;
    }

    /**
     * Generates a complete HTML email string.
     *
     * @param string  $subject  Email subject (used in <title>).
     * @param array   $header   Decoded header data.
     * @param string   $block_markup gutenberg string.
     * @param array   $footer   Decoded footer data.
     * @param array   $design   Decoded design settings.
     * @return string
     */
    public static function generate($subject, $header, $block_markup, $footer, $design)
    {
        $blocks = self::parse($block_markup);
        $header_html    = self::header($header, $design);
        $footer_html    = self::footer($footer, $design);
        $blocks_html    = self::blocks($blocks);
        $body_bg        = $design['bodyBg']     ?? '#f4f4f4';
        $container_bg   = $design['containerBg'] ?? '#ffffff';
        $container_w    = (int) ($design['containerWidth'] ?? 600);
        $border_radius  = (int) ($design['borderRadius']  ?? 8);
        $padding        = self::spacing_style(['spacing' => $design['padding'] ?? self::default_spacing('content')]);
        $text_color     = $design['textColor']  ?? '#333333';
        $font_size      = (int) ($design['fontSize'] ?? 14);
        $font_family    = self::resolve_font_family($design['fontFamily'] ?? 'system');
        $line_height    = $design['lineHeight'] ?? 1.6;

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>{$subject}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings>
        <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
	<style id="cps-styles-inline-css">
        ul,ol{margin: 0 0 0.5em 1.5em;padding: 0;list-style-type: disc;}
        img.wp-smiley, img.emoji { display: inline !important;border: none !important;box-shadow: none !important; height: 1em !important;width: 1em !important;margin: 0 0.07em !important;vertical-align: -0.1em !important;background: none !important;padding: 0 !important;}
        /* Mobile stacking for columns */
        @media only screen and (max-width: 600px) {
            .email-column { display: block !important; width: 100% !important; }
        }
    </style>
</head>
<body style="margin: 20px; padding:0; background-color: {$body_bg};">
    <table class="econtainer" style="width: 100%; border: 0; border-collapse: separate; background: {$container_bg}; max-width: {$container_w}px; border-radius: {$border_radius}px; margin:auto;overflow:hidden;">
       {$header_html}
        <tr>
            <td class="einner" style="padding: {$padding}; color: {$text_color}; font-size: {$font_size}px; font-family: {$font_family}; line-height: {$line_height};">
                <table style="width: 100%; border: 0; border-collapse: collapse;">
                    {$blocks_html}
                </table>
            </td>
        </tr>
        {$footer_html}
    </table>
</body>
</html>
HTML;
    }

    /**
     * Convenience method — generates HTML directly from a campaign DB row.
     *
     * @param array|object $campaign  Row from wpdb (array or stdClass).
     * @return string
     */
    public static function from_campaign($campaign): string
    {
        $c       = (array) $campaign;
        $subject = $c['subject'] ?? '';
        $design  = json_decode($c['design'] ?? '{}', true) ?: [];
        $blocks  = json_decode($c['blocks'] ?? '', true) ?: '';
        $header  = json_decode($c['header'] ?? '{}', true) ?: [];
        $footer  = json_decode($c['footer'] ?? '{}', true) ?: [];

        return self::generate($subject, $header, $blocks, $footer, $design);
    }
}
