<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Fieldset, Img, Label, Legend, Li, Link, Number, Para, Select, Text, Ul };
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
    private static function canEdit(): bool
    {
        return App::blog()->settings()->get('commentsWikibar')->get('active') !== false
            && App::blog()->settings()->get('system')->get('markdown_comments');
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
                                        (new Checkbox(My::id() . 'canedit_post', (bool) $blog_settings->get(My::id())->get('canedit_post')))
                                            ->value(1)
                                            ->disabled(!self::canEdit())
                                            ->label(new Label(__('Allow users to edit their own discussions from frontend'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Number(My::id() . 'canedit_time', 0, 60))
                                            ->value((string) (int) $blog_settings->get(My::id())->get('canedit_time'))
                                            ->label(new Label(__('Limit discussions edition to a given time in minutes (0 for no limit):'), Label::OL_TF)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'unregister_comment', (bool) $blog_settings->get(My::id())->get('unregister_comment')))
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
                                            ->default((string) (int) $blog_settings->get(My::id())->get('root_cat'))
                                            ->label((new Label(__('Limit discussion to this category children:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Select(My::id() . 'artifact'))
                                            ->items(Core::getPostArtifactsCombo())
                                            ->default((string) $blog_settings->get(My::id())->get('artifact'))
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
        $blog_settings->get(My::id())->put('active', !empty($_POST[My::id() . 'active']), 'boolean');
        $blog_settings->get(My::id())->put('signup_perm', !empty($_POST[My::id() . 'signup_perm']), 'boolean');
        $blog_settings->get(My::id())->put('publish_post', !empty($_POST[My::id() . 'publish_post']), 'boolean');
        $blog_settings->get(My::id())->put('canedit_post', !empty($_POST[My::id() . 'canedit_post']), 'boolean');
        $blog_settings->get(My::id())->put('canedit_time', (int) $_POST[My::id() . 'canedit_time'] ?: 0, 'integer');
        $blog_settings->get(My::id())->put('unregister_comment', !empty($_POST[My::id() . 'unregister_comment']), 'boolean');
        $blog_settings->get(My::id())->put('root_cat', (int) $_POST[My::id() . 'root_cat'] ?: 0, 'integer');
        $blog_settings->get(My::id())->put('artifact', (string) $_POST[My::id() . 'artifact'], 'string');
    }
}
