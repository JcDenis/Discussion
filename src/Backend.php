<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\Notices;
use Dotclear\Database\Cursor;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Input, Label, Note, Para, Select, Text, Textarea };
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Interface\Core\BlogSettingsInterface;
use Throwable;

/**
 * @brief       Discussion backend class.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            // blog settings form
            'adminBlogPreferencesFormV2' => function (BlogSettingsInterface $blog_settings): void {
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
                                (new Checkbox(My::id() . 'publish_post', (bool) $blog_settings->get(My::id())->get('publish_post')))
                                    ->value(1)
                                    ->label(new Label(__('Publish new discussion without validation'), Label::IL_FT)),
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
            },
            // blog settings update
            'adminBeforeBlogSettingsUpdate' => function (BlogSettingsInterface $blog_settings): void {
                $blog_settings->get(My::id())->put('active', !empty($_POST[My::id() . 'active']));
                $blog_settings->get(My::id())->put('publish_post', !empty($_POST[My::id() . 'publish_post']));
                $blog_settings->get(My::id())->put('root_cat', (int) $_POST[My::id() . 'root_cat'] ?? '');
            },
            // add js for test editor
            'adminBlogPreferencesHeaders' => fn (): string => My::jsLoad('backend'),
        ]);

        return true;
    }
}
