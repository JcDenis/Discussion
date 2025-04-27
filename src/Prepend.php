<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       Discussion module prepend.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Add permission to create post from frontend
        App::auth()->setPermissionType(
            My::id(),
            My::name()
        );

        // Add URL to create post from frontend
        App::url()->register(
            My::id(),
            'discussion',
            '^discussion(/(create|list)(/.+)?)$',
            FrontendUrl::discussionEndpoint(...)
        );

        return true;
    }
}
