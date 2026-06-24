<?php

use ChicpixiesBloomMailer\Campaign;

if (! defined('ABSPATH')) exit;
?>

<div class="wrap">
	<h1>
		<?php if ($campaign_id) : ?>
			<?php
			$campaign = Campaign::get($campaign_id);
			echo esc_html__('Stats: ', 'cps-bloom-mailer') . esc_html($campaign->title ?? '');
			?>
			<a href="<?php echo esc_url(admin_url('admin.php?page=cps-bloom-mailer-stats')); ?>" class="page-title-action">
				<?php esc_html_e('Overview', 'cps-bloom-mailer'); ?>
			</a>
		<?php else : ?>
			<?php esc_html_e('Stats Overview', 'cps-bloom-mailer'); ?>
		<?php endif; ?>
	</h1>

	<div id="cps-stats-dashboard">
		<p style="color:#999; padding: 40px 0;">
			<?php esc_html_e('Loading stats...', 'cps-bloom-mailer'); ?>
		</p>
	</div>
</div>