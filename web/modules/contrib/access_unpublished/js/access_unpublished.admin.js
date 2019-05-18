/**
 * @file
 * Provides admin utilities.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.accessUnpublishedClipboardCopy = {
    attach: function (context) {

      $('a[data-unpublished-access-url]', context).once('accessUnpublishedClipboardCopy').on('click', function (event) {
        /* Copy url to clipboard */
        var url = this.getAttribute('data-unpublished-access-url');
        // Create a fake element and position outside viewport.
        var fakeElem = document.createElement('textarea');
        fakeElem.value = url;
        fakeElem.style.position = 'absolute';
        fakeElem.style.top = '-9999px';
        document.body.appendChild(fakeElem);

        fakeElem.select();
        document.execCommand('copy');
        document.body.removeChild(fakeElem);

        event.preventDefault();
        event.stopPropagation();
      });
    }
  };
})(jQuery, Drupal);
