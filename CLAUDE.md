# Discussion

Notes for AI assistants working on the Dotclear plugin Discussion.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Allow user to post from frontend.

No admin page. Public side: widgets.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Core.php`: "resolver" is a comment that resolved a discussion.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/FrontendBehaviors.php`: public behaviors.
* `src/FrontendTemplate.php`: template tags.
* `src/FrontendUrl.php`: public URL handler.
* `src/Install.php`: install and update, default settings.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/Widgets.php`: widgets definition and rendering.
* `js/`: `frontend-comment.js`, `frontend-post.js`.
* `default-templates/`: public templates.
* Widgets: `lastdiscussions`.
* Behaviors used: `initWidgets`, `adminBlogPreferencesFormV2`, `adminBeforeBlogSettingsUpdate`, `initCommentsWikibar`, `urlHandlerBeforeGetData`, `publicHeadContent`, `publicEntryBeforeContent`, `publicEntryAfterContent`, `publicPostBeforeGetPosts`, `publicCommentBeforeContent`, `publicCommentFormAfterContent`, `publicAfterCommentCreate`, `publicBreadcrumb`, `publicCategoryBeforeGetCategories`, `coreBlogAfterTriggerComments`.
* Requires FrontendSession and legacyMarkdown (`requires` in `_define.php`), commentsWikibar is optional.

## Pitfalls

* CI installs FrontendSession, commentsWikibar, legacyMarkdown from the author's repositories, then from franck-paul's.
* legacyMarkdown is required (discussions are stored in Markdown). commentsWikibar is optional (edition only): check `App::plugins()->moduleExists('commentsWikibar')` before using its classes or settings, and keep it out of `requires`.

## Testing

* Check the widgets (admin and public), in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
