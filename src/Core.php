<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Html;

/**
 * @brief       Discussion core class.
 * @ingroup     Discussion
 *
 * "resolver" is a comment that resolved a discussion.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Core
{
    public const DEFAULT_ARTIFACT = "\u{2713}";

    /**
     * @var     array<int, MetaRecord>  $resolvers  The posts/commments resolver stack
     */
    private static array $resolvers = [];

    public static function getCategories(): MetaRecord
    {
        return App::blog()->getCategories(App::task()->checkContext('BACKEND') ? [] : ['start' => self::getRootCategory()]);
    }

    public static function getRootCategory(): int
    {
        return (int) (My::settings()->get('root_cat') ?: 0);
    }

    public static function getRootCategoryTitle(): string
    {
        return self::hasRootCategory() ? App::blog()->getCategories(['cat_id' => self::getRootCategory()])->f('cat_title') : __('Discussions');
    }

    public static function getRootCategoryUrl(): string
    {
        return self::hasRootCategory() ? App::blog()->url() . App::url()->getURLFor('category', Html::sanitizeURL(App::blog()->getCategories(['cat_id' => self::getRootCategory()])->f('cat_url'))) : '';
    }

    public static function getRootCategoryDescription(): string
    {
        return self::hasRootCategory() ? App::blog()->getCategories(['cat_id' => self::getRootCategory()])->f('cat_desc') : '';
    }

    public static function isRootCategory(int|string $id): bool
    {
        return self::getRootCategory() === (int) $id;
    }

    public static function hasRootCategory(): bool
    {
        return self::getRootCategory() !== 0;
    }

    /**
     * Returns an hierarchical categories combo.
     *
     * @return     array<Option>   The categories combo.
     */
    public static function getCategoriesCombo(): array
    {
        $categories_combo = [new Option(App::task()->checkContext('BACKEND') ? __('Do not limit') : __('Select a category'), '')];
        $root_cat         = self::getRootCategory();
        $rs               = self::getCategories();
        $level            = self::hasRootCategory() ? 1 : 0;

        while ($rs->fetch()) {
            if (!App::task()->checkContext('BACKEND') && self::isRootCategory($rs->f('cat_id'))) {
                continue;
            }
            $option = new Option(
                str_repeat('&nbsp;', (int) (($rs->level - $level) * 4)) . Html::escapeHTML($rs->cat_title),
                (string) $rs->cat_id
            );
            if ($rs->level - $level) {
                $option->class('sub-option' . ($rs->level - $level));
            }
            $categories_combo[] = $option;
        }

        return $categories_combo;
    }

    public static function isDiscussionCategory(int|string $cat_id): bool
    {
        $rs = self::getCategories();
        while ($rs->fetch()) {
            if (self::isRootCategory($rs->f('cat_id'))) {
                continue;
            }
            if (((int) $cat_id) == ((int) $rs->f('cat_id'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user posts.
     *
     * @param   array<string, mixed> $params
     */
    public static function getUserPosts(array $params = [], bool $count_only = false): MetaRecord
    {
        $params['user_id'] = (string) App::auth()->userID();

        return self::getPosts($params, $count_only);
    }

    /**
     * Get posts (started at root category).
     *
     * @param   array<string, mixed> $params
     */
    public static function getPosts(array $params = [], bool $count_only = false): MetaRecord
    {
        $params['cat_id']  = self::getRootCategory() . '?sub';

        return App::blog()->getPosts($params, $count_only);
    }

    public static function getComments(): Metarecord
    {
        return metaRecord::newFromArray([]);
    }

    /**
     * Update post update date on new comment.
     *
     * This moves post order to reflect forum style order on category page.
     * @see     FrontendBehaviors::templatePrepareParams()
     *
     * @param   array<int, int>     $comments
     * @param   array<int, int>     $posts
     */
    public static function coreBlogAfterTriggerComments(array $comments, array $posts): void
    {
        $rs = App::blog()->getPosts(['post_id' => $posts]);
        if (!$rs->isEmpty()) {
            // update discussion post date to follow last comments
            while($rs->fetch()) {
                if (!self::isDiscussionCategory($rs->f('cat_id'))) {
                    continue;
                }
                $cur = App::blog()->openPostCursor();
                $cur->setField('post_upddt', date('Y-m-d H:i:s', time() + Date::getTimeOffset(App::blog()->settings()->get('system')->get('blog_timezone'))));

                $sql = new UpdateStatement();
                $sql->where('post_id = ' . $rs->f('post_id'));
                $sql->update($cur);
            }
        }
    }

    /**
     * Check if user can resolve post.
     */
    public static function canResolvePost(MetaRecord $rs): bool
    {
        return !$rs->isEmpty() 
            && Core::isDiscussionCategory((int) $rs->f('cat_id')) 
            && (
                App::auth()->userID() === $rs->f('user_id')
                || App::auth()->check(App::auth()::PERMISSION_ADMIN, App::blog()->id())
            );
    }

    /**
     * Set post resolver.
     */
    public static function setPostResolver(MetaRecord $rs, int $resolver_id): void
    {
        if (self::canResolvePost($rs)) {
            $post_id = (int) $rs->f('post_id');

            // mark post as resolved
            App::auth()->sudo(App::meta()->setPostMeta(...), $post_id, My::id() . 'post', (string) $resolver_id);

            // Close post comments
            $cur = App::blog()->openPostCursor();
            $cur->setField('post_open_comment', 0);
            $cur->update(
                'WHERE post_id = ' . $post_id . ' ' .
                "AND blog_id = '" . App::con()->escapeStr(App::blog()->id()) . "' " .
                "AND user_id = '" . App::con()->escapeStr((string) App::auth()->userID()) . "' "
            );
            App::blog()->triggerBlog();
        }
    }

    /**
     * Delete post resolver.
     */
    public static function delPostResolver(int $post_id): void
    {
        App::auth()->sudo(App::meta()->delPostMeta(...), $post_id, My::id() . 'post');
        App::blog()->triggerBlog();
    }

    /**
     * Get post resolver.
     */
    public static function getPostResolver(int $post_id): MetaRecord
    {
        if (!isset(self::$resolvers[$post_id])) {
            $posts = self::$resolvers;
            $meta  = App::meta()->getMetadata(['meta_type' => My::id() . 'post', 'post_id' => $post_id]);

            $posts[$post_id] = $meta->isEmpty() ?
                MetaRecord::newFromArray([]) :
                App::blog()->getComments(['post_id' => $post_id, 'comment_id' => $meta->meta_id, 'limit' => 1]);

            self::$resolvers = $posts;
        }

        return self::$resolvers[$post_id] ?? MetaRecord::newFromArray([]);
    }

    /**
     * Get post artifact.
     */
    public static function getPostArtifact(): string
    {
        return My::settings()->get('artifact') ?: self::DEFAULT_ARTIFACT;
    }

    /**
     * Get artifacts list.
     *
     * @return  array<int, string>
     */
    public static function getPostArtifacts(): array
    {
        return array_unique([
            My::settings()->get('artifact') ?: self::DEFAULT_ARTIFACT,
            self::DEFAULT_ARTIFACT,
            "\u{2718}",
            "\u{25A0}",
            __('[Resolved]'),
        ]);
    }

    /**
     * Get artifacts combo.
     *
     * @return array<int, Option>
     */
    public static function getPostArtifactsCombo(): array
    {
        $options = [
            new Option(__('Do not use artifact'), ''),
        ];
        foreach (self::getPostArtifacts() as $artifact) {
            $options[] = new Option(
                Html::escapeHTML($artifact),
                Html::escapeHTML($artifact)
            );
        }

        return $options;
    }
}