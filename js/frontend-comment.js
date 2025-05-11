/*global $, dotclear */
'use strict';

dotclear.DiscussionCommentOptions = (source, target) => {
  if (!source) {
    return;
  }

  // create menu
  const tpl_input = document.createElement('template');
  tpl_input.innerHTML = `<input type="submit" class="post-comment-quote" value="${dotclear.Discussion.input_text}" />`;
  const button = tpl_input.content.childNodes[0];

  button.addEventListener('click', (e) => {
    const text = source.querySelector('.comment-content').textContent.trim();
    if (text?.length) {
      target.innerHTML += '>' + `[${dotclear.Discussion.response_text}|#c${source.getAttribute('id').substr(1)}]` + "\n";
      target.focus();
    }

    e.preventDefault();
    return false;
  });
  source.querySelector('form')?.prepend(button);
};

dotclear.ready(() => {
  // translations
  dotclear.Discussion = dotclear.getData('Discussion');

  // find comments and form
  const target = document.getElementById('c_content');
  const sources = document.querySelectorAll('#comments .comment');
  if (sources?.length) {
    for (const source of sources) dotclear.DiscussionCommentOptions(source, target);
  }
});