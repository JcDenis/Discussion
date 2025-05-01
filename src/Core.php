<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Html;

class Core
{
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
     * Update post date on new comment.
     *
     * This moves post order to reflect forum style order.
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
                if (self::isDiscussionCategory($rs->f('cat_id'))) {
                    continue;
                }
                $cur = App::blog()->openPostCursor();
                $cur->setField('post_dt', date('Y-m-d H:i:00', time() + Date::getTimeOffset(App::blog()->settings()->get('system')->get('blog_timezone'))));

                $sql = new UpdateStatement();
                $sql->where('post_id = ' . $rs->f('post_id'));
                $sql->update($cur);
            }
        }
    }
}