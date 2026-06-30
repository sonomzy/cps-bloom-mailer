<?php

namespace ChicpixiesBloomMailer\Mailers;
use ChicpixiesBloomMailer\Core\Settings;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MailerFactory {

	public static function make(): MailerBase {
		$mailer = Settings::get( 'mailer', 'smtp' );

		switch ( $mailer ) {
			case 'ses':
				return new MailerSES();
			case 'smtp':
			default:
				return new MailerSMTP();
		}
	}
}
