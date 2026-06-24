<?php

namespace ChicpixiesBloomMailer;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Mailer_Base {

	/**
	 * Send an email.
	 *
	 * @param array $args {
	 *     @type string $to
	 *     @type string $subject
	 *     @type string $html
	 *     @type string $from_name
	 *     @type string $from_email
	 *    @type string $reply_to
	 * }
	 * @return bool
	 */
	abstract public function send( array $args ): bool;

	protected function get_default_from_name(): string {
		return Settings::get( 'from_name', get_bloginfo( 'name' ) );
	}

	protected function get_default_from_email(): string {
		return Settings::get( 'from_email', get_option( 'admin_email' ) );
	}

	protected function get_default_reply_to(): string {
		return Settings::get( 'reply_to', '' );
	}
}
