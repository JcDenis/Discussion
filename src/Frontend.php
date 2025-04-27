<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{ Li, Link, Para, Text, Ul };
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\WikiToHtml;
use Dotclear\Helper\L10n;
use Dotclear\Plugin\commentsWikibar\My as Wb;

/**
 * @brief       Discussion module frontend process.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status() || !My::settings()->get('active')) {
            return false;
        }

        App::frontend()->template()->addBlock('DiscussionIf', FrontendTemplate::DiscussionIf(...));
        App::frontend()->template()->addValue('DiscussionFormNonce', FrontendTemplate::DiscussionFormNonce(...));
        App::frontend()->template()->addValue('DiscussionFormURL', FrontendTemplate::DiscussionFormURL(...));
        App::frontend()->template()->addValue('DiscussionFormSuccess', FrontendTemplate::DiscussionFormSuccess(...));
        App::frontend()->template()->addValue('DiscussionPostTitle', FrontendTemplate::DiscussionPostTitle(...));
        App::frontend()->template()->addValue('DiscussionPostContent', FrontendTemplate::DiscussionPostContent(...));
        App::frontend()->template()->addValue('DiscussionCategoriesCombo', FrontendTemplate::DiscussionCategoriesCombo(...));

        App::behavior()->addBehaviors([
            'publicHeadContent' => function (): void {
                $tplset = App::themes()->moduleInfo(App::blog()->settings()->get('system')->get('theme'), 'tplset');
                if (in_array($tplset, ['dotty', 'mustek'])) {
                    echo My::cssLoad('frontend-' . $tplset) . My::jsLoad('frontend');
                }

                self::commentsWikiBarPublicHeadContent();
            },
            'publicFrontendSessionPage' => function (): void {
                if (App::auth()->check(My::id(), App::blog()->id())) {
                    $li  = fn (array $line): Li => (new Li())->items([(new Link())->href(App::blog()->url() . $line[0])->title($line[1])->text($line[2])]);
                    $lines = [
                        $li([App::url()->getURLFor(My::id(), 'list'), Html::escapeHTML(__('View my discussions')), Html::escapeHTML(__('My discussions'))]),
                        $li([App::url()->getURLFor(My::id(), 'create'), Html::escapeHTML(__('Create a new discussion')), Html::escapeHTML(__('New discussion'))]),
                    ];

                    echo (new Para())
                        ->items([
                            (new Text('h3', __('Discussion'))),
                            (new Text('p', __('You can create discussions.'))),
                            (new Ul())->items($lines),
                        ])
                        ->render();
                }
            },
            'publicFrontendSessionWidget' => function (ArrayObject $lines): void {
                if (App::auth()->check(My::id(), App::blog()->id())) {
                    $li  = fn (array $line): Li => (new Li())->items([(new Link())->href(App::blog()->url() . $line[0])->title($line[1])->text($line[2])]);
                    $url = App::url()->getURLFor(My::id(), 'create');

                    $lines->append($li([$url, Html::escapeHTML(__('Create a new discussion')), __('New Discussion')]));
                }
            },
            'coreInitWikiPost' => self::commentsWikibarCoreInitWikiPost(...),

        ]);
        return true;
    }

    public static function commentsWikiBarPublicHeadContent(): string
    {
        if (!App::plugins()->moduleExists('commentsWikibar')
            || !Wb::settings()->get('active')
            || App::url()->getType() != My::id()
        ) {
            return '';
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

        return '';
    }

    public static function commentsWikibarCoreInitWikiPost(WikiToHtml $wiki): string
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
}
