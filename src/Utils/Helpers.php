<?php

namespace App\Utils;
use App\Services\NotificationService;

/**
 * Class Helpers
 *
 * A place for small, generic helper functions you might need.
 */
class Helpers
{
    /**
     * Example helper function to sanitize user input or format strings.
     *
     * @param string $input
     * @return string
     */

    public static function sanitizeInput(string $input): string
    {
        // trivial example
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

}
