<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Ctx;
use Dotclear\Database\{ Cursor, MetaRecord };
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Form, Hidden, Label, Li, Link, Para, Submit, Text, Textarea, Ul };
use Dotclear\Helper\Html\{ Html};
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\commentsWikibar\My as Wikibar;
use Dotclear\Plugin\commentsWikibar\FrontendBehaviors as WikibarHelper;
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
     * Tools fo wikibar.
     *
     * @param   ArrayObject<array-key, string>  $supported_modes
     */
    public static function initCommentsWikibar(ArrayObject $supported_modes): string
    {
        $supported_modes->append('Discussion');

        return '';
    }

    /**
     * Overload discussion post template.
     */
    public static function urlHandlerBeforeGetData(Ctx $ctx): void
    {
        if (!self::$loop && str_ends_with($ctx?->content_type, 'xml') && $ctx->exists('posts') && $ctx->posts instanceof MetaRecord) {
            $cat_id = is_numeric($cat_id = $ctx->posts->f('cat_id')) ? (int) $cat_id : 0;
            if (Core::isDiscussionCategory($cat_id)) {
                self::$loop = true;
                FrontendUrl::serveTemplate('post');
                exit();
            }
        }
    }

    /**
     * Load JS and CSS and add wiki bar to post form.
     */
    public static function publicHeadContent(): void
    {
        // style
        $theme  = is_string($theme = App::blog()->settings()->system->theme) ? $theme : '';
        $tplset = is_string($tplset = App::themes()->moduleInfo($theme, 'tplset')) ? $tplset : '';
        if (in_array($tplset, ['dotty', 'mustek'], true)) {
            echo My::cssLoad('frontend-' . $tplset);
        }

        // resolve
        if (Core::getPostArtifact() !== '') {
            echo My::jsLoad('frontend-post') .
                Html::jsJson(My::id() . 'resolver', [
                    'url' => App::blog()->url() . App::url()->getBase(My::id()),
                ]);
        }

        // reply
        if (App::auth()->userID() != '') {
            $syntax = 'html';
            if (App::blog()->settings()->get('system')->get('wiki_comments')) {
                $syntax = 'wiki';
                if (App::blog()->settings()->get('system')->get('markdown_comments')) {
                    $syntax = 'markdown';
                }
            }

            echo My::jsLoad('frontend-comment') .
                Html::jsJson(My::id() . 'reply', [
                    'input_text'    => __('Respond'),
                    'response_text' => __('In response to a comment'),
                    'syntax'        => $syntax,
                ]);
        }

        // edit
        if (!Wikibar::settings()->get('active')
            || !in_array(App::url()->getType(), ['post', My::id()])
        ) {
            return;
        }

        WikibarHelper::publicHeadContentHelper(empty($_POST['FrontendSessioncomment']) ? 'discussion_content' : 'discussion_comment_content');
    }

    /**
     * Edit post.
     */
    public static function FrontendSessionPostAction(MetaRecord $post): void
    {
        // Check rights
        if (Core::canEditPost($post)) {
            $post_id = is_numeric($post_id = $post->f('post_id')) ? (int) $post_id : 0;

            // update form
            if (!empty($_POST[My::id() . 'editpost'])) {
                $content = is_string($content = $post->f('post_content')) ? $content : '';

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
                                    ->value(Html::escapeHTML($content)),
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
            } elseif (!empty($_POST[My::id() . 'updatepost'])) {
                $content = isset($_POST['discussion_content']) && is_string($content = $_POST['discussion_content']) ? trim($content) : '';
                if ($content !== '') {
                    FrontendUrl::loadFormater();

                    $cur = App::blog()->openPostCursor();
                    $cur->setField('post_content', $_POST['discussion_content']);
                    $cur->setField('post_format', 'markdown');
                    $cur->setField('post_lang', $post->f('post_lang'));
                    $cur->setField('post_title', $post->f('post_title'));
                    $cur->setField('post_dt', $post->f('post_dt'));
                    $cur->setField('post_content_xhtml', null);

                    App::auth()->sudo(App::blog()->updPost(...), $post_id, $cur);

                    $url_scan = is_string($url_scan = App::blog()->settings()->get('system')->get('url_scan')) ? $url_scan : '';
                    $post_url = is_string($post_url = $post->getURL()) ? $post_url : '';

                    Http::redirect($post_url . ($url_scan === 'query_string' ? '&' : '?') . 'pupd=' . $post_id);
                }
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
                        ->class('success'),
                ])
                ->render();
        }
    }

    /**
     * Check if post is marked as resolved and add link to the comment.
     */
    public static function publicEntryAfterContent(): void
    {
        if (!App::frontend()->context()->exists('posts') || !App::frontend()->context()->posts instanceof MetaRecord) {
            return;
        }

        $post_id = is_numeric($post_id = App::frontend()->context()->posts->f('post_id')) ? (int) $post_id : 0;
        if ($post_id === 0) {
            return;
        }

        $meta = Core::getPostResolver($post_id);
        if (!$meta->isEmpty()) {
            $post_url = is_string($post_url = App::frontend()->context()->posts->getURL()) ? $post_url : '';

            $comment_id      = is_numeric($comment_id = $meta->f('comment_id')) ? (int) $comment_id : 0;
            $comment_author  = is_string($comment_author = $meta->f('comment_author')) ? $comment_author : '';
            $comment_content = is_string($comment_content = $meta->f('comment_content')) ? $comment_content : '';

            echo (new Div())
                ->class('post-resolver')
                ->items([
                    (new Link())
                        ->href($post_url . '#c' . $comment_id)
                        ->text(sprintf(__('Discussion closed as it is resolved in comment from %s'), $comment_author)),
                    (new Text('', $comment_content)),
                ])
                ->render();
        }
    }

    /**
     * Mark post as resolved from an existing comment, and save comment edition.
     */
    public static function FrontendSessionCommentAction(MetaRecord $post, MetaRecord $comment): void
    {
        $cat_id = is_numeric($cat_id = $post->f('cat_id')) ? (int) $cat_id : 0;

        // Post resolved
        if (!empty($_POST['discussion_answer'])
            && $post->f('post_open_comment')
            && Core::isDiscussionCategory($cat_id)
        ) {
            $comment_id = is_numeric($comment_id = $comment->f('comment_id')) ? (int) $comment_id : 0;

            Core::setPostResolver($post, $comment_id);
            Http::redirect(Http::getSelfURI());
        }

        // Comment edition
        if (Core::canEditComment($post, $comment)) {
            $post_id         = is_numeric($post_id = $post->f('post_id')) ? (int) $post_id : 0;
            $comment_id      = is_numeric($comment_id = $comment->f('comment_id')) ? (int) $comment_id : 0;
            $comment_content = is_string($comment_content = $comment->f('comment_content')) ? Markdown::fromHTML($comment_content) : '';

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
                                    ->value(Html::escapeHTML($comment_content)),
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
            } elseif (!empty($_POST[My::id() . 'updatecomment'])) {
                $content = isset($_POST['discussion_comment_content']) && is_string($content = $_POST['discussion_comment_content']) ? trim($content) : '';
                if ($content !== '') {
                    FrontendUrl::loadFormater();

                    # --BEHAVIOR-- publicBeforeCommentTransform -- string
                    $buffer = App::behavior()->callBehavior('publicBeforeCommentTransform', $content);
                    if ($buffer !== '') {
                        $content = $buffer;
                    } else {
                        App::filter()->initWikiComment();
                        $content = App::filter()->wikiTransform($content);
                    }

                    $content = App::filter()->HTMLfilter($content);

                    if ($content === '') {
                        return;
                    }

                    $cur = App::blog()->openCommentCursor();
                    $cur->setField('comment_content', $content);

                    App::auth()->sudo(App::blog()->updComment(...), $comment_id, $cur);

                    $url_scan = is_string($url_scan = App::blog()->settings()->get('system')->get('url_scan')) ? $url_scan : '';
                    $post_url = is_string($post_url = $post->getURL()) ? $post_url : '';

                    Http::redirect($post_url . ($url_scan === 'query_string' ? '&' : '?') . 'cupd=' . $comment_id);
                }
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
        $cat_id = is_numeric($cat_id = $post->f('cat_id')) ? (int) $cat_id : 0;

        // Resolve button
        if ($post->f('post_open_comment')
            && Core::isDiscussionCategory($cat_id)
            && Core::canResolvePost($post)
        ) {
            $buttons->append(
                (new Submit(['discussion_answer'], __('Solution')))
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
        if (App::frontend()->context()->comments instanceof MetaRecord) {
            $cupd       = isset($_REQUEST['cupd']) && is_numeric($cupd = $_REQUEST['cupd']) ? (int) $cupd : 0;
            $comment_id = is_numeric($comment_id = App::frontend()->context()->comments->f('comment_id')) ? (int) $comment_id : 0;

            if ($cupd !== 0 && $cupd === $comment_id) {
                // succes message of post edition
                echo (new Div())
                    ->items([
                        (new Text('p', __('Comment successfully updated')))
                            ->class('success'),
                    ])
                    ->render();
            }
        }
    }

    /**
     * Add form for resolver to new comment.
     */
    public static function publicCommentFormAfterContent(): void
    {
        if (App::frontend()->context()->exists('posts')
            && App::frontend()->context()->posts instanceof MetaRecord
            && Core::canResolvePost(App::frontend()->context()->posts)
        ) {
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
        if (App::frontend()->context()->exists('posts')
            && App::frontend()->context()->posts instanceof MetaRecord
            && !empty($_POST[My::id() . 'resolved'])
        ) {
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
        if (!$rs->isEmpty()) {
            $cat_id  = is_numeric($cat_id = $rs->f('cat_id')) ? (int) $cat_id : 0;
            $post_id = is_numeric($post_id = $rs->f('post_id')) ? (int) $post_id : 0;

            if ($rs->f('post_open_comment')
                && Core::isDiscussionCategory($cat_id)
                && !Core::getPostResolver($post_id)->isEmpty()
            ) {
                Core::delPostResolver($post_id);
            }
        }
    }

    /**
     * Check comments perms.
     */
    public static function FrontendSessionCommentsActive(CommentOptions $option): void
    {
        if ($option->rs instanceof MetaRecord) {
            $cat_id = is_numeric($cat_id = $option->rs->f('cat_id')) ? (int) $cat_id : 0;

            // check if it is a discussion category else follow blog settings
            if (Core::isDiscussionCategory($cat_id)) {
                // active if user is auth or unregistered comments are allowed
                $option->setActive(App::auth()->check(My::id(), App::blog()->id()) || (bool) My::settings()->get('unregister_comment'));

                // not moderate if user is auth else follow blog settings
                if (App::auth()->check(My::id(), App::blog()->id())) {
                    $option->setModerate(false);
                }
            }
        }
    }

    /**
     * Add discussion menu to session page.
     */
    public static function FrontendSessionProfil(FrontendSessionProfil $profil): void
    {
        if (App::auth()->check(My::id(), App::blog()->id())) {
            $li = fn (string $url, string $title, string $text): Li => (new Li())
                ->items([
                    (new Link())
                        ->href(App::blog()->url() . $url)
                        ->title($title)
                        ->text($text),
                ]);

            $profil->addAction(My::id(), My::name(), [
                (new Text('p', __('You can paticipate in discussions.'))),
                (new Ul())
                    ->items([
                        $li(App::url()->getURLFor(My::id(), 'create'), Html::escapeHTML(__('Create a new discussion')), Html::escapeHTML(__('New discussion'))),
                        $li(App::url()->getURLFor(My::id(), 'posts'), Html::escapeHTML(__('View my discussions')), __('My discussions')),
                    ]),
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
            $li = fn (string $url, string $title, string $text): Li => (new Li())
                ->items([
                    (new Link())
                        ->href(App::blog()->url() . $url)
                        ->title($title)
                        ->text($text),
                ]);

            $lines->append($li(App::url()->getURLFor(My::id(), 'create'), Html::escapeHTML(__('Create a new discussion')), __('New discussion')));
            $lines->append($li(App::url()->getURLFor(My::id(), 'posts'), Html::escapeHTML(__('View my discussions')), __('My discussions')));
        }
    }

    /**
     * Add user permission after registration.
     */
    public static function FrontendSessionAfterSignup(Cursor $cur): void
    {
        $user_id = is_string($user_id = $cur->user_id) ? $user_id : '';

        if ($user_id !== '') {
            $perms           = App::users()->getUserPermissions($user_id);
            $perms           = $perms[App::blog()->id()]['p'] ?? [];
            $perms[My::id()] = true;
            App::auth()->sudo([App::users(), 'setUserBlogPermissions'], $cur->user_id, App::blog()->id(), $perms);
        }
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
     * Check if current category is Root category and serve categories template.
     *
     * @param   ArrayObject<string, mixed>  $params
     */
    public static function publicCategoryBeforeGetCategories(ArrayObject $params, ?string $args): void
    {
        App::frontend()->context()->categories = App::blog()->getCategories($params);
        if (!App::frontend()->context()->categories->isEmpty()) {
            $cat_id = is_numeric($cat_id = App::frontend()->context()->categories->f('cat_id')) ? (int) $cat_id : 0;
            if (Core::isRootCategory($cat_id)) {
                FrontendUrl::serveTemplate('categories');
                exit;
            }

            if (Core::isDiscussionCategory($cat_id)) {
                FrontendUrl::serveTemplate('category');
                exit;
            }
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
        if ($tpl['tag']              === 'Entries'
            && $tpl['method']        === 'blog::getPosts'
            && App::url()->getType() === 'category'
        ) {
            return
                'if (' . Core::class . '::isDiscussionCategory(App::frontend()->context()->categories->cat_id)){' .
                "\$params['order'] = 'post_selected DESC, post_upddt DESC' . (!empty(\$params['order']) ? ', ' . \$params['order'] : '');" .
                "}\n";
        }

        return '';
    }
}
