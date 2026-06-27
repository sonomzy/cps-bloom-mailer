<?php

namespace ChicpixiesBloomMailer;

use WP_Error;

if (! defined('ABSPATH')) {
	exit;
}

class Mailer_SMTP extends Mailer_Base
{
	protected ?string $from_email = null;
	protected ?string $from_name  = null;
	protected ?string $reply_to   = null;

	public function send(array $args): mixed
	{
		$to         = $args['to'] ?? '';
		$subject    = $args['subject'] ?? '';
		$html       = $args['html'] ?? '';
		$from_name  = $args['from_name'] ?: $this->get_default_from_name();
		$from_email = $args['from_email'] ?: $this->get_default_from_email();
		$reply_to	= $args['reply_to'] ?: $this->get_default_reply_to();

		if (! $to || ! $subject || ! $html) {
			$isTo = !$to;
			$isSub = !$subject;
			$isHtml = !$html;
			return new WP_Error('ses_error', "missing required data:Recipient:{$isTo}, Subject:{$isSub}, Html:{$isHtml}", ['status' => $code]);
		}

		// Hook into wp_mail to set SMTP credentials
		add_action('phpmailer_init', array($this, 'configure_phpmailer'));
		add_filter('wp_mail_from', array($this, 'set_from_email'));
		add_filter('wp_mail_from_name', array($this, 'set_from_name'));

		$this->from_email = $from_email;
		$this->from_name  = $from_name;
		$this->reply_to    = $reply_to;

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		$result = wp_mail($to, $subject, $html, $headers);

		remove_action('phpmailer_init', array($this, 'configure_phpmailer'));
		remove_filter('wp_mail_from', array($this, 'set_from_email'));
		remove_filter('wp_mail_from_name', array($this, 'set_from_name'));

		if (is_wp_error($result)) {
			error_log('CPS Mailer SES error: ' . $result->get_error_message());
			return $result;
		}

		return (bool) $result;
	}

	public function configure_phpmailer($phpmailer)
	{
		$host       = Settings::get('smtp_host');
		$port       = intval(Settings::get('smtp_port', 587));
		$encryption = Settings::get('smtp_encryption', 'tls');
		$username   = Settings::get('smtp_username');
		$password   = Settings::get('smtp_password');

		if (! $host) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $host;
		$phpmailer->Port       = $port;
		$phpmailer->SMTPAuth   = ! empty($username);
		$phpmailer->Username   = $username;
		$phpmailer->Password   = $password;
		$phpmailer->SMTPSecure = $encryption === 'ssl' ? 'ssl' : 'tls';

		if ($this->reply_to) {
			$phpmailer->addReplyTo($this->reply_to);
		}
	}

	public function set_from_email($email)
	{
		return $this->from_email ?: $email;
	}

	public function set_from_name($name)
	{
		return $this->from_name ?: $name;
	}
}
