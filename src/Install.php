<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       Discussion module install class.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Install
{
    use TraitProcess;

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
            My::settings()->put('active', false, App::blogWorkspace()::NS_BOOL, 'Enable users to post discussions on frontend', false, true);
            My::settings()->put('publish_post', false, App::blogWorkspace()::NS_BOOL, 'Publish new discussion without validation', false, true);
            My::settings()->put('canedit_post', false, App::blogWorkspace()::NS_BOOL, 'Allow post edition on frontend', false, true);
            My::settings()->put('canedit_time', 0, App::blogWorkspace()::NS_INT, 'Limit post edition to a given time', false, true);
            My::settings()->put('signup_perm', false, App::blogWorkspace()::NS_BOOL, 'Add user permission on signup', false, true);
            My::settings()->put('root_cat', 0, App::blogWorkspace()::NS_INT, 'Limit discussion to this category children', false, true);

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}
