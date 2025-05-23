<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Label, Para, Select, Text };
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\BlogSettingsInterface;

/**
 * @brief       Discussion module backend behaviors.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class BackendBehaviors
{
    public static function adminBlogPreferencesFormV2(BlogSettingsInterface $blog_settings): void
    {
        echo (new Div())
            ->class('fieldset')
            ->items([
                (new Text('h4', My::name()))
                    ->id(My::id() . '_params'),
                (new Para())
                    ->items([
                        (new Checkbox(My::id() . 'active', (bool) $blog_settings->get(My::id())->get('active')))
                            ->value(1)
                            ->label(new Label(__('Enable users to post discussions on frontend'), Label::IL_FT)),
                    ]),
                (new Para())
                    ->items([
                        (new Checkbox(My::id() . 'signup_perm', (bool) $blog_settings->get(My::id())->get('signup_perm')))
                            ->value(1)
                            ->label(new Label(__('Add user permission to post discussions on sign up'), Label::IL_FT)),
                    ]),
                (new Para())
                    ->items([
                        (new Checkbox(My::id() . 'publish_post', (bool) $blog_settings->get(My::id())->get('publish_post')))
                            ->value(1)
                            ->label(new Label(__('Publish new discussion without validation'), Label::IL_FT)),
                    ]),
                (new Para())
                    ->items([
                        (new Checkbox(My::id() . 'unregister_comment', (bool) $blog_settings->get(My::id())->get('unregister_comment')))
                            ->value(1)
                            ->label(new Label(__('Open discussions comments to unregistered users'), Label::IL_FT)),
                    ]),
                (new Para())
                    ->items([
                        (new Select(My::id() . 'root_cat'))
                            ->items(Core::getCategoriesCombo())
                            ->default((string) (int) $blog_settings->get(My::id())->get('root_cat'))
                            ->label((new Label(__('Limit discussion to this category children:'), Label::OL_TF))),
                    ])
            ])
            ->render();
    }

    public static function adminBeforeBlogSettingsUpdate(BlogSettingsInterface $blog_settings): void
    {
        $blog_settings->get(My::id())->put('active', !empty($_POST[My::id() . 'active']), 'boolean');
        $blog_settings->get(My::id())->put('signup_perm', !empty($_POST[My::id() . 'signup_perm']), 'boolean');
        $blog_settings->get(My::id())->put('publish_post', !empty($_POST[My::id() . 'publish_post']), 'boolean');
        $blog_settings->get(My::id())->put('unregister_comment', !empty($_POST[My::id() . 'unregister_comment']), 'boolean');
        $blog_settings->get(My::id())->put('root_cat', (int) $_POST[My::id() . 'root_cat'] ?: 0, 'integer');
    }

    public static function adminBlogPreferencesHeaders(): string
    {
        return My::jsLoad('backend');
    }
}
