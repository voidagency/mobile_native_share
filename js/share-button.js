/**
 * @file
 * Share button.
 */

(function (Drupal) {
  'use strict';

  var initialized;

  Drupal.behaviors.mnShare = {
    attach: function (context, settings) {
      if (!initialized) {
        initialized = true;
        var button = document.getElementsByClassName('mn-share-button');
        // navigator.share = {};
        // console.log(navigator.share);
        // console.log(button);

        if (navigator.share) {
          for (var i = 0; i < button.length; i++) {
            button[i].classList.remove('visually-hidden');
            button[i].addEventListener('click', function (element) {
              var url = element.toElement.getAttribute('data-url');
              var title = element.toElement.getAttribute('data-title');
              var description = element.toElement.getAttribute('data-description');
              navigator.share({
                title: title,
                text: description,
                url: url,
              });
            });
          }
        }
      }
    }
  }

})(Drupal);
