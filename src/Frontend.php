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
            'urlHandlerBeforeGetData'           => FrontendBehaviors::urlHandlerBeforeGetData(...),
            'publicHeadContent'                 => FrontendBehaviors::publicHeadContent(...),
            'publicEntryBeforeContent'          => FrontendBehaviors::publicEntryBeforeContent(...),
            'publicEntryAfterContent'           => FrontendBehaviors::publicEntryAfterContent(...),
            'publicPostBeforeGetPosts'          => FrontendBehaviors::publicPostBeforeGetPosts(...),
            'publicCommentBeforeContent'        => FrontendBehaviors::publicCommentBeforeContent(...),
            'publicCommentFormAfterContent'     => FrontendBehaviors::publicCommentFormAfterContent(...),
            'publicAfterCommentCreate'          => FrontendBehaviors::publicAfterCommentCreate(...),
            'FrontendSessionProfil'             => FrontendBehaviors::FrontendSessionProfil(...),
            'FrontendSessionWidget'             => FrontendBehaviors::FrontendSessionWidget(...),
            'FrontendSessionAfterSignup'        => FrontendBehaviors::FrontendSessionAfterSignup(...),
            'FrontendSessionPostForm'           => FrontendBehaviors::FrontendSessionPostForm(...),
            'FrontendSessionPostAction'         => FrontendBehaviors::FrontendSessionPostAction(...),
            'FrontendSessionCommentsActive'     => FrontendBehaviors::FrontendSessionCommentsActive(...),
            'FrontendSessionCommentForm'        => FrontendBehaviors::FrontendSessionCommentForm(...),
            'FrontendSessionCommentAction'      => FrontendBehaviors::FrontendSessionCommentAction(...),
            'ReadingTrackingUrlTypes'           => FrontendBehaviors::ReadingTrackingUrlTypes(...),
            'publicBreadcrumb'                  => FrontendBehaviors::publicBreadcrumb(...),
            'coreInitWikiPost'                  => FrontendBehaviors::coreInitWikiPost(...),
            'publicCategoryBeforeGetCategories' => FrontendBehaviors::publicCategoryBeforeGetCategories(...),
            'templatePrepareParams'             => FrontendBehaviors::templatePrepareParams(...),
        ]);

        return true;
    }
}
