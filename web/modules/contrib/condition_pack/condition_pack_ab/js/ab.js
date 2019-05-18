/**
 * @file
 * Attaches behaviors for the A/B condition.
 */
(function ($) {

  "use strict";

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.abtestSettingsSummaries = {
    attach: function () {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      // There may be an easier way to do this. Right now, we just copy code
      // from block module.
      function radiosSummary(context) {
        var vals = [];
        var $radios = $(context).find('input[type="radio"]:checked + label');
        var il = $radios.length;
        for (var i = 0; i < il; i++) {
          vals.push($($radios[i]).html());
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }

      $('[data-drupal-selector="edit-visibility-abtest"]').drupalSetSummary(radiosSummary);

    }
  };

})(jQuery);
