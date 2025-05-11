<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\{ Cursor, MetaRecord };
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Html\Form\{ Checkbox, Form, Hidden, Label, Li, Link, Para, Submit, Text, Ul };
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\WikiToHtml;
use Dotclear\Plugin\commentsWikibar\My as Wb;
use Dotclear\Plugin\FrontendSession\CommentOptions;

/**
 * @brief       Discussion module frontend behaviors.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendBehaviors
{
    public static function publicPostBeforeGetPosts(ArrayObject $params, $args): void
    {
        if (!empty($_POST['discussion_comment'])) {
            FrontendUrl::checkForm();

            $comment_id = (int) $_POST['discussion_comment'];
            $rs = App::blog()->getPosts($params);
            if (!$rs->isEmpty()) {
                FrontendUrl::loadFormater();
                $text = match ($rs->f('post_format')) {
                    'wiki'  => "\n\n''[%s|%s]''",
                    default => "\n\n%s",
                };

                $cur = App::blog()->openPostCursor();
                $cur->setField('post_open_comment', 0);
                $cur->setField('post_title', sprintf('[%s] ', __('Resolved')) . $rs->f('post_title'));
                $cur->setField('post_lang', $rs->f('post_lang'));
                $cur->setField('post_format', $rs->f('post_format'));
                $cur->setField('post_content', $rs->f('post_content') . sprintf(
                    $text,
                    __('Discussion closed as it is resolved in comments'),
                    $rs->getURL() . '#c' . $comment_id
                ));

                App::auth()->sudo(App::blog()->updPost(...), $rs->f('post_id'), $cur);
            }
        }
    }

    public static function publicHeadContent(): void
    {
        $tplset = App::themes()->moduleInfo(App::blog()->settings()->get('system')->get('theme'), 'tplset');
        if (in_array($tplset, ['dotty', 'mustek'])) {
            echo My::cssLoad('frontend-' . $tplset);
        }

        // wiki, taken from plugin commentsWikibar
        if (!App::plugins()->moduleExists('commentsWikibar')
            || !Wb::settings()->get('active')
            || App::url()->getType() != My::id()
        ) {
            return;
        }

        $settings = Wb::settings();
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
                $css = Wb::cssLoad('wikibar.css');
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
                $js = Wb::jsLoad('wikibar.js');
            }

            echo $js;
        }

        if ($settings->add_jsglue) {
            $mode = 'wiki';
            // Formatting Markdown activated
            if (App::blog()->settings()->system->markdown_comments) {
                $mode = 'markdown';
            }

            echo
            Html::jsJson('commentswikibar', [
                'base_url'   => App::blog()->host(),
                'id'         => 'discussion_content',
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
            Wb::jsLoad('bootstrap.js');
        }
    }

    public static function publicCommentAfterContent(): void
    {
        if (App::auth()->userID() === App::frontend()->context()->posts->f('user_id')) {
            echo (new Form(My::id(). App::frontend()->context()->comments->f('comment_id')))
                ->method('post')
                ->action('')
                ->class(['post-comment-answer', 'button'])
                ->fields([
                    (new Submit(['discussion_answer'], __('Solution'))
                        ->title('Mark this comment as answer and close discussion')),
                    (new Hidden(['discussion_check'], App::nonce()->getNonce())),
                    (new Hidden(['discussion_comment'], App::frontend()->context()->comments->f('comment_id'))),
                ])
                ->render();
        }
    }

    public static function publicCommentFormAfterContent(): void
    {
        if (App::auth()->userID() === App::frontend()->context()->posts->f('user_id')) {
            echo (new Para())
                ->items([
                    (new Checkbox(My::id() . 'resolved', !empty($_POST[My::id() . 'resolved'])))
                        ->value(1)
                        ->label((new Label(__('Resolved'), Label::IL_FT))->title(__('Mark as resolved and close disscussion'))),
                ])
                ->render();
        }
    }

    public static function publicAfterCommentCreate(Cursor $cur, $comment_id)
    {
        if (App::auth()->userID() === App::frontend()->context()->posts->f('user_id') && !empty($_POST[My::id() . 'resolved'])) {
            $cur = App::blog()->openPostCursor();
            $cur->setField('post_open_comment', 0);
            $cur->setField('post_title', sprintf('[%s] ', __('Resolved')) . App::frontend()->context()->posts->f('post_title'));

            $sql = new UpdateStatement();
            $sql
                ->where('blog_id = ' . $sql->quote(App::blog()->id()))
                ->and('post_id = ' . App::frontend()->context()->posts->f('post_id'))
                ->update($cur);
        }
    }

    public static function FrontendSessionPage(): void
    {
        if (App::auth()->check(My::id(), App::blog()->id())) {
            $li  = fn (array $line): Li => (new Li())->items([(new Link())->href(App::blog()->url() . $line[0])->title($line[1])->text($line[2])]);
            $lines = [
                //$li([App::url()->getURLFor(My::id(), 'mine'), Html::escapeHTML(__('View my discussions')), Html::escapeHTML(__('My discussions'))]),
                $li([App::url()->getURLFor(My::id(), 'create'), Html::escapeHTML(__('Create a new discussion')), Html::escapeHTML(__('New discussion'))]),
                $li([App::url()->getURLFor(My::id(), 'posts'), Html::escapeHTML(__('View my discussions')), __('My discussions')]),
            ];

            echo (new Para())
                ->items([
                    (new Text('h3', __('Discussion'))),
                    (new Text('p', __('You can paticipate in discussions.'))),
                    (new Ul())->items($lines),
                ])
                ->render();
        }
    }

    /**
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
        if (My::conf()->isActive('signup')) {
            $perms = App::users()->getUserPermissions($cur->user_id);
            $perms = $perms[App::blog()->id()]['p'] ?? [];
            $perms[My::id()]  = true;
            App::auth()->sudo([App::users(), 'setUserBlogPermissions'], $cur->user_id, App::blog()->id(), $perms);
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
     * @param   ArrayObject<int, string> $types
     */
    public static function ReadingTrackingUrlTypes(ArrayObject $types): void
    {
        $types->append(My::id());
    }

    public static function publicBreadcrumb(string $context, string $separator): string
    {
        return $context == My::id() ? My::name() : '';
    }

    public static function coreInitWikiPost(WikiToHtml $wiki): string
    {
        if (!App::plugins()->moduleExists('commentsWikibar')
            || !Wb::settings()->get('active')
            || App::url()->getType() != My::id()
        ) {
            return '';
        }

        $settings = Wb::settings();
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
            $wiki->setOpt('active_quote', 1);
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
