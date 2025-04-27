<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{ Li, Link, Para, Text, Ul };
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;

/**
 * @brief       Discussion module frontend process.
 * @ingroup     Discussion
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status() || !My::settings()->get('active')) {
            return false;
        }

        App::frontend()->template()->addBlock('DiscussionIf', FrontendTemplate::DiscussionIf(...));
        App::frontend()->template()->addValue('DiscussionFormNonce', FrontendTemplate::DiscussionFormNonce(...));
        App::frontend()->template()->addValue('DiscussionFormURL', FrontendTemplate::DiscussionFormURL(...));
        App::frontend()->template()->addValue('DiscussionFormSuccess', FrontendTemplate::DiscussionFormSuccess(...));
        App::frontend()->template()->addValue('DiscussionPostTitle', FrontendTemplate::DiscussionPostTitle(...));
        App::frontend()->template()->addValue('DiscussionPostContent', FrontendTemplate::DiscussionPostContent(...));
        App::frontend()->template()->addValue('DiscussionCategoriesCombo', FrontendTemplate::DiscussionCategoriesCombo(...));

        // behaviors
        App::behavior()->addBehaviors([
            'publicHeadContent' => function (): void {
                $tplset = App::themes()->moduleInfo(App::blog()->settings()->get('system')->get('theme'), 'tplset');
                if (in_array($tplset, ['dotty', 'mustek'])) {
                    echo My::cssLoad('frontend-' . $tplset) . My::jsLoad('frontend');
                }
            },
            'publicFrontendSessionPage' => function (): void {
                if (App::auth()->check(My::id(), App::blog()->id())) {
                    $li  = fn (array $line): Li => (new Li())->items([(new Link())->href(App::blog()->url() . $line[0])->title($line[1])->text($line[2])]);
                    $lines = [
                        $li([App::url()->getURLFor(My::id(), 'list'), Html::escapeHTML(__('View my discussions')), Html::escapeHTML(__('My discussions'))]),
                        $li([App::url()->getURLFor(My::id(), 'create'), Html::escapeHTML(__('Create a new discussion')), Html::escapeHTML(__('New discussion'))]),
                    ];

                    echo (new Para())
                        ->items([
                            (new Text('h3', __('Discussion'))),
                            (new Text('p', __('You can create discussions.'))),
                            (new Ul())->items($lines),
                        ])
                        ->render();
                }
            },
            'publicFrontendSessionWidget' => function (ArrayObject $lines): void {
                if (App::auth()->check(My::id(), App::blog()->id())) {
                    $li  = fn (array $line): Li => (new Li())->items([(new Link())->href(App::blog()->url() . $line[0])->title($line[1])->text($line[2])]);
                    $url = App::url()->getURLFor(My::id(), 'create');

                    $lines->append($li([$url, Html::escapeHTML(__('Create a new discussion')), __('New Discussion')]));
                }
            },

        ]);
        return true;
    }
}
