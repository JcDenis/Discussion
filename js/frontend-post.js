/*global jQuery, dotclear, jsToolBar */
'use strict';

dotclear.getDiscussionResolver = (post) => {
  try {
    const request = new XMLHttpRequest();
    const requestUrl = `${dotclear.Discussionresolver.url}/resolver/${post.id.substr(1)}`;
    const target = post.querySelector('.post-title');

    if (target) {
      request.onreadystatechange = () => {
        if (request.readyState === 4 && request.status === 200) {
          const response = JSON.parse(request.responseText);

          target.prepend(`${response.ret} `);
        }
      };

      request.open('GET', requestUrl);
      request.responseType = 'text';
      request.send();
    }
  } catch (_e) {}
};

dotclear.ready(() => {
  const Discussionresolver = dotclear.getData('Discussionresolver');
  dotclear.Discussionresolver = Discussionresolver;

  const rtPosts = document.querySelectorAll('article');
  if (rtPosts) {
    rtPosts.forEach((post) => {
      dotclear.getDiscussionResolver(post);
    });
  }

  const rtCats = document.querySelectorAll('.discussion-posts tr');
  if (rtCats) {
    rtCats.forEach((post) => {
      dotclear.getDiscussionResolver(post);
    });
  }
});
