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

        App::frontend()->template()->addBlocks([
            'DiscussionIf'                => FrontendTemplate::DiscussionIf(...),
            'DiscussionPreviewIf'         => FrontendTemplate::DiscussionPreviewIf(...),
            'DiscussionCategories'        => FrontendTemplate::DiscussionCategories(...),
            'DiscussionCategoryComments'  => FrontendTemplate::DiscussionCategoryComments(...),
            'DiscussionEntries'           => FrontendTemplate::DiscussionEntries(...),
            'DiscussionEntriesIf'         => FrontendTemplate::DiscussionEntriesIf(...),
            'DiscussionEntriesPagination' => FrontendTemplate::DiscussionEntriesPagination(...),
        ]);
        App::frontend()->template()->addValues([
            'DiscussionFormNonce'             => FrontendTemplate::DiscussionFormNonce(...),
            'DiscussionFormURL'               => FrontendTemplate::DiscussionFormURL(...),
            'DiscussionFormSuccess'           => FrontendTemplate::DiscussionFormSuccess(...),
            'DiscussionPreviewPostTitle'      => FrontendTemplate::DiscussionPreviewPostTitle(...),
            'DiscussionPreviewPostContent'    => FrontendTemplate::DiscussionPreviewPostContent(...),
            'DiscussionPostTitle'             => FrontendTemplate::DiscussionPostTitle(...),
            'DiscussionPostContent'           => FrontendTemplate::DiscussionPostContent(...),
            'DiscussionCategoriesTitle'       => FrontendTemplate::DiscussionCategoriesTitle(...),
            'DiscussionCategoriesDescription' => FrontendTemplate::DiscussionCategoriesDescription(...),
            'DiscussionCategoriesCombo'       => FrontendTemplate::DiscussionCategoriesCombo(...),
            'CategoryDescription'             => FrontendTemplate::CategoryDescription(...),
        ]);
        App::behavior()->addBehaviors([
            'initWidgets'                       => Widgets::initWidgets(...),
            'publicPostBeforeGetPosts'          => FrontendBehaviors::publicPostBeforeGetPosts(...),
            'publicHeadContent'                 => FrontendBehaviors::publicHeadContent(...),
            'publicCommentAfterContent'         => FrontendBehaviors::publicCommentAfterContent(...),
            'publicCommentFormAfterContent'     => FrontendBehaviors::publicCommentFormAfterContent(...),
            'publicAfterCommentCreate'          => FrontendBehaviors::publicAfterCommentCreate(...),
            'FrontendSessionPage'               => FrontendBehaviors::FrontendSessionPage(...),
            'FrontendSessionWidget'             => FrontendBehaviors::FrontendSessionWidget(...),
            'FrontendSessionAfterSignup'        => FrontendBehaviors::FrontendSessionAfterSignup(...),
            'FrontendSessionCommentsActive'     => FrontendBehaviors::FrontendSessionCommentsActive(...),
            'ReadingTrackingUrlTypes'           => FrontendBehaviors::ReadingTrackingUrlTypes(...),
            'publicBreadcrumb'                  => FrontendBehaviors::publicBreadcrumb(...),
            'coreInitWikiPost'                  => FrontendBehaviors::coreInitWikiPost(...),
            'publicCategoryBeforeGetCategories' => FrontendBehaviors::publicCategoryBeforeGetCategories(...),
            'templatePrepareParams'             => FrontendBehaviors::templatePrepareParams(...),
        ]);

        return true;
    }
}
