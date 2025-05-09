<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Core\Process;
use Exception;

/**
 * @brief       Discussion module install class.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            My::settings()->put('active', false, 'boolean', 'Enable users to post discussions on frontend', false, true);
            My::settings()->put('publish_post', false, 'boolean', 'Publish new discussion without validation', false, true);
            My::settings()->put('signup_perm', false, 'boolean', 'Add user permission on signup', false, true);
            My::settings()->put('root_cat', 0, 'integer', 'Limit discussion to this category children', false, true);

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}
