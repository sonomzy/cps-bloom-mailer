<?php

namespace ChicpixiesBloomMailer;

use WP_List_Table;

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Campaigns_Table extends WP_List_Table
{
	private int   $total_items = 0;
	private array $filter_args = [];

	public function __construct()
	{
		parent::__construct([
			'singular' => 'campaign',
			'plural'   => 'campaigns',
			'ajax'     => true,
		]);
	}

	public function get_columns()
	{
		return [
			'cb'            => '<input type="checkbox" />',
			'title'         => __('Title', 'cps-bloom-mailer'),
			'subject'       => __('Subject', 'cps-bloom-mailer'),
			'status'        => __('Status', 'cps-bloom-mailer'),
			'recipients'        => __('Recipients', 'cps-bloom-mailer'),
			'sent at'       => __('Sent At', 'cps-bloom-mailer'),
		];
	}

	public function get_sortable_columns()
	{
		return [
			'status'  => ['status', false]
		];
	}

	// -------------------------------------------------------------------------
	// Cell renderers
	// -------------------------------------------------------------------------

	protected function column_default($item, $column_name)
	{
		return esc_html($item->$column_name ?? '');
	}

	protected function column_cb($item)
	{
		return '<input type="checkbox" name="campaigns[]" value="' . (int) $item->id . '" />';
	}

	protected function column_title($item)
	{
		$actions = [];
		if (in_array($item->status, ['draft', 'scheduled '])) {
			$actions['send'] = '<a href="#" class="cps-single-action" data-action="send" data-id="' . esc_attr($item->id) . '">' . __('Send', 'cps-bloom-mailer') . '</a>';
		} elseif ($item->status === 'sending') {
			$actions['cancel'] = '<a href="#" class="cps-single-action" data-action="cancel" data-id="' . esc_attr($item->id) . '">' . __('Cancel', 'cps-bloom-mailer') . '</a>';
		}

		$actions['dublicate'] = '<a href="#" class="cps-single-action" data-action="dublicate" data-id="' . esc_attr($item->id) . '">' . __('Dublicate', 'cps-bloom-mailer') . '</a>';
		$actions['delete'] = '<a href="#" class="cps-single-action" data-action="delete" data-id="' . esc_attr($item->id) . '">' . __('Delete', 'cps-bloom-mailer') . '</a>';

		$url = add_query_arg(['campaign' => $item->id, 'action' => 'edit']);
		return '<a href="' . $url . '"><strong>' . esc_html($item->title) . '</strong></a>' .
			$this->row_actions($actions);
	}

	protected function column_subject($item)
	{
		return $item->subject ?? '-';
	}

	protected function column_status($item)
	{
		return '<span class="cps-status cps-status--' . esc_attr($item->status) . '">' . esc_html(ucfirst($item->status)) . '</span>';
	}

	protected function column_recipients($item)
	{
		return number_format($item->total_recipients);
	}

	protected function column_sent_at($item)
	{
		return $item->sent_at ? esc_html(date_i18n(get_option('date_format'), strtotime($item->sent_at))) : '&mdash;';
	}


	protected function get_bulk_actions()
	{
		return [
			'cancel' => __('Cancel', 'cps-bloom-mailer'),
			'delete' => __('Delete', 'cps-bloom-mailer'),
			'pause' => __('Pause', 'cps-bloom-mailer'),
			'export' => __('Export selected (CSV)', 'cps-bloom-mailer'),
		];
	}

	public function prepare_items()
	{
		$per_page = $this->get_items_per_page('cps_campaign_per_page', 20);
		$paged    = max(1, $this->get_pagenum());
		$order = strtoupper($_GET['order'] ?? 'DESC');

		$this->filter_args = [
			'limit'   => $per_page,
			'offset'  => ($paged - 1) * $per_page,
			'orderby' => 'created_at',
			'order'   => in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC',
			'status'  => !empty($_GET['status']) ? sanitize_text_field($_GET['status']) : null,
			'search'  => !empty($_GET['s']) ? sanitize_text_field($_GET['s']) : null,
		];

		// 1. paginated data
		$this->items = Campaign::get_all($this->filter_args);

		// 2. total count (same filters, no limit)
		$count_args = $this->filter_args;
		unset($count_args['limit'], $count_args['offset']);

		$this->total_items = Campaign::count($count_args);

		$this->_column_headers = [
			$this->get_columns(),
			[],
			$this->get_sortable_columns(),
		];

		$this->set_pagination_args([
			'total_items' => $this->total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($this->total_items / $per_page),
		]);
	}

	public function get_filter_args()
	{
		return $this->filter_args;
	}

	// -------------------------------------------------------------------------
	// Extra table nav (filters + export button)
	// -------------------------------------------------------------------------

	protected function extra_tablenav($which)
	{
		if ($which !== 'top') return;
		$status_filter = sanitize_text_field($_GET['status'] ?? '');
?>
		<div class="alignleft actions cps-sub-filters">
			<select name="status" style="margin-left: 10px;">
				<option value=""><?php esc_html_e('All Statuses', 'cps-bloom-mailer'); ?></option>
				<?php
				foreach (['sent', 'draft', 'scheduled', 'sending', 'paused', 'failed'] as $value) {
					$label = str_replace('_', ' ', $value);
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr($value),
						selected($status_filter, $value, false),
						esc_html(ucfirst($label))
					);
				}
				?>
			</select>
			<input type="submit" name="filter_action" id="campaign-query-submit" class="button" value="<?php esc_attr_e('Filter', 'cps-bloom-mailer'); ?>">
		</div>
<?php
	}

	public function no_items()
	{
		esc_html_e('No campaigns yet.', 'cps-bloom-mailer');
	}
}
