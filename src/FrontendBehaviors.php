<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Ctx;
use Dotclear\Database\{ Cursor, MetaRecord };
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Form, Hidden, Label, Li, Link, Para, Submit, Text, Textarea, Ul };
use Dotclear\Helper\Html\{ Html, WikiToHtml };
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\commentsWikibar\My as Wikibar;
use Dotclear\Plugin\legacyMarkdown\Helper as Markdown;
use Dotclear\Plugin\FrontendSession\{ CommentOptions, FrontendSessionProfil };

/**
 * @brief       Discussion module frontend behaviors.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendBehaviors
{
    private static bool $loop = false;

    /**
     * Overload discussion post template.
     */
    public static function urlHandlerBeforeGetData(Ctx $ctx): void
    {
        if (!self::$loop && $ctx->exists('posts') && Core::isDiscussionCategory((int) $ctx->posts->f('cat_id'))) {
            // force Markdown syntax for public post and comment !
            App::blog()->settings()->get('system')->set('markdown_comments', true);

            self::$loop = true;
            FrontendUrl::serveTemplate('post');
            exit();
        }
    }

    /**
     * Load JS and CSS and add wiki bar to post form.
     */
    public static function publicHeadContent(): void
    {
        // style
        $tplset = App::themes()->moduleInfo(App::blog()->settings()->get('system')->get('theme'), 'tplset');
        if (in_array($tplset, ['dotty', 'mustek'])) {
            echo My::cssLoad('frontend-' . $tplset);
        }

        // resolve
        if (Core::getPostArtifact() != '') {
            echo My::jsLoad('frontend-post') .
                Html::jsJson(My::id() . 'resolver', [
                    'url' => App::blog()->url() . App::url()->getBase(My::id()),
                ]);
        }

        // reply
        if (App::auth()->userID() != '') {
            echo My::jsLoad('frontend-comment') .
                Html::jsJson(My::id() . 'reply', [
                    'input_text' => __('Respond'),
                    'response_text' => __('In response to a comment'),
                ]);
        }

        // edit
        if (!Wikibar::settings()->get('active')
            || !in_array(App::url()->getType(), ['post', My::id()])
        ) {
            return;
        }

        $settings = Wikibar::settings();
        // CSS
        if ($settings->add_css) {
            $custom_css = trim((string) $settings->custom_css);
            if ($custom_css !== '') {
                if (str_starts_with($custom_css, '/') || preg_match('!^https?://.+!', $custom_css)) {
                    // Absolute URL
                    $css_file = $custom_css;
                } else {
                    // Relative URL
                    $css_file = App::blog()->settings()->system->themes_url . '/' .
                    App::blog()->settings()->system->theme . '/' .
                        $custom_css;
                }

                $css = App::plugins()->cssLoad($css_file);
            } else {
                $css = Wikibar::cssLoad('wikibar.css');
            }

            echo $css;
        }

        // JS
        if ($settings->add_jslib) {
            $custom_jslib = trim((string) $settings->custom_jslib);
            if ($custom_jslib !== '') {
                if (str_starts_with($custom_jslib, '/') || preg_match('!^https?://.+!', $custom_jslib)) {
                    $js_file = $custom_jslib;
                } else {
                    $js_file = App::blog()->settings()->system->themes_url . '/' .
                    App::blog()->settings()->system->theme . '/' .
                        $custom_jslib;
                }

                $js = App::plugins()->jsLoad($js_file);
            } else {
                $js = Wikibar::jsLoad('wikibar.js');
            }

            echo $js;
        }

        if ($settings->add_jsglue) {
            // Force formatting Markdown
            $mode = 'markdown';

            echo
            Html::jsJson('commentswikibar', [
                'base_url'   => App::blog()->host(),
                'id'         => !empty($_POST['FrontendSessioncomment']) ? 'discussion_comment_content' : 'discussion_content',
                'mode'       => $mode,
                'legend_msg' => __('You can use the following shortcuts to format your text.'),
                'label'      => __('Text formatting'),
                'elements'   => [
                    'strong' => ['title' => __('Strong emphasis')],
                    'em'     => ['title' => __('Emphasis')],
                    'ins'    => ['title' => __('Inserted')],
                    'del'    => ['title' => __('Deleted')],
                    'quote'  => ['title' => __('Inline quote')],
                    'code'   => ['title' => __('Code')],
                    'br'     => ['title' => __('Line break')],
                    'ul'     => ['title' => __('Unordered list')],
                    'ol'     => ['title' => __('Ordered list')],
                    'pre'    => ['title' => __('Preformatted')],
                    'bquote' => ['title' => __('Block quote')],
                    'link'   => [
                        'title'           => __('Link'),
                        'href_prompt'     => __('URL?'),
                        'hreflang_prompt' => __('Language?'),
                        'title_prompt'    => __('Title?'),
                    ],
                ],
                'options' => [
                    'no_format' => $settings->no_format,
                    'no_br'     => $settings->no_br,
                    'no_list'   => $settings->no_list,
                    'no_pre'    => $settings->no_pre,
                    'no_quote'  => $settings->no_quote,
                    'no_url'    => $settings->no_url,
                ],
            ]) .
            Wikibar::jsLoad('bootstrap.js');
        }
    }

    /**
     * Edit post.
     */
    public static function FrontendSessionPostAction(MetaRecord $post): void
    {
        if (Core::canEditPost($post)) {
            $post_id = (int) $post->f('post_id');

            // update form
            if (!empty($_POST[My::id() . 'editpost'])) {
                echo (new Form('discussion-form'))
                    ->method('post')
                    ->action('#p' . $post_id)
                    ->items([
                        (new Div())
                            ->class(['inputfield', 'edit-entry'])
                            ->items([
                                (new Text('h5', __('Edit discussion:'))),
                                (new Textarea('discussion_content'))
                                    ->rows(15)
                                    ->value(Html::escapeHTML($post->f('post_content'))),
                            ]),
                        (new Div())
                            ->class('controlset')
                            ->separator(' ')
                            ->items([
                                (new Submit([My::id() . 'updatepost'], __('Update')))
                                    ->title(__('Save discussion modifications')),
                                (new Submit([My::id() . 'cancelpost'], __('Cancel'))),
                                (new Hidden(['FrontendSessioncheck'], App::nonce()->getNonce())),
                                (new Hidden(['FrontendSessionpost'], (string) $post_id)),
                            ]),
                    ])
                    ->render();
            // update action
            } elseif (!empty($_POST[My::id() . 'updatepost']) && !empty(trim($_POST['discussion_content'] ?? ''))) {
                FrontendUrl::loadFormater();

                $cur = App::blog()->openPostCursor();
                $cur->setField('post_content', $_POST['discussion_content']);
                $cur->setField('post_format', 'markdown');
                $cur->setField('post_lang', $post->f('post_lang'));
                $cur->setField('post_title', $post->f('post_title'));
                $cur->setField('post_dt', $post->f('post_dt'));
                $cur->setField('post_content_xhtml', null);

                App::auth()->sudo(App::blog()->updPost(...), $post_id, $cur);

                Http::redirect(
                    $post->getURL() . (App::blog()->settings()->get('system')->get('url_scan') == 'query_string' ? '&' : '?') . 'pupd=' . $post_id
                );
            }
        }
    }

    /**
     * Add edit button after post content.
     *
     * @param   ArrayObject<int, Submit>    $buttons
     */
    public static function FrontendSessionPostForm(MetaRecord $post, ArrayObject $buttons): void
    {
        if (empty($_POST[My::id() . 'editpost'])
            && Core::canEditPost($post)
        ) {
            $buttons->append(
                (new Submit([My::id() . 'editpost'], __('Edit')))
                    ->title(__('Edit my discussion'))
            );
        }
    }

    /**
     * Add succes message on post edition.
     */
    public static function publicEntryBeforeContent(): void
    {
        // succes message of post edition
        if (!empty($_REQUEST['pupd'])) {
                echo (new Div())
                    ->items([
                        (new Text('p', __('Discussion successfully updated')))
                            ->class('success')
                    ])
                    ->render();
        }
    }

    /**
     * Check if post is marked as resolved and add link to the comment.
     */
    public static function publicEntryAfterContent(): void
    {
        $meta = Core::getPostResolver((int) App::frontend()->context()->posts->f('post_id'));
        if (!$meta->isEmpty()) {
            echo (new Div())
                ->class('post-resolver')
                ->items([
                    (new Link())
                        ->href(App::frontend()->context()->posts->getURL() . '#c' . $meta->f('comment_id'))
                        ->text(sprintf(__('Discussion closed as it is resolved in comment from %s'), $meta->f('comment_author'))),
                    (new Text('', $meta->f('comment_content'))),
                ])
                ->render();
        }
    }

    /**
     * Mark post as resolved from an existing comment, and save comment edition.
     */
    public static function FrontendSessionCommentAction(MetaRecord $post, MetaRecord $comment): void
    {
        // Post resolved
        if (!empty($_POST['discussion_answer'])
            && $post->f('post_open_comment')
            && Core::isDiscussionCategory((int) $post->f('cat_id'))
        ) {
            Core::setPostResolver($post, (int) $comment->f('comment_id'));
            Http::redirect(Http::getSelfURI());
        }

        // Comment edition
        if (Core::canEditComment($post, $comment)) {
            $post_id         = (int) $post->f('post_id');
            $comment_id      = (int) $comment->f('comment_id');
            $comment_content = Markdown::fromHTML((string) $comment->f('comment_content'));

            // update comment form
            if (!empty($_POST[My::id() . 'editcomment'])) {
                echo (new Form('discussion-form'))
                    ->method('post')
                    ->action('#c' . $comment_id)
                    ->items([
                        (new Div())
                            ->class(['inputfield', 'edit-entry'])
                            ->items([
                                (new Text('h5', __('Edit comment:'))),
                                (new Textarea('discussion_comment_content'))
                                    ->rows(7)
                                    //->value(App::frontend()->context()->remove_html((string) $comment->f('comment_content'))),
                                    ->value(Html::escapeHTML((string) $comment_content)),
                            ]),
                        (new Div())
                            ->class('controlset')
                            ->separator(' ')
                            ->items([
                                (new Submit([My::id() . 'updatecomment'], __('Update')))
                                    ->title(__('Save comment modifications')),
                                (new Submit([My::id() . 'cancelcomment'], __('Cancel'))),
                                (new Hidden(['FrontendSessioncheck'], App::nonce()->getNonce())),
                                (new Hidden(['FrontendSessionpost'], (string) $post_id)),
                                (new Hidden(['FrontendSessioncomment'], (string) $comment_id)),
                            ]),
                    ])
                    ->render();
            // update comment action
            } elseif (!empty($_POST[My::id() . 'updatecomment']) && !empty(trim($_POST['discussion_comment_content'] ?? ''))) {
                FrontendUrl::loadFormater();

                $content = $_POST['discussion_comment_content'];

                # --BEHAVIOR-- publicBeforeCommentTransform -- string
                $buffer = App::behavior()->callBehavior('publicBeforeCommentTransform', $content);
                if ($buffer !== '') {
                    $content = $buffer;
                } else {
                    App::filter()->initWikiComment();
                    $content = App::filter()->wikiTransform($content);
                }
                $content = App::filter()->HTMLfilter($content);

                if ($content == '') {
                    return;
                }

                $cur = App::blog()->openCommentCursor();
                $cur->setField('comment_content', $content);

                App::auth()->sudo(App::blog()->updComment(...), $comment_id, $cur);

                Http::redirect(
                    $post->getURL() . (App::blog()->settings()->get('system')->get('url_scan') == 'query_string' ? '&' : '?') . 'cupd=' . $comment_id
                );
            }
        }
    }

    /**
     * Add form for resolver to existing comments.
     *
     * @param   ArrayObject<int, Submit>    $buttons
     */
    public static function FrontendSessionCommentForm(MetaRecord $post, MetaRecord $comment, ArrayObject $buttons): void
    {
        // Resolve button
        if ($post->f('post_open_comment')
            && Core::isDiscussionCategory($post->f('cat_id'))
            && Core::canResolvePost($post)
        ) {
            $buttons->append((new Submit(['discussion_answer'], __('Solution')))
                ->title(__('Mark this comment as answer and close discussion'))
            );
        }

        // Edit button
        if (empty($_POST[My::id() . 'editcomment'])
            && Core::canEditComment($post, $comment)
        ) {
            $buttons->append(
                (new Submit([My::id() . 'editcomment'], __('Edit')))
                    ->title(__('Edit my comment'))
            );
        }
    }

    /**
     * Add success message on comment edition.
     */
    public static function publicCommentBeforeContent(): void
    {
        // succes message of post edition
        if (($_REQUEST['cupd'] ?? '') == App::frontend()->context()->comments->f('comment_id')) {
                echo (new Div())
                    ->items([
                        (new Text('p', __('Comment successfully updated')))
                            ->class('success')
                    ])
                    ->render();
        }
    }

    /**
     * Add form for resolver to new comment.
     */
    public static function publicCommentFormAfterContent(): void
    {
        if (Core::canResolvePost(App::frontend()->context()->posts)) {
            echo (new Para())
                ->items([
                    (new Checkbox(My::id() . 'resolved', !empty($_POST[My::id() . 'resolved'])))
                        ->value(1)
                        ->label((new Label(__('Mark discussion as resolved'), Label::IL_FT))->title(__('Mark as resolved and close disscussion'))),
                ])
                ->render();
        }
    }

    /**
     * Mark post as resolved from a new comment.
     */
    public static function publicAfterCommentCreate(Cursor $cur, int $comment_id): void
    {
        if (!empty($_POST[My::id() . 'resolved'])) {
            Core::setPostResolver(App::frontend()->context()->posts, $comment_id);
        }
    }

    /**
     * Check if post comments are reopened.
     *
     * @param   ArrayObject<string, mixed>  $params
     */
    public static function publicPostBeforeGetPosts(ArrayObject $params, ?string $args): void
    {
        $rs = App::blog()->getPosts($params);
        if (!$rs->isEmpty() && $rs->f('post_open_comment')
            && Core::isDiscussionCategory((int) $rs->f('cat_id'))
            && !Core::getPostResolver((int) $rs->f('post_id'))->isEmpty()
        ) {
            Core::delPostResolver((int) $rs->f('post_id'));
        }
    }

    /**
     * Check comments perms.
     */
    public static function FrontendSessionCommentsActive(CommentOptions $option): void
    {
        // check if it is a discussion category else follow blog settings
        if (!is_null($option->rs) && Core::isDiscussionCategory((int) $option->rs->f('cat_id'))) {
            // active if user is auth or unregistered comments are allowed
            $option->setActive(App::auth()->check(My::id(), App::blog()->id()) || (bool) My::settings()->get('unregister_comment'));

            // not moderate if user is auth else follow blog settings
            if (App::auth()->check(My::id(), App::blog()->id())) {
                $option->setModerate(false);
            }
        }
    }

    /**
     * Add discussion menu to session page.
     */
    public static function FrontendSessionProfil(FrontendSessionProfil $profil): void
    {
        if (App::auth()->check(My::id(), App::blog()->id())) {
            $li  = fn (array $line): Li => (new Li())->items([(new Link())->href(App::blog()->url() . $line[0])->title($line[1])->text($line[2])]);
            $lines = [
                $li([App::url()->getURLFor(My::id(), 'create'), Html::escapeHTML(__('Create a new discussion')), Html::escapeHTML(__('New discussion'))]),
                $li([App::url()->getURLFor(My::id(), 'posts'), Html::escapeHTML(__('View my discussions')), __('My discussions')]),
            ];

            $profil->addAction(My::id(), My::name(), [
                (new Text('p', __('You can paticipate in discussions.'))),
                (new Ul())->items($lines),
            ]);
        }
    }

    /**
     * Add discussion menu to session widget.
     *
     * @param   ArrayObject<int, Li>    $lines
     */
    public static function FrontendSessionWidget(ArrayObject $lines): void
    {
        if (App::auth()->check(My::id(), App::blog()->id())) {
            $li  = fn (array $line): Li => (new Li())->items([(new Link())->href(App::blog()->url() . $line[0])->title($line[1])->text($line[2])]);
 
            $lines->append($li([App::url()->getURLFor(My::id(), 'create'), Html::escapeHTML(__('Create a new discussion')), __('New discussion')]));
            $lines->append($li([App::url()->getURLFor(My::id(), 'posts'), Html::escapeHTML(__('View my discussions')), __('My discussions')]));
        }
    }

    /**
     * Add user permission after registration.
     */
    public static function FrontendSessionAfterSignup(Cursor $cur): void
    {
        $perms = App::users()->getUserPermissions($cur->user_id);
        $perms = $perms[App::blog()->id()]['p'] ?? [];
        $perms[My::id()]  = true;
        App::auth()->sudo([App::users(), 'setUserBlogPermissions'], $cur->user_id, App::blog()->id(), $perms);
    }

    /**
     * Add Discussion URL type to plugin ReadingTracking.
     *
     * @param   ArrayObject<int, string> $types
     */
    public static function ReadingTrackingUrlTypes(ArrayObject $types): void
    {
        $types->append(My::id());
    }

    /**
     * Add Discussion name to breadcrumb.
     */
    public static function publicBreadcrumb(string $context, string $separator): string
    {
        return $context == My::id() ? My::name() : '';
    }

    /**
     * Init wiki syntax for post form.
     */
    public static function coreInitWikiPost(WikiToHtml $wiki): string
    {
        if (App::url()->getType() != My::id()) {
            return '';
        }

        $settings = Wikibar::settings();
        if ($settings->no_format) {
            $wiki->setOpt('active_strong', 0);
            $wiki->setOpt('active_em', 0);
            $wiki->setOpt('active_ins', 0);
            $wiki->setOpt('active_del', 0);
            $wiki->setOpt('active_q', 0);
            $wiki->setOpt('active_code', 0);
        }

        if ($settings->no_br) {
            $wiki->setOpt('active_br', 0);
        }

        if ($settings->no_list) {
            $wiki->setOpt('active_lists', 0);
        }

        if ($settings->no_pre) {
            $wiki->setOpt('active_pre', 0);
        }

        if ($settings->no_quote) {
            $wiki->setOpt('active_quote', 0);
        } elseif (App::blog()->settings()->system->wiki_comments) {
            //$wiki->setOpt('active_quote', 1);
        }

        if ($settings->no_url) {
            $wiki->setOpt('active_urls', 0);
        }

        return '';
    }

    /**
     * Check if current category is Root category and serve categories template.
     *
     * @param   ArrayObject<string, mixed>  $params
     */
    public static function publicCategoryBeforeGetCategories(ArrayObject $params, ?string $args): void
    {
        App::frontend()->context()->categories = App::blog()->getCategories($params);
        if (!App::frontend()->context()->categories->isEmpty()
            && Core::isRootCategory(App::frontend()->context()->categories->f('cat_id'))
        ) {
            FrontendUrl::serveTemplate('categories');
            exit;
        }
    }

    /**
     * Put selected post on first then by upddt on category page.
     *
     * @param   array<string, string>       $tpl
     * @param   ArrayObject<string, mixed>  $attr
     */
    public static function templatePrepareParams(array $tpl, ArrayObject $attr, string $content): string
    {
        if ($tpl['tag'] == 'Entries' 
            && $tpl['method'] == 'blog::getPosts'
            && in_array(App::url()->getType(), ['category'])
        ) {
            return 
                "if (". Core::class . "::isDiscussionCategory(App::frontend()->context()->categories->cat_id)){" .
                "\$params['order'] = 'post_selected DESC, post_upddt DESC' . (!empty(\$params['order']) ? ', ' . \$params['order'] : '');" .
                "}\n";
        }

        return '';
    }
}
