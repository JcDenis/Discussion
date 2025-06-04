<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Database\MetaRecord;

class CoreResolver
{
    /**
     * @var     array<int, MetaRecord>  $posts  The posts/commments resolver
     */
    private static array $posts = [];

    /**
     * Load relations between post and comments or comments and comments.
     */
    public static function loadRelated(int $post_id): void
    {
        if (!isset(self::$posts[$post_id])) {
            $posts = self::$posts;
            $meta  = App::meta()->getMetadata(['meta_type' => My::id() . 'post', 'post_id' => $post_id]);

            $posts[$post_id] = $meta->isEmpty() ?
                MetaRecord::newFromArray([]) :
                App::blog()->getComments(['post_id' => $post_id, 'comment_id' => $meta->meta_id, 'limit' => 1]);

            self::$posts = $posts;
        }
    }

    /**
     * Set post resolver.
     */
    public static function setPostResolver(int $post_id, int $resolver_id): void
    {
        // mark post as resolved
        App::auth()->sudo(App::meta()->setPostMeta(...), $post_id, My::id() . 'post', (string) $resolver_id);

        // Close post comments
        $cur = App::blog()->openPostCursor();
        $cur->setField('post_open_comment', 0);
        $cur->update(
            'WHERE post_id = ' . $post_id . ' ' .
            "AND blog_id = '" . App::con()->escapeStr(App::blog()->id()) . "' " .
            "AND user_id = '" . App::con()->escapeStr(App::auth()->userID()) . "' "
        );
        App::blog()->triggerBlog();
    }

    /**
     * Delete post resolver.
     */
    public static function delPostResolver(int $post_id): void
    {
        App::auth()->sudo(App::meta()->delPostMeta(...), $post_id, My::id() . 'post');
    }

    /**
     * Get post resolver.
     */
    public static function getPostResolver(int $post_id): MetaRecord
    {
        self::loadRelated($post_id);

        return self::$posts[$post_id] ?? MetaRecord::newFromArray([]);
    }
}