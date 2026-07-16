<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Fieldset, Img, Label, Legend, Li, Link, Number, Para, Select, Text, Ul };
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
    private static function canEdit(): bool
    {
        return App::blog()->settings()->get('commentsWikibar')->getBool('active')
            && App::blog()->settings()->get('system')->getBool('markdown_comments');
    }

    public static function adminBlogPreferencesFormV2(BlogSettingsInterface $blog_settings): void
    {
        echo (new Fieldset(My::id() . '_params'))
            ->legend(new Legend((new Img(My::icons()[0]))->class('icon-small')->render() . ' ' . My::name()))
            ->items([
                (new Div())
                    ->class('two-cols')->separator('')
                    ->items([
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'active', $blog_settings->get(My::id())->getBool('active', false)))
                                            ->value(1)
                                            ->label(new Label(__('Enable users to post discussions on frontend'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'signup_perm', $blog_settings->get(My::id())->getBool('signup_perm', false)))
                                            ->value(1)
                                            ->label(new Label(__('Add user permission to post discussions on sign up'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'publish_post', $blog_settings->get(My::id())->getBool('publish_post', false)))
                                            ->value(1)
                                            ->label(new Label(__('Publish new discussion without validation'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'canedit_post', $blog_settings->get(My::id())->getBool('canedit_post', false)))
                                            ->value(1)
                                            ->disabled(!self::canEdit())
                                            ->label(new Label(__('Allow users to edit their own discussions from frontend'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Number(My::id() . 'canedit_time', 0, 60))
                                            ->value($blog_settings->get(My::id())->getInt('canedit_time', false))
                                            ->label(new Label(__('Limit discussions edition to a given time in minutes (0 for no limit):'), Label::OL_TF)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'unregister_comment', $blog_settings->get(My::id())->getBool('unregister_comment', false)))
                                            ->value(1)
                                            ->label(new Label(__('Open discussions comments to unregistered users'), Label::IL_FT)),
                                    ]),
                            ]),
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Select(My::id() . 'root_cat'))
                                            ->items(Core::getCategoriesCombo())
                                            ->default($blog_settings->get(My::id())->getInt('root_cat', false))
                                            ->label((new Label(__('Limit discussion to this category children:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Select(My::id() . 'artifact'))
                                            ->items(Core::getPostArtifactsCombo())
                                            ->default($blog_settings->get(My::id())->getStr('artifact', false))
                                            ->label((new Label(__('Prefix to use on resolved posts titles:'), Label::OL_TF))),
                                    ]),
                                (new Text('h5', __('Discussions and comments edition requirements:')))
                                    ->class('form-note'),
                                (new Ul())
                                    ->items([
                                        (new Li())
                                            ->class('form-note')
                                            ->items([
                                                (new Link())
                                                    ->href('#legacy_markdown')
                                                    ->text(__('Markdown syntax must be activated')),
                                            ]),
                                        (new Li())
                                            ->class('form-note')
                                            ->items([
                                                (new Link())
                                                    ->href('?process=Plugin&p=commentsWikibar')
                                                    ->text(__('Wikibar must be activated')),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ])
            ->render();
    }

    public static function adminBeforeBlogSettingsUpdate(BlogSettingsInterface $blog_settings): void
    {
        // Post data helpers
        $_Bool = fn (string $name): bool => !empty($_POST[$name]);
        $_Int  = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
        $_Str  = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

        $blog_settings->get(My::id())->put('active', $_Bool(My::id() . 'active'), App::blogWorkspace()::NS_BOOL);
        $blog_settings->get(My::id())->put('signup_perm', $_Bool(My::id() . 'signup_perm'), App::blogWorkspace()::NS_BOOL);
        $blog_settings->get(My::id())->put('publish_post', $_Bool(My::id() . 'publish_post'), App::blogWorkspace()::NS_BOOL);
        $blog_settings->get(My::id())->put('canedit_post', $_Bool(My::id() . 'canedit_post'), App::blogWorkspace()::NS_BOOL);
        $blog_settings->get(My::id())->put('canedit_time', $_Int(My::id() . 'canedit_time'), App::blogWorkspace()::NS_INT);
        $blog_settings->get(My::id())->put('unregister_comment', $_Bool(My::id() . 'unregister_comment'), App::blogWorkspace()::NS_BOOL);
        $blog_settings->get(My::id())->put('root_cat', $_Int(My::id() . 'root_cat'), App::blogWorkspace()::NS_INT);
        $blog_settings->get(My::id())->put('artifact', $_Str(My::id() . 'artifact'), App::blogWorkspace()::NS_STRING);
    }
}
