<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\legacyMarkdown\Helper as Markdown;
use Exception;

/**
 * @brief       Discussion module URL handler.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendUrl
{
    /**
     * Form errors.
     *
     * @var     array<int, string>  $form_error
     */
    private static array $form_error = [];

    /**
     * Discussion creation endpoint.
     */
    public static function discussionEndpoint(?string $args): void
    {
        $args = (string) $args;
        if (str_starts_with($args, '/')) {
            $args = substr($args, 1);
        }

        $exp = explode('/', $args);

        switch ($exp[0]) {
            case 'create':
                self::create($exp);

                break;
            case 'posts':
                self::posts($exp);

                break;
            case 'comments':
                self::comments($exp);

                break;

            case 'resolver':
                self::resolver($exp);

                break;

            default:
                $exp[0] = 'categories';
                self::categories($exp);

                break;
        }
    }

    /**
     * Discussion creation endpoint.
     *
     * @param   array<int, string>  $args
     */
    public static function create(array $args): void
    {
        if (!My::settings()->get('active')
            || App::auth()->userID() == ''
            || !App::auth()->check(My::id(), App::blog()->id())
        ) {
            App::url()::p404();
        }
        // from URL
        $post_id  = 0;
        $post_cat = 0;
        foreach ($args as $k => $arg) {
            if ($arg === 'post' && isset($args[$k + 1]) && is_numeric($args[$k + 1])) {
                App::frontend()->context()->discussion_success = __('Discussion successfully created.');
                if (My::settings()->get('publish_post')) {
                    $post_id = (int) $args[$k + 1];
                }
            }

            if ($arg === 'category' && isset($args[$k + 1]) && is_numeric($args[$k + 1])) {
                $cat_id = (int) $args[$k + 1];
                if (Core::isDiscussionCategory($cat_id)) {
                    App::frontend()->context()->categories = App::blog()->getCategories(['cat_id' => $cat_id]);

                    $post_cat = $cat_id;
                }
            }
        }

        // post content format force to markdown
        $post_format = 'markdown';

        // preview
        $init_preview = [
            'title'      => '',
            'content'    => '',
            'rawcontent' => '',
            'preview'    => false,
        ];
        App::frontend()->context()->post_preview = new ArrayObject($init_preview);

        self::loadFormater();

        if ($_POST !== []) {
            // Post data helpers
            $_Bool = fn (string $name): bool => !empty($_POST[$name]);
            $_Int  = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
            $_Str  = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

            Core::checkForm();

            $preview      = $_Bool('discussion_preview');
            $post_cat     = $_Int('discussion_category', $post_cat);
            $post_title   = trim($_Str('discussion_title'));
            $post_content = trim($_Str('discussion_content'));

            if ($post_cat === 0) {
                self::$form_error[] = __('You must select a category.');
            }

            if ($post_title === '') {
                self::$form_error[] = __('You must set a discussion title.');
            }

            if ($post_content === '') {
                self::$form_error[] = __('You must set a discussion content.');
            }

            if (self::$form_error === [] && $preview) {
                $content = App::formater()->callEditorFormater('dcLegacyEditor', $post_format, $post_content);
                $content = App::filter()->HTMLfilter($content);

                App::frontend()->context()->post_preview['title']      = $post_title;
                App::frontend()->context()->post_preview['content']    = $content;
                App::frontend()->context()->post_preview['rawcontent'] = $post_content;
                App::frontend()->context()->post_preview['preview']    = true;
            } elseif (self::$form_error === [] && !$preview) {
                try {
                    $cur = App::blog()->openPostCursor();
                    $cur->setField('user_id', App::auth()->userID());
                    $cur->setField('post_status', My::settings()->get('publish_post') ? App::status()->post()::PUBLISHED : App::status()->post()::PENDING);
                    $cur->setField('post_title', $post_title);
                    $cur->setField('post_content', $post_content);
                    $cur->setField('post_format', $post_format);
                    $cur->setField('post_lang', App::blog()->settings()->get('system')->get('lang'));
                    $cur->setField('post_open_comment', 1);
                    $cur->setField('cat_id', $post_cat);

                    $post_id = is_numeric($post_id = App::auth()->sudo(App::blog()->addPost(...), $cur)) ? (int) $post_id : 0;

                    $more = '/post/' . (My::settings()->get('publish_post') ? $post_id : '0') . '/category/' . $post_cat;

                    header('Location: ' . App::blog()->url() . App::url()->getURLFor(My::id(), 'create') . $more);
                } catch (Exception $e) {
                    self::$form_error[] = $e->getMessage();
                }
            }
        }

        // Need to have a posts instance for templates
        App::frontend()->context()->posts = App::blog()->getPosts(['post_id' => $post_id]);

        self::serveTemplate('create');
    }

    /**
     * Discussion user posts list endpoint.
     *
     * @param   array<int, string>  $args
     */
    public static function posts(array $args): void
    {
        if (!My::settings()->get('active')
            || App::auth()->userID() == ''
            || !App::auth()->check(My::id(), App::blog()->id())
        ) {
            App::url()::p404();
        }

        $uri  = implode('/', $args);
        $page = App::url()::getPageNumber($uri) ?: 1;
        App::frontend()->setPageNumber($page);

        $nbpp = is_numeric($nbpp = App::blog()->settings()->get('system')->get('nb_post_per_page')) ? (int) $nbpp : 20;

        App::frontend()->context()->__set('nb_entry_first_page', $nbpp);
        App::frontend()->context()->__set('nb_entry_per_page', $nbpp);

        self::serveTemplate('posts');
    }

    /**
     * Discussion user comments list endpoint.
     *
     * @param   array<int, string>  $args
     */
    public static function comments(array $args): void
    {
        App::url()::p404();
    }

    /**
     * Discussion categories endpoint.
     *
     * @param   array<int, string>  $args
     */
    public static function categories(array $args): void
    {
        self::serveTemplate('categories');
    }

    /**
     * Resolver (post title) endpoint.
     *
     * @param   array<int, string>  $args
     */
    public static function resolver(array $args): void
    {
        $rsp      = '';
        $post_id  = (int) ($args[1] ?? 0);
        $artifact = Core::getPostArtifact();
        if ($post_id && $artifact !== '') {
            $rsp = Core::getPostResolver($post_id)->isEmpty() ? '' : $artifact;
        }

        Http::head(200);
        header('Content-type: application/json');
        echo json_encode([
            'ret' => $rsp,
        ]);
        exit;
    }

    /**
     * Load formater.
     */
    public static function loadFormater(): void
    {
        // init wiki transform
        if (!App::filter()->wiki()) {
            App::filter()->initWikiPost();
        }

        if (App::filter()->wiki()) {
            // add wiki tranform capabilities for submission
            App::formater()->addEditorFormater('dcLegacyEditor', 'wiki', App::filter()->wiki()->transform(...));
        }

        // add markdown tranform capabilities for submission
        /* @phpstan-ignore-next-line */
        App::formater()->addEditorFormater('dcLegacyEditor', 'markdown', Markdown::convert(...));
    }

    /**
     * Serve template.
     */
    public static function serveTemplate(string $template): void
    {
        // use only dotty tplset
        $theme  = is_string($theme = App::blog()->settings()->system->theme) ? $theme : '';
        $tplset = is_string($tplset = App::themes()->moduleInfo($theme, 'tplset')) ? $tplset : '';
        if (!in_array($tplset, ['dotty', 'mustek'], true)) {
            App::url()::p404();
        }

        if (self::$form_error !== []) {
            App::frontend()->context()->form_error = implode("\n", self::$form_error);
        }

        $root             = is_string($root = App::plugins()->moduleInfo(My::id(), 'root')) ? $root : '';
        $default_template = Path::real($root) . DIRECTORY_SEPARATOR . App::frontend()::TPL_ROOT . DIRECTORY_SEPARATOR;
        if (is_dir($default_template . $tplset)) {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
        }

        App::url()::serveDocument(My::id() . '-' . $template . '.html');
    }
}
