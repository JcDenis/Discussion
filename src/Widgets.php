<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Helper\Html\Form\{  Li, Link, Text, Ul };
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\{ WidgetsElement, WidgetsStack };

/**
 * @brief       Discussion module widgets helper.
 * @ingroup     Discussion
 *
 * @author      Dotclear team
 * @copyright   AGPL-3.0
 */
class Widgets
{
    public static function initWidgets(WidgetsStack $widgets): void
    {
        $widgets
            ->create(
                'lastdiscussions',
                __('Last discussions'),
                self::lastWidget(...),
                null,
                'List of last discussions'
            )
            ->addTitle(__('Last discussions'))
            ->setting('limit', __('Limit:'), 10)
            ->setting(
                'addcat',
                __('Add link to discussion category'),
                0,
                'check'
            )            ->setting(
                'addroot',
                __('Add link to forum'),
                1,
                'check'
            )
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    public static function lastWidget(WidgetsElement $widget): string
    {
        if ($widget->isOffline()
            || !$widget->checkHomeOnly(App::url()->type)
            || !My::settings()->get('active')
        ) {
            return '';
        }

        $lines = [];
        $rs    = Core::getPosts(['limit' => $widget->get('limit')]);
        while($rs->fetch()) {
            $res = [
                (new Link())
                    ->href($rs->getURL())
                    ->text($rs->f('post_title'))
            ];

            if ($widget->get('addcat')) {
                $res[] = new Text('', ' (' . (new Link())
                    ->href($rs->getCategoryURL())
                    ->text($rs->f('cat_title'))
                    ->render() . ')');
            }

            $lines[] = (new Li())
                ->items($res);
        }

        if ($lines === []) {

            return '';
        }

        if ($widget->get('addroot') && Core::hasRootCategory()) {
            $lines[] = (new Li())
                ->items([
                    (new Link())
                        ->href(Core::getRootCategoryUrl())
                        ->text(__('All discussions')),
                ]);
        }

        return $widget->renderDiv(
            (bool) $widget->get('content_only'),
            'lastdiscussions ' . $widget->get('class'),
            '',
            ($widget->get('title') ? $widget->renderTitle(Html::escapeHTML($widget->get('title'))) : '') . (new Ul())->items($lines)->render()
        );
    }
}
