<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Exception\PreconditionException;
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
        return is_numeric($root_cat = My::settings()->get('root_cat')) ? (int) $root_cat : 0;
    }

    public static function getRootCategoryTitle(): string
    {
        if (self::hasRootCategory()) {
            $rs = App::blog()->getCategories(['cat_id' => self::getRootCategory()]);
            if (!$rs->isEmpty()) {
                $cat_title = is_string($cat_title = $rs->f('cat_title')) ? $cat_title : '';
                if ($cat_title !== '') {
                    return $cat_title;
                }
            }
        }

        return __('Discussions');
    }

    public static function getRootCategoryUrl(): string
    {
        if (self::hasRootCategory()) {
            $rs = App::blog()->getCategories(['cat_id' => self::getRootCategory()]);
            if (!$rs->isEmpty()) {
                $cat_url = is_string($cat_url = $rs->f('cat_url')) ? $cat_url : '';
                if ($cat_url !== '') {
                    return App::blog()->url() . App::url()->getURLFor('category', Html::sanitizeURL($cat_url));
                }
            }
        }

        return '';
    }

    public static function getRootCategoryDescription(): string
    {
        if (self::hasRootCategory()) {
            $rs = App::blog()->getCategories(['cat_id' => self::getRootCategory()]);
            if (!$rs->isEmpty()) {
                $cat_desc = is_string($cat_desc = $rs->f('cat_desc')) ? $cat_desc : '';
                if ($cat_desc !== '') {
                    return $cat_desc;
                }
            }
        }

        return '';
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

        self::getRootCategory();

        $rs    = self::getCategories();
        $level = self::hasRootCategory() ? 1 : 0;

        while ($rs->fetch()) {
            $cat_id = is_numeric($cat_id = $rs->f('cat_id')) ? (int) $cat_id : 0;
            if (!App::task()->checkContext('BACKEND') && self::isRootCategory($cat_id)) {
                continue;
            }

            $cat_level = is_numeric($cat_level = $rs->f('level')) ? (int) $cat_level : 1;
            $cat_title = is_string($cat_title = $rs->f('cat_title')) ? $cat_title : '';

            $option = new Option(
                str_repeat('&nbsp;', ($cat_level - $level) * 4) . Html::escapeHTML($cat_title),
                (string) $cat_id
            );
            if ($cat_level - $level !== 0) {
                $option->class('sub-option' . ($cat_level - $level));
            }

            $categories_combo[] = $option;
        }

        return $categories_combo;
    }

    public static function isDiscussionCategory(?int $cat_id): bool
    {
        if (is_null($cat_id)) {
            return false;
        }

        $rs = self::getCategories();
        while ($rs->fetch()) {
            $current_cat_id = is_numeric($current_cat_id = $rs->f('cat_id')) ? (int) $current_cat_id : 0;
            if (self::isRootCategory($current_cat_id)) {
                continue;
            }

            if ($cat_id === $current_cat_id) {
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
        $params['cat_id'] = self::getRootCategory() . '?sub';

        return App::blog()->getPosts($params, $count_only);
    }

    public static function getComments(): MetaRecord
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
            while ($rs->fetch()) {
                $cat_id = is_numeric($cat_id = $rs->f('cat_id')) ? (int) $cat_id : 0;
                if (!self::isDiscussionCategory($cat_id)) {
                    continue;
                }

                $post_id  = is_numeric($post_id = $rs->f('post_id')) ? (int) $post_id : 0;
                $timezone = is_string($timezone = App::blog()->settings()->system->blog_timezone) ? $timezone : 'UTC';

                $cur = App::blog()->openPostCursor();
                $cur->setField('post_upddt', date('Y-m-d H:i:s', time() + Date::getTimeOffset($timezone)));

                $sql = new UpdateStatement();
                $sql->where('post_id = ' . $post_id);
                $sql->update($cur);
            }
        }
    }

    /**
     * Check partialy if post or comment can be editied from frontend.
     */
    public static function canEdit(): bool
    {
        return App::task()->checkContext('FRONTEND') // only on frontend
            && App::url()->getType() == 'post' // only on post page
            && My::settings()->get('canedit_post') // only if edition is allowed
            && App::blog()->settings()->get('commentsWikibar')->get('active') !== false // only if plugin commentsWikibar is active
            && App::blog()->settings()->get('system')->get('markdown_comments'); // only if markdown syntax is active
    }

    /**
     * Check if post can be edited from frontend.
     */
    public static function canEditPost(MetaRecord $post): bool
    {
        if (self::canEdit()) {
            $post_ts      = is_numeric($post_ts = $post->getTS()) ? (int) $post_ts : 0;
            $canedit_time = is_numeric($canedit_time = My::settings()->get('canedit_time')) ? (int) $canedit_time : 0;
            if (($post_ts + $canedit_time) > time()) { // only on limited time
                $cat_id = is_numeric($cat_id = $post->f('cat_id')) ? (int) $cat_id : 0;
                if (self::isDiscussionCategory($cat_id)) { // only on discussion
                    $post_id = is_numeric($post_id = $post->f('post_id')) ? (int) $post_id : 0;
                    if (self::getPostResolver($post_id)->isEmpty()) { // only if not resolved
                        // only if admin or post author
                        if (App::auth()->check(App::auth()::PERMISSION_CONTENT_ADMIN, App::blog()->id())) {
                            return true;
                        }

                        $user_id = is_string($user_id = $post->f('user_id')) ? $user_id : '';

                        return $user_id !== '' && App::auth()->userID() === $user_id;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if comment can be edited from frontend.
     */
    public static function canEditComment(MetaRecord $post, MetaRecord $comment): bool
    {
        if (self::canEdit()) {
            $comment_ts   = is_numeric($comment_ts = $comment->getTS()) ? (int) $comment_ts : 0;
            $canedit_time = is_numeric($canedit_time = My::settings()->get('canedit_time')) ? (int) $canedit_time : 0;
            if (($comment_ts + $canedit_time) > time()) { // only on limited time
                $cat_id = is_numeric($cat_id = $post->f('cat_id')) ? (int) $cat_id : 0;
                if (self::isDiscussionCategory($cat_id)) { // only on discussion
                    $post_id = is_numeric($post_id = $post->f('post_id')) ? (int) $post_id : 0;
                    if (self::getPostResolver($post_id)->isEmpty()) { // only if not resolved
                        // only if admin or post author
                        if (App::auth()->check(App::auth()::PERMISSION_CONTENT_ADMIN, App::blog()->id())) {
                            return true;
                        }

                        $user_id = is_string($user_id = $comment->f('author')) ? $user_id : '';

                        return $user_id !== '' && App::auth()->userID() === $user_id;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check nonce from POST requests.
     */
    public static function checkForm(): void
    {
        $check = isset($_POST['discussion_check']) && is_string($check = $_POST['discussion_check']) ? $check : '-';
        if (!App::nonce()->checkNonce($check)) {
            throw new PreconditionException();
        }
    }

    /**
     * Check if user can resolve post.
     */
    public static function canResolvePost(MetaRecord $rs): bool
    {
        if (!$rs->isEmpty()) {
            $cat_id = is_numeric($cat_id = $rs->f('cat_id')) ? (int) $cat_id : 0;
            if (self::isDiscussionCategory($cat_id)) { // only on discussion
                // only if admin or post author
                if (App::auth()->check(App::auth()::PERMISSION_CONTENT_ADMIN, App::blog()->id())) {
                    return true;
                }

                $user_id = is_string($user_id = $rs->f('user_id')) ? $user_id : '';

                return $user_id !== '' && App::auth()->userID() === $user_id;
            }
        }

        return false;
    }

    /**
     * Set post resolver.
     */
    public static function setPostResolver(MetaRecord $rs, int $resolver_id): void
    {
        if (self::canResolvePost($rs)) {
            $post_id = is_numeric($post_id = $rs->f('post_id')) ? (int) $post_id : 0;

            // mark post as resolved
            App::auth()->sudo(App::meta()->setPostMeta(...), $post_id, My::id() . 'post', (string) $resolver_id);

            // Close post comments
            $cur = App::blog()->openPostCursor();
            $cur->setField('post_open_comment', 0);
            $cur->update(
                'WHERE post_id = ' . $post_id . ' ' .
                "AND blog_id = '" . App::db()->con()->escapeStr(App::blog()->id()) . "' " .
                "AND user_id = '" . App::db()->con()->escapeStr((string) App::auth()->userID()) . "' "
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
        return is_string($artifact = My::settings()->get('artifact')) ? $artifact : self::DEFAULT_ARTIFACT;
    }

    /**
     * Get artifacts list.
     *
     * @return  array<int, string>
     */
    public static function getPostArtifacts(): array
    {
        return array_unique([
            self::getPostArtifact(),
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
