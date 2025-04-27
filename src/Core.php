<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Html;

class Core
{
    /**
     * Returns an hierarchical categories combo.
     *
     * @return     array<Option>   The categories combo.
     */
    public static function getCategoriesCombo(): array
    {
        $root_cat = 0;
        if (App::task()->checkContext('BACKEND')) {
            $categories_combo = [new Option(__('Do not limit'), '')];
            $categories       = App::blog()->getCategories();
        } else {
            $root_cat = My::settings()->get('root_cat');
            $categories_combo = [new Option(__('Select a category'), '')];
            $categories       = App::blog()->getCategories([
                'start' => $root_cat,
            ]);
        }

        $level = 1;
        while ($categories->fetch()) {
            if ($root_cat && $root_cat == $categories->cat_id) {
                $level = 2;
                continue;
            }
            $option = new Option(
                str_repeat('&nbsp;', (int) (($categories->level - $level) * 4)) . Html::escapeHTML($categories->cat_title),
                (string) $categories->cat_id
            );
            if ($categories->level - $level) {
                $option->class('sub-option' . ($categories->level - $level));
            }
            $categories_combo[] = $option;
        }

        return $categories_combo;
    }

    public static function isDiscussionCategory(int $cat_id)
    {
        $root_cat = 0;
        if (App::task()->checkContext('BACKEND')) {
            $categories = App::blog()->getCategories();
        } else {
            $root_cat = My::settings()->get('root_cat');
            $categories = App::blog()->getCategories([
                'start' => $root_cat,
            ]);
        }

        while ($categories->fetch()) {
            if ($root_cat && $root_cat == $categories->cat_id) {
                continue;
            }
            if ($cat_id == (int) $categories->cat_id) {
                return true;
            }
        }

        return false;
    }
}