<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Tpl;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Form, Hidden, Input, Label, Link, Note, Para, Password, Submit, Text };
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

/**
 * @brief       FrontendSession module template specifics.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendTemplate
{
    /**
     * Generic filter helper.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    private static function filter(ArrayObject $attr, string $res): string
    {
        return '<?php echo ' . sprintf(App::frontend()->template()->getFilters($attr), $res) . '; ?>';
    }

    /**
     * Check conditions.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionIf(ArrayObject $attr, string $content): string
    {
        $if   = [];
        $sign = fn ($a): string => (bool) $a ? '' : '!';

        $operator = isset($attr['operator']) ? Tpl::getOperator($attr['operator']) : '&&';

        // success message
        if (isset($attr['success'])) {
            $if[] = $sign($attr['success']) . "(App::frontend()->context()->discussion_success != '')";
        }
        if (isset($attr['published'])) {
            $if[] = $sign($attr['published']) . My::class . "::settings()->get('publish_post')";
        }
        if (isset($attr['has_root_cat'])) {
            $if[] = $sign($attr['has_root_cat']) . '(' . My::class . "::settings()->get('root_cat') != '')";
        }
        if (isset($attr['preview'])) {
            $if[] = $sign($attr['preview']) . "(App::frontend()->context()->post_preview !== null && App::frontend()->context()->post_preview['preview'])";
        }

        return $if === [] ?
            $content :
            '<?php if(' . implode(' ' . $operator . ' ', $if) . ') : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * Get form nonce.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionFormNonce(ArrayObject $attr): string
    {
        return self::filter($attr, 'App::nonce()->getNonce()');
    }

    /**
     * Get form URL.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionFormURL(ArrayObject $attr): string
    {
        $page = '';
        if (isset($attr['page']) && in_array($attr['page'], ['list', 'create'])) {
            $page = $attr['page'];
            unset($attr['page']);
        }
        return self::filter($attr, 'App::blog()->url() . App::url()->getURLFor("' . My::id() . '", "'. $page . '")');
    }

    /**
     * Get form success message.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionFormSuccess(ArrayObject $attr): string
    {
        return self::filter($attr, "App::frontend()->context()->discussion_success ?: ''");
    }

    /**
     * Check preview conditions.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionPreviewIf(ArrayObject $attr, string $content): string
    {
        return '<?php if(App::frontend()->context()->post_preview !== null && App::frontend()->context()->post_preview[\'preview\']) : ?>' . 
            $content . 
            '<?php endif; ?>';
    }

    /**
     * Get form post title preview.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionPreviewPostTitle(ArrayObject $attr): string
    {
        return self::filter($attr, 'App::frontend()->context()->post_preview[\'title\']');
    }

    /**
     * Get form post content preview.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionPreviewPostContent(ArrayObject $attr): string
    {
        return self::filter($attr, 'App::frontend()->context()->post_preview[\'content\']');
    }

    /**
     * Get form post title.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionPostTitle(ArrayObject $attr): string
    {
        return self::filter($attr, '$_POST[\'discussion_title\'] ?? \'\'');
    }

    /**
     * Get form post content.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionPostContent(ArrayObject $attr): string
    {
        return self::filter($attr, '$_POST[\'discussion_content\'] ?? \'\'');
    }

    /**
     * Get form categories select options.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionCategoriesCombo(ArrayObject $attr): string
    {
        return self::filter($attr, '(new Dotclear\Helper\Html\Form\Select(\'discussion_category\'))' .
            '->items(' . Core::class . '::getCategoriesCombo())' .
            '->default((string) (int) ($_POST[\'discussion_category\'] ?? (App::frontend()->context()->categories?->f(\'cat_id\') ?: \'\')))' .
            '->render()'
        );
    }

    /**
     * Get discussions categories.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionCategories(ArrayObject $attr, string $content): string
    {
        return '<?php App::frontend()->context()->categories = ' . Core::class . '::getCategories();' .
            'while (App::frontend()->context()->categories->fetch()) : ?>' .
            $content .
            '<?php endwhile; App::frontend()->context()->pop("categories"); ?>';
    }

    /**
     * Get discussions categories page title.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionCategoriesTitle(ArrayObject $attr): string
    {
        return self::filter($attr, Core::class . '::getRootCategoryTitle()');
    }

    /**
     * Get discussions categories page description.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionCategoriesDescription(ArrayObject $attr): string
    {
        return self::filter($attr, Core::class . '::getRootCategoryDescription()');
    }

    /**
     * Get discussions categories comments.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionCategoryComments(ArrayObject $attr, string $content): string
    {
        $p = 
            '$params[\'cat_id\'] = App::frontend()->context()->categories->cat_id;' .
            '$params[\'order\'] = \'comment_dt desc\';';

        $lastn = 0;
        if (isset($attr['lastn'])) {
            $lastn = abs((int) $attr['lastn']) + 0;
        }
        if ($lastn > 0) {
            $p .= '$params[\'limit\'] = ' . $lastn . ';';
        }
        if (isset($attr['no_content']) && $attr['no_content']) {
            $p .= '$params[\'no_content\'] = true;';
        }
    
        return 
            '<?php ' . $p . 
            'App::frontend()->context()->comments_params = $params;' .
            'App::frontend()->context()->comments = App::blog()->getComments($params); unset($params);' .
            'while (App::frontend()->context()->comments->fetch()) : ' .
            'App::frontend()->context()->posts = App::blog()->getPosts([\'post_id\' => App::frontend()->context()->comments->post_id]);' .
            ' ?>' .
            $content .
            '<?php endwhile; App::frontend()->context()->pop("comments"); App::frontend()->context()->pop("posts"); ?>';
    }

    /**
     * Overload Category description on category page.
     *
     * attributes:
     *
     *      - any filters     See self::getFilters()
     *
     * @param      ArrayObject<string, mixed>    $attr     The attributes
     */
    public static function CategoryDescription(ArrayObject $attr): string
    {
        return self::filter($attr, 'App::frontend()->context()->categories->cat_desc') . 
            self::filter($attr, self::class . '::newDiscussionButton()');
    }

    /**
     * Check user discussions conditions.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionEntriesIf(ArrayObject $attr, string $content): string
    {
        $if   = [];
        $sign = fn ($a): string => (bool) $a ? '!' : '';

        $operator = isset($attr['operator']) ? Tpl::getOperator($attr['operator']) : '&&';

        if (isset($attr['has_discussion'])) {
            $if[] = $sign($attr['has_discussion']) . Core::class . '::getPosts()->isEmpty()';
        }

        return $if === [] ?
            $content :
            '<?php if(' . implode(' ' . $operator . ' ', $if) . ') : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * Get discussions user posts.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionEntries(ArrayObject $attr, string $content): string
    {
        $params = 'if (App::frontend()->getPageNumber() === 0) { App::frontend()->setPageNumber(1); }' . "\n";
        $params .= "if (!isset(\$params) || !isset(\$params['sql'])) { \$params['sql'] = ''; }\n";
        $params .= "\$params['limit'] = App::frontend()->context()->nb_entry_per_page;\n";
        $params .= "\$params['limit'] = [(App::frontend()->getPageNumber() - 1) * \$params['limit'],\$params['limit']];\n";

        if (isset($attr['no_content']) && $attr['no_content']) {
            $params .= "\$params['no_content'] = true;\n";
        }

        return "<?php\n" .
            $params .
            'App::frontend()->context()->post_params = $params;' . "\n" .
            'App::frontend()->context()->posts = ' . Core::class . '::getPosts($params); unset($params);' . "\n" .
            'while (App::frontend()->context()->posts->fetch()) : ?>' .
            $content .
            '<?php endwhile; App::frontend()->context()->pop("posts"); ?>';
    }

    /**
     * Special pagination for user posts.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function DiscussionEntriesPagination(ArrayObject $attr, string $content): string
    {
        $params = "<?php\n" .
            '$params = App::frontend()->context()->post_params;' . "\n" .
            'App::frontend()->context()->pagination = ' . Core::class . '::getPosts($params,true); unset($params);' . "\n" .
            "?>\n";

        if (isset($attr['no_context']) && $attr['no_context']) {
            return $params . $content;
        }

        return $params . '<?php if (App::frontend()->context()->pagination->f(0) > App::frontend()->context()->posts->count()) : ?>' . $content . '<?php endif; ?>';
    }

    public static function newDiscussionButton(): string
    {
        if (App::url()->getType() == 'category'
            && Core::isDiscussionCategory(App::frontend()->context()->categories->f('cat_id'))
            && App::auth()->check(My::id(), App::blog()->id())
        ) {
            return (new Para())
                ->items([
                    (new Link())
                        ->class('button')
                        ->href(App::blog()->url() . App::url()->getURLFor(My::id(), 'create') . '/category/' . App::frontend()->context()->categories->f('cat_id'))
                        ->title(Html::escapeHTML(__('Create a new discussion')))
                        ->text(__('New discussion'))
                ])
                ->render();
        }

        return '';
    }
}
