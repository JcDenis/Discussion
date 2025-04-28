<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Exception\PreconditionException;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Text;
use Throwable;

/**
 * @brief       Discussion module URL handler.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendUrl extends Url
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
        $exp = explode('/', (string) $args);

        switch ($exp[0]) {
            case 'create':
                self::create($exp);
                break;
            case 'mylist':
                self::mylist($exp);
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
            self::p404();
        }

        //self::loadFormater();

        if (!empty($_POST)) {
            self::checkForm();

            $post_cat     = (int) $_POST['discussion_category'] ?? 0;
            $post_title   = trim($_POST['discussion_title'] ?? '');
            $post_content = trim($_POST['discussion_content'] ?? '');

            if (empty($post_cat) || !Core::isDiscussionCategory($post_cat)) {
                self::$form_error[] = __('You must select a category.');
            }
            if (empty($post_title)) {
                self::$form_error[] = __('You must set a discussion title.');
            }
            if (empty($post_content)) {
                self::$form_error[] = __('You must set a discussion content.');
            }

            if (self::$form_error === []) {
                try {
                    $cur = App::blog()->openPostCursor();
                    $cur->setField('user_id', App::auth()->userID());
                    $cur->setField('post_status', My::settings()->get('publish_post') ? App::status()->post()::PUBLISHED : App::status()->post()::PENDING);
                    $cur->setField('post_title', $post_title);
                    $cur->setField('post_content', $post_content);
                    $cur->setField('post_format', 'wiki');
                    $cur->setField('post_lang', App::blog()->settings()->get('system')->get('lang'));
                    $cur->setField('post_open_comment', 1);
                    $cur->setField('cat_id', $post_cat);

                    $post_id = App::auth()->sudo(App::blog()->addPost(...), $cur);

                    $more = My::settings()->get('publish_post') ? '/' . $post_id : '';

                    header('Location: ' . App::blog()->url() . App::url()->getURLFor(My::id(), 'create') . $more);
                } catch (Exception $e) {
                    self::$form_error[] = $e->getMessage();
                }
            }
        }

        $post_id = 0;
        if (!empty($args[1]) && is_numeric($args[1])) {
            $post_id = (int) $args[1];
            App::frontend()->context()->discussion_success = __('Discussion successfully created.');
        }

        // Need to have a posts instance for templates
        App::frontend()->context()->posts = App::blog()->getPosts(['post_id' => $post_id]);

        self::serveTemplate('create');
    }

    /**
     * Discussion user list endpoint.
     * 
     * @param   array<int, string>  $args
     */
    public static function mylist(array $args): void
    {
        self::p404();

        //self::serveTemplate('mylist');
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
     * Check nonce from POST requests.
     */
    private static function checkForm(): void
    {
        if (!App::nonce()->checkNonce($_POST['discussion_check'] ?? '-')) {
            throw new PreconditionException();
        }
    }

    /**
     * Load formater.
     */
    private static function loadFormater(): void
    {
        // init wiki transform
        if (!App::filter()->wiki()) {
            App::filter()->initWikiPost();
        }
        if (App::filter()->wiki()) {
            // add wiki tranform capabilities for submission
            App::formater()->addEditorFormater('dcLegacyEditor', 'wiki', App::filter()->wiki()->transform(...));
        }
        // add markdown tranform capabilities for submission (convert readme contents on addPost)
        /* @phpstan-ignore-next-line */
        //App::formater()->addEditorFormater('dcLegacyEditor', 'markdown', Markdown::convert(...));
    }

    /**
     * Serve template.
     */
    private static function serveTemplate(string $template): void
    {
        // use only dotty tplset
        $tplset = App::themes()->moduleInfo(App::blog()->settings()->get('system')->get('theme'), 'tplset');
        if (!in_array($tplset, ['dotty', 'mustek'])) {
            self::p404();
        }

        if (count(self::$form_error) > 0) {
            App::frontend()->context()->form_error = implode("\n", self::$form_error);
        }

        $default_template = Path::real(App::plugins()->moduleInfo(My::id(), 'root')) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;
        if (is_dir($default_template . $tplset)) {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
        }

        self::serveDocument(My::id() . '-' . $template . '.html');
    }
}
