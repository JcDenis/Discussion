/*global $, dotclear */
'use strict';

dotclear.DiscussionCommentOptions = (source, target) => {
  if (!source) {
    return;
  }

  // create menu
  const tpl_input = document.createElement('template');
  tpl_input.innerHTML = `<input type="submit" class="post-comment-quote" value="${dotclear.Discussionreply.input_text}" />`;
  const button = tpl_input.content.childNodes[0];

  button.addEventListener('click', (e) => {
    const text = source.querySelector('.comment-content').textContent;
    if (text?.length) {
      //var content = text.trim().split('\n').map((line,index,main)=>{ return '>' + line; }).join("\n");
      target.innerHTML += '>' + `[${dotclear.Discussionreply.response_text}|#c${source.getAttribute('id').substr(1)}]` + "\n";// + content;
      target.focus();
    }

    e.preventDefault();
    return false;
  });
  source.querySelector('.comment-action form')?.prepend(button);
};

dotclear.ready(() => {
  // translations
  const Discussionreply = dotclear.getData('Discussionreply');
  dotclear.Discussionreply = Discussionreply;

  // find comments and form
  const target = document.getElementById('c_content');
  const sources = document.querySelectorAll('#comments .comment');
  if (sources?.length) {
    for (const source of sources) dotclear.DiscussionCommentOptions(source, target);
  }
});