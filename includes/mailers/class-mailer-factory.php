<?php

namespace ChicpixiesBloomMailer;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mailer_Factory {

	public static function make(): Mailer_Base {
		$mailer = Settings::get( 'mailer', 'smtp' );

		switch ( $mailer ) {
			case 'ses':
				return new Mailer_SES();
			case 'smtp':
			default:
				return new Mailer_SMTP();
		}
	}
}
