<?php

namespace ChicpixiesBloomMailer;

use WP_Query;

if (! defined('ABSPATH')) exit;

/**
 * Class Builder
 *
 * Converts campaign block data into a full HTML email string.
 *
 * Usage:
 *   // From a DB row (array or object):
 *   $html = CPS_Email_Generator::from_campaign( $campaign_row );
 *
 *   // Or directly:
 *   $html = CPS_Email_Generator::generate( $subject, $header, $blocks, $footer, $design );
 */
class Builder
{
	// -------------------------------------------------------------------------
	// Utilities
	// -------------------------------------------------------------------------

    /**
     * Returns default spacing for a given section type.
     *
     * @param string $type  block | header | footer | button | space
     * @return array{ top: int, lft: int, right: int, left: int }
     */
    private static function default_spacing(string $type = 'block'): array
    {
        $map = [
            'block' => ['top' => '10px', 'left' => '10px', 'right' => '10px', 'bottom' => '10px'],
            'button' => ['top' => '12px', 'left' => '30px', 'right' => '30px', 'bottom' => '12px'],
            'space' => ['top' => '30px', 'bottom' => '30px'],
            'header' => ['top' => '20px', 'left' => '40px', 'right' => '40px', 'bottom' => '30px'],
            'footer' => ['top' => '20px', 'left' => '40px', 'right' => '40px', 'bottom' => '20px'],
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

    /**
     * Strips editor-placeholder spans, returning their inner text.
     *
     * @param mixed $content  String or array with a 'text' key.
     * @return string
     */
    private static function convert_placeholders($content): string
    {
        if (empty($content)) return '';

        if (! is_string($content)) {
            $arr = (array) $content;
            return $arr['text'] ?? '';
        }

        return preg_replace(
            '/<code[^>]*class=["\']editor-placeholder["\'][^>]*>(.*?)<\/code>/is',
            '$1',
            $content
        );
    }

    /**
     * Normalises table content to a 2-D array of strings.
     *
     * @param mixed $content
     * @return array
     */
    private static function sanitize_table($content): array
    {
        if (! is_array($content)) return [];

        return array_map(function ($row) {
            if (! is_array($row)) return [];
            return array_map(function ($cell) {
                if ($cell === null) return '';
                return trim((string) $cell);
            }, $row);
        }, $content);
    }

    /**
     * Normalises columns content to an array of non-empty block arrays.
     *
     * @param mixed $content
     * @return array
     */
    private static function sanitize_columns($content): array
    {
        if (! is_array($content)) return [];

        return array_map(function ($column) {
            if (! is_array($column)) return [];
            return array_values(array_filter($column));
        }, $content);
    }

	// -------------------------------------------------------------------------
	// Section generators
	// -------------------------------------------------------------------------

    /**
     * Generates HTML for a 'post' block.
     *
     * @param array $block
     * @return string
     */
    private static function post_block_html(array $block): string
    {
        $s            = $block['settings'] ?? [];
        $ids          = $s['ids'] ?? [];
        $count        = $s['count'] ?? 2;
        $cols         = max(1, (int) ($s['columns'] ?? 2));
        $order_by     = $s['orderBy'] ?? 'newest';
        $show_image   = $s['showImage'] ?? true;
        $show_excerpt = $s['showExcerpt'] ?? false;
        $show_button  = $s['showButton'] ?? true;
        $button_text  = $s['buttonText'] ?? 'Read More';
        $bg_color     = $s['background'] ?? '#ffffff';
        $text_color   = $s['textColor'] ?? '#333333';
        $padding      = Sanitize::box_values($s['padding'] ?? self::default_spacing('block'));

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

            if (! empty($s['categories'])) {
                $args['category__in'] = array_map('absint', (array) $s['categories']);
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
            $cards[] = "<td style=\"padding:8px;width:{$col_width};vertical-align:top;\">{$inner}</td>";
        }

        wp_reset_postdata();

        return self::render_card_rows($cards, $cols, $col_width, $padding, $bg_color);
    }

    /**
     * Generates HTML for a 'product' block.
     *
     * @param array $block
     * @return string
     */
    private static function product_block_html(array $block): string
    {
        $s            = $block['settings'] ?? [];
        $ids          = $s['ids'] ?? [];
        $count        = $s['count'] ?? 2;
        $cols         = max(1, (int) ($s['columns'] ?? 2));
        $order_by     = $s['orderBy'] ?? 'date';
        $order        = strtoupper($s['order'] ?? 'ASC');
        $sale_only    = $s['saleOnly'] ?? false;
        $show_image   = $s['showImage'] ?? true;
        $show_button  = $s['showButton'] ?? true;
        $button_text  = $s['buttonText'] ?? 'Shop Now';
        $bg_color     = $s['background'] ?? '#ffffff';
        $text_color   = $s['textColor'] ?? '#333333';
        $padding      = Sanitize::box_values($s['padding'] ?? self::default_spacing('block'));

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
            if (! empty($s['categories'])) {
                $args['tax_query'] = [[
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => array_map('absint', (array) $s['categories']),
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

            $cards[] = "<td style=\"padding:8px;width:{$col_width};vertical-align:top;\">{$inner}</td>";
        }

        wp_reset_postdata();

        return self::render_card_rows($cards, $cols, $col_width, $padding, $bg_color);
    }

    /**
     * Generates HTML for a 'post' block.
     *
     * @param array $block
     * @return string
     */
    private static function preview_post_block_html(array $block): string
    {
        $s         = $block['settings'] ?? [];
        $cols      = (int) ($s['columns'] ?? 2);
        $col_width = (int) floor(100 / $cols);
        $padding   = Sanitize::box_values($s['padding'] ?? self::default_spacing('block'));
        $items     = $block['content'] ?? [];

        if (empty($items)) {
            return "";
        }

        $show_image   = $s['showImage']   ?? true;
        $show_excerpt = $s['showExcerpt'] ?? false;
        $show_button  = $s['showButton']  ?? true;
        $bg_color     = $s['background']     ?? '#ffffff';
        $text_color   = $s['textColor']   ?? '#333333';
        $btn_text     = $s['buttonText']  ?? 'Read More';
        $count        = (int) ($s['count'] ?? 2);

        $cards = [];
        foreach (array_slice($items, 0, $count) as $item) {
            $image   = $item['featured_image'] ?? '';
            $title   = $item['title']          ?? '';
            $excerpt = isset($item['excerpt'])
                ? substr(strip_tags($item['excerpt']), 0, 120) . '...'
                : '';
            $link = $item['permalink'] ?? '#';

            $img_html = ($show_image && $image)
                ? "<img src=\"{$image}\" alt=\"" . esc_attr($title) . "\" style=\"width:100%;height:auto;display:block;margin-bottom:8px;\">"
                : '';

            $excerpt_html = $show_excerpt
                ? "<p style=\"margin:0 0 8px;font-size:13px;color:#666;\">{$excerpt}</p>"
                : '';

            $btn_html = $show_button
                ? "<a href=\"{$link}\" style=\"display:block;padding:6px 14px;background:#0073aa;color:#ffffff;font-size:13px;text-decoration:none;\">{$btn_text}</a>"
                : '';

            $cards[] = "<td style=\"padding:8px;width:{$col_width}%;vertical-align:top;\">
    			{$img_html}
    			<p style=\"margin:0 0 6px;font-weight:600;font-size:15px;color:{$text_color};\">{$title}</p>
    			{$excerpt_html}
    			{$btn_html}
    		</td>";
        }

        return self::render_card_rows($cards, $cols, $col_width, $padding, $bg_color);
    }

    /**
     * Generates HTML for a 'product' block.
     *
     * @param array $block
     * @return string
     */
    private static function preview_product_block_html(array $block): string
    {
        $s         = $block['settings'] ?? [];
        $cols      = (int) ($s['columns'] ?? 2);
        $col_width = (int) floor(100 / $cols);
        $padding   = Sanitize::box_values($s['padding'] ?? self::default_spacing('block'));
        $items     = $block['content'] ?? [];

        if (empty($items)) return '';

        $show_image  = $s['showImage']  ?? true;
        $show_button = $s['showButton'] ?? true;
        $bg_color    = $s['background']    ?? '#ffffff';
        $text_color  = $s['textColor']  ?? '#333333';
        $btn_text    = $s['buttonText'] ?? 'Shop Now';
        $count       = (int) ($s['count'] ?? 2);

        $cards = [];
        foreach (array_slice($items, 0, $count) as $item) {
            $image = $item['featured_image']['src'] ?? '';
            $link  = $item['permalink']             ?? '#';
            $name  = $item['name']                  ?? '';
            $price = isset($item['price_html'])
                ? strip_tags($item['price_html'])
                : ($item['price'] ?? '');

            $img_html = ($show_image && $image) || ! empty($item['on_sale']) ? '<div style="position:relative">' : '';
            $img_html .= ($show_image && $image)
                ? "<img src=\"{$image}\" alt=\"" . esc_attr($name) . "\" style=\"width:100%;height:auto;display:block;margin-bottom:8px;\">"
                : '';
            $img_html .= ! empty($item['on_sale'])
                ? '<span style="position:absolute;top:0;right:0;padding:5px;background:#000;color:#fff;font-size:12px;letter-spacing:1px;margin-bottom:6px;">SALE</span>'
                : '';
            $img_html .= ($show_image && $image) || ! empty($item['on_sale']) ? '</div>' : '';

            $rating_html = '';
            if (! empty($item['average_rating'])) {
                $stars     = self::render_stars((float) $item['average_rating']);
                $rat_count = $item['rating_count'] ?? 0;
                $rating_html = "<span style=\"display:block;padding:3px;font-size:15px;margin-bottom:6px;\">{$stars}"
                    . "<span>({$rat_count})</span></span>";
            }

            $btn_html = $show_button
                ? "<a href=\"{$link}\" style=\"display:block;padding:6px 14px;background:#0073aa;color:#ffffff;font-size:13px;text-decoration:none;\">{$btn_text}</a>"
                : '';

            $cards[] = "<td style=\"padding:8px;width:{$col_width}%;vertical-align:top;\">
    			{$img_html}
    			<h3 style=\"margin:0 0 4px;font-weight:600;font-size:15px;color:{$text_color};\">{$name}</h3>
    			<p style=\"margin:0 0 8px;font-size:14px;font-weight:600;color:#333;\">{$price}</p>
    			{$rating_html}
    			{$btn_html}
    		</td>";
        }

        return self::render_card_rows($cards, $cols, $col_width, $padding, $bg_color);
    }

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
                $row[] = "<td style=\"width:{$col_width}\"></td>";
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

    /**
     * Generates HTML for a 'table' block.
     *
     * @param array $block
     * @param array $design
     * @return string
     */
    private static function table_block_html(array $block, array $design): string
    {
        $table = self::sanitize_table($block['content'] ?? []);
        if (empty($table)) return '';

        $s            = $block['settings'] ?? [];
        $padding      = Sanitize::box_values($s['padding'] ?? self::default_spacing('block'));
        $th_bg        = $s['thbackground']   ?? '#0073aa';
        $border_color = $s['borderColor'] ?? '#e0e0e0';
        $th_color     = $s['thTextColor'] ?? '#ffffff';
        $bg_color     = $s['background']     ?? '#ffffff';
        $text_color   = $s['textColor']   ?? ($design['textColor'] ?? '#333333');
        $font         = $design['fontFamily'] ?? 'sans-serif';

        // Header row
        $header_html = '';
        if (! empty($table[0]) && is_array($table[0])) {
            $th_cells = array_map(function ($cell) use ($padding, $border_color, $th_color, $font) {
                $val = self::convert_placeholders($cell);
                return "<th style=\"padding:{$padding};border:1px solid {$border_color};color:{$th_color};font-family:{$font};font-weight:bold;text-align:left;\">{$val}</th>";
            }, $table[0]);
            $header_html = "<tr style=\"background-color:{$th_bg};\">" . implode('', $th_cells) . '</tr>';
        }

        // Body rows
        $body_html = '';
        foreach (array_slice($table, 1) as $row) {
            if (! is_array($row)) continue;
            $cells = array_map(function ($cell) use ($padding, $border_color, $text_color, $font) {
                $val = self::convert_placeholders($cell);
                return "<td style=\"padding:{$padding};border:1px solid {$border_color};color:{$text_color};font-family:{$font};\">{$val}</td>";
            }, $row);
            $body_html .= "<tr style=\"background-color:{$bg_color};\">" . implode('', $cells) . '</tr>';
        }

        return "<tr>
			<td style=\"padding:{$padding};\">
				<table role=\"presentation\" style=\"width:100%;border:0;border-collapse:collapse;\">
					{$header_html}
					{$body_html}
				</table>
			</td>
		</tr>";
    }

    /**
     * Generates HTML for a single block.
     *
     * @param array $block
     * @param array $design
     * @return string
     */
    private static function block_html(array $block, array $design): string
    {
        $type    = $block['type']    ?? '';
        $s       = $block['settings'] ?? [];
        $content = $block['content']  ?? ['html' => ''];
        $font    = $design['fontFamily'] ?? 'sans-serif';
        $padding = Sanitize::box_values($s['padding'] ?? self::default_spacing($type === 'button' ? 'button' : 'block'));

        switch ($type) {

            case 'text':
                $text_color = $s['textColor']  ?? ($design['textColor'] ?? '#333333');
                $bg_color   = $s['background']    ?? 'transparent';
                $alignment  = $s['alignment']  ?? 'left';
                return "<tr>
					<td style=\"padding:{$padding};color:{$text_color};font-family:{$font};background-color:{$bg_color};text-align:{$alignment};\">
						" . self::convert_placeholders($content['html']) . "
					</td>
				</tr>";

            case 'heading':
                $htag       = (int) ($s['level'] ?? 2);
                $text_color = $s['textColor'] ?? ($design['textColor'] ?? '#333333');
                $bg_color   = $s['background']   ?? 'transparent';
                $alignment  = $s['alignment'] ?? 'left';
                return "<tr>
					<td style=\"padding:{$padding};color:{$text_color};font-family:{$font};background-color:{$bg_color};text-align:{$alignment};\">
						<h{$htag}>" . self::convert_placeholders($content['html']) . "</h{$htag}>
					</td>
				</tr>";

            case 'button':
                $alignment     = $s['alignment']    ?? 'center';
                $spacing       = Sanitize::box_values($s['spacing'] ?? self::default_spacing('space'));
                $href          = $s['href']          ?? '#';
                $bg_color      = $s['background']       ?? '#0073aa';
                $text_color    = $s['textColor']     ?? '#ffffff';
                $border_radius = (int) ($s['borderRadius'] ?? 5);
                $margin        = $alignment === 'left' ? '0 auto 0 0' : ($alignment === 'right' ? '0 0 0 auto' : '0 auto');
                return "<tr>
					<td style=\"text-align:{$alignment};padding:0;\">
						<table role=\"presentation\" style=\"border:0;border-collapse:collapse;margin:{$margin};\">
							<tr>
								<td style=\"padding:{$spacing};\">
									<a href=\"{$href}\" target=\"_blank\" style=\"display:block;padding:{$padding};background-color:{$bg_color};color:{$text_color};font-family:{$font};font-weight:bold;text-decoration:none;border-radius:{$border_radius}px;\">
										" . self::convert_placeholders($content['html']) . "
									</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>";

            case 'image':
                if (empty($s['src'])) return '';
                $src       = $s['src'];
                $alt       = $s['alt']       ?? '';
                $width     = $s['width']     ?? '100%';
                $alignment = $s['alignment'] ?? 'center';
                $margin    = $alignment === 'left' ? '0 auto 0 0' : ($alignment === 'right' ? '0 0 0 auto' : '0 auto');
                return "<tr>
					<td style=\"padding:{$padding};text-align:{$alignment};\">
						<img src=\"{$src}\" alt=\"" . esc_attr($alt) . "\" width=\"{$width}\" style=\"max-width:100%;height:auto;display:block;margin:{$margin};\" />
					</td>
				</tr>";

            case 'divider':
                $height = (int) ($s['height'] ?? 1);
                $color  = $s['color'] ?? '#e0e0e0';
                $spacing = Sanitize::box_values($s['padding'] ?? self::default_spacing('space'));
                return "<tr>
					<td style=\"padding:{$spacing};\">
						<table role=\"presentation\" style=\"width:100%;border:0;border-collapse:collapse;\">
							<tr>
								<td style=\"border-top:{$height}px solid {$color};\">&nbsp;</td>
							</tr>
						</table>
					</td>
				</tr>";
            case 'spacer':
                $height = (int) ($s['height'] ?? 1);
                $color  = $s['color'] ?? '#e0e0e0';
                return "<tr>
					<td style=\"padding:0;\">
						<td style=\"border-top:{$height}px solid transparent;\">&nbsp;</td>
					</td>
				</tr>";
            case 'table':
                return self::table_block_html($block, $design);

            case 'post':
                return self::post_block_html($block);

            case 'product':
                return self::product_block_html($block);

            case 'columns':
                $columns   = self::sanitize_columns($content);
                if (empty($columns)) return '';
                $col_count = count($columns) ?: 1;
                $col_width = (int) floor(100 / $col_count);
                $align     = $s['alignItems'] ?? 'top';

                $cols_html = implode('', array_map(
                    function ($column_blocks) use ($col_width, $align, $design) {
                        $nested = is_array($column_blocks)
                            ? implode('', array_map(
                                fn($b) => self::block_html((array) $b, $design),
                                array_filter($column_blocks)
                            ))
                            : '';
                        return "<td style=\"width:{$col_width}%;vertical-align:{$align};padding:0;\">
							<table role=\"presentation\" style=\"width:100%;border:0;border-collapse:collapse;\">
								{$nested}
							</table>
						</td>";
                    },
                    $columns
                ));

                return "<tr>
					<td style=\"padding:{$padding};\">
						<table role=\"presentation\" style=\"width:100%;border:0;border-collapse:collapse;\">
							<tr>{$cols_html}</tr>
						</table>
					</td>
				</tr>";

            default:
                return "<tr>
					<td style=\"padding:0 0 20px;font-family:{$font};\">
						" . self::convert_placeholders($content['html']) . "
					</td>
				</tr>";
        }
    }

    /**
     * Generates the header <tr>.
     *
     * @param array $header
     * @param array $design
     * @return string
     */
    private static function header_html(array $header, array $design): string
    {
        $s          = $header['settings'] ?? [];
        $bg_color   = $s['background']   ?? 'unset';
        $padding    = Sanitize::box_values($s['padding'] ?? self::default_spacing('header'));
        $alignment  = $s['alignment'] ?? 'center';
        $font       = $design['fontFamily'] ?? 'sans-serif';
        $text_color = $s['textColor'] ?? '#333333';
        $logo_url   = $header['logoUrl']   ?? '';
        $logo_width = $header['logoWidth'] ?? 60;

        if (! empty($header['logo']) && empty($logo_url)) {
            $inner = '{{site_logo}}';
        } elseif (! empty($logo_url)) {
            $inner = "<img src=\"{$logo_url}\" alt=\"{{site_name}}\" width=\"{$logo_width}%\" style=\"height:auto;display:block;margin:0 auto;\" />";
        } else {
            $title      = self::convert_placeholders($header['title']['html'] ?? '') ?: '{{site_name}}';
            $title_size = $s['titleSize'] ?? '28px';
            $inner      = "<h1 style=\"margin:0;color:{$text_color};font-size:{$title_size};font-family:{$font};\">{$title}</h1>";
        }

        $desc_html = '';
        if (! empty($s['showDescription'])) {
            $font_size   = $s['fontSize']   ?? '14px';
            $description = self::convert_placeholders($header['description']['html'] ?? '');
            $desc_html   = "<p style=\"margin:10px 0 0;font-size:{$font_size};color:{$text_color};font-family:{$font};\">{$description}</p>";
        }

        return "<tr>
			<td style=\"background:{$bg_color};padding:{$padding};text-align:{$alignment};\">
				{$inner}
				{$desc_html}
			</td>
		</tr>";
    }

    /**
     * Generates the footer <tr>.
     *
     * @param array $footer
     * @param array $design
     * @return string
     */
    private static function footer_html(array $footer, array $design): string
    {
        $s          = $footer['settings'] ?? [];
        $bg_color   = $s['background']   ?? 'unset';
        $alignment  = $s['alignment'] ?? 'center';
        $padding    = Sanitize::box_values($s['padding'] ?? self::default_spacing('footer'));
        $font_size  = $s['fontSize']  ?? '12';
        $text_color = $s['textcolor'] ?? '#666666'; // intentionally lowercase 'c' — matches JS source
        $font       = $design['fontFamily'] ?? 'sans-serif';
        $content    = self::convert_placeholders($footer['content']['html'] ?? '');

        return "<tr>
			<td style=\"background:{$bg_color};text-align:{$alignment};padding:{$padding};border-top:1px solid #e0e0e0;font-size:{$font_size};color:{$text_color};font-family:{$font};\">
				{$content}
			</td>
		</tr>";
    }

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

    /**
     * Generates a complete HTML email string.
     *
     * @param string  $subject  Email subject (used in <title>).
     * @param array   $header   Decoded header data.
     * @param array[] $blocks   Array of decoded block arrays.
     * @param array   $footer   Decoded footer data.
     * @param array   $design   Decoded design settings.
     * @return string
     */
    public static function generate(string $subject, array $header, array $blocks, array $footer, array $design): string
    {
        $header_html    = self::header_html($header, $design);
        $footer_html    = self::footer_html($footer, $design);
        $blocks_html    = implode('', array_map(fn($b) => self::block_html((array) $b, $design), $blocks));
        $body_bg        = $design['bodyBg']     ?? '#f4f4f4';
        $container_bg   = $design['containerBg'] ?? '#ffffff';
        $container_w    = (int) ($design['containerWidth'] ?? 600);
        $border_radius  = (int) ($design['borderRadius']  ?? 8);
        $padding        = Sanitize::box_values($design['padding'] ?? self::default_spacing());
        $text_color     = $design['textColor']  ?? '#333333';
        $font_size      = (int) ($design['fontSize'] ?? 14);
        $font_family    = $design['fontFamily'] ?? 'sans-serif';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{$subject}</title>
	<style id="cps-styles-inline-css">ul,ol{margin: 0 0 0.5em 1.5em;padding: 0;list-style-type: disc;}</style>
</head>
<body style="margin: 20px; padding:0; background-color: {$body_bg};">
    <table class="econtainer" style="width: 100%; border: 0; border-collapse: collapse; background: {$container_bg}; max-width: {$container_w}px; border-radius: {$border_radius}px; margin:auto">
        {$header_html}
        <tr>
            <td class="einner" style="padding: {$padding}; color: {$text_color}; font-size: {$font_size}; font-family: {$font_family}; line-height: 1.6;">
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
        $blocks  = json_decode($c['blocks'] ?? '[]', true) ?: [];
        $header  = json_decode($c['header'] ?? '{}', true) ?: [];
        $footer  = json_decode($c['footer'] ?? '{}', true) ?: [];

        return self::generate($subject, $header, $blocks, $footer, $design);
    }
}
