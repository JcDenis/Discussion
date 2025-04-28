<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Html;

class Core
{
    public static function getCategories(): MetaRecord
    {
        if (App::task()->checkContext('BACKEND')) {
            $rs = App::blog()->getCategories();
        } else {
            $rs = App::blog()->getCategories([
                'start' => My::settings()->get('root_cat'),
            ]);
        }

        return $rs;
    }

    public static function getCategoriesTitle()
    {
        return App::blog()->getCategories(['cat_id' => My::class::settings()->get('root_cat')])->cat_title ?: __('Categories');
    }

    public static function getCategoriesDescription()
    {
        return App::blog()->getCategories(['cat_id' => My::class::settings()->get('root_cat')])->cat_desc ?: '';
    }

    /**
     * Returns an hierarchical categories combo.
     *
     * @return     array<Option>   The categories combo.
     */
    public static function getCategoriesCombo(): array
    {
        if (App::task()->checkContext('BACKEND')) {
            $root_cat         = 0;
            $categories_combo = [new Option(__('Do not limit'), '')];
        } else {
            $root_cat         = My::settings()->get('root_cat');
            $categories_combo = [new Option(__('Select a category'), '')];
        }

        $level = 1;
        $rs = self::getCategories();
        while ($rs->fetch()) {
            if ($root_cat && $root_cat == $rs->cat_id) {
                $level = 2;
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

    public static function isDiscussionCategory(int $cat_id)
    {
        if (App::task()->checkContext('BACKEND')) {
            $root_cat = 0;
        } else {
            $root_cat = My::settings()->get('root_cat');
        }

        $rs = self::getCategories();
        while ($rs->fetch()) {
            if ($root_cat && $root_cat == $rs->cat_id) {
                continue;
            }
            if ($cat_id == (int) $rs->cat_id) {
                return true;
            }
        }

        return false;
    }
}