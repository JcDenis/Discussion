<?php

declare(strict_types=1);

namespace Dotclear\Plugin\Discussion;

use Dotclear\App;
use Dotclear\Core\Process;

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
        App::frontend()->template()->addBlock('DiscussionPreviewIf', FrontendTemplate::DiscussionPreviewIf(...));
        App::frontend()->template()->addValue('DiscussionPreviewPostTitle', FrontendTemplate::DiscussionPreviewPostTitle(...));
        App::frontend()->template()->addValue('DiscussionPreviewPostContent', FrontendTemplate::DiscussionPreviewPostContent(...));
        App::frontend()->template()->addValue('DiscussionPostTitle', FrontendTemplate::DiscussionPostTitle(...));
        App::frontend()->template()->addValue('DiscussionPostContent', FrontendTemplate::DiscussionPostContent(...));
        App::frontend()->template()->addBlock('DiscussionCategories', FrontendTemplate::DiscussionCategories(...));
        App::frontend()->template()->addValue('DiscussionCategoriesTitle', FrontendTemplate::DiscussionCategoriesTitle(...));
        App::frontend()->template()->addValue('DiscussionCategoriesDescription', FrontendTemplate::DiscussionCategoriesDescription(...));
        App::frontend()->template()->addValue('DiscussionCategoriesCombo', FrontendTemplate::DiscussionCategoriesCombo(...));
        App::frontend()->template()->addBlock('DiscussionCategoryComments', FrontendTemplate::DiscussionCategoryComments(...));
        App::frontend()->template()->addValue('CategoryDescription', FrontendTemplate::CategoryDescription(...));
        App::frontend()->template()->addBlock('DiscussionEntries', FrontendTemplate::DiscussionEntries(...));
        App::frontend()->template()->addBlock('DiscussionEntriesIf', FrontendTemplate::DiscussionEntriesIf(...));
        App::frontend()->template()->addBlock('DiscussionEntriesPagination', FrontendTemplate::DiscussionEntriesPagination(...));

        App::behavior()->addBehaviors([
            'publicHeadContent'                 => FrontendBehaviors::publicHeadContent(...),
            'publicFrontendSessionPage'         => FrontendBehaviors::publicFrontendSessionPage(...),
            'publicFrontendSessionWidget'       => FrontendBehaviors::publicFrontendSessionWidget(...),
            'publicBreadcrumb'                  => FrontendBehaviors::publicBreadcrumb(...),
            'coreInitWikiPost'                  => FrontendBehaviors::coreInitWikiPost(...),
            'publicCategoryBeforeGetCategories' => FrontendBehaviors::publicCategoryBeforeGetCategories(...),
            'templatePrepareParams'             => FrontendBehaviors::templatePrepareParams(...),
        ]);

        return true;
    }
}
