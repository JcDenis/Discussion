/*global $, dotclear */
'use strict';

dotclear.DiscussionCommentOptions = (source, target) => {
  if (!source) {
    return;
  }

  // create menu
  const tpl_input = document.createElement('template');
  tpl_input.innerHTML = `<a class="post-comment-quote button" href="#">${dotclear.Discussion.input_text}</a>`;
  const button = tpl_input.content.childNodes[0];

  button.style.cursor = 'pointer';
  button.addEventListener('click', (e) => {
    const text = source.querySelector('.comment-content').textContent.trim();
    if (!text?.length) {
      return;
    }

    target.innerHTML += '>' + `[${dotclear.Discussion.response_text}|#c${source.getAttribute('id').substr(1)}]` + "\n";

    e.preventDefault();
    return false;
  });
  source.querySelector('form')?.prepend(button);
};

dotclear.ready(() => {
  // DOM ready and content loaded

  const Discussion = dotclear.getData('Discussion');
  dotclear.Discussion = Discussion;

  // find comments and form
  const target = document.getElementById('c_content');
  const sources = document.querySelectorAll('#comments .comment');
  if (sources?.length) {
    for (const source of sources) dotclear.DiscussionCommentOptions(source, target);
  }
});