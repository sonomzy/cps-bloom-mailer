<?php

namespace ChicpixiesBloomMailer;

use ChicpixiesBloom\Audience;
use WP_Error;

if (!defined('ABSPATH')) exit;

class Segments extends Audience
{
    protected static string $table = 'cps_mailer_segments';

    protected static function supports_rules(): bool
    {
        return true;
    }
}
