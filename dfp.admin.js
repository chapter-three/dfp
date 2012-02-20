(function ($) {

/**
 * Custom summary for the module vertical tab.
 */
Drupal.behaviors.dfpVerticalTabs = {
  attach: function (context) {
    $('fieldset#edit-tag-settings', context).drupalSetSummary(function (context) {
      var machinename = Drupal.checkPlain($('#edit-machinename', context).val());
      var size = Drupal.checkPlain($('#edit-size', context).val());
      return machinename  + ' [' + size + ']';
    });
    $('fieldset#edit-display-settings', context).drupalSetSummary(function (context) {
      return Drupal.t('Configure how the ad will be displayed');
    });
    $('fieldset#edit-targeting-settings', context).drupalSetSummary(function (context) {
      var summary = '';
      $('.field-target-target', context).each(function (context) {
        target = Drupal.checkPlain($(this).val());
        if (target != '') {
          value = Drupal.checkPlain($(this).closest('tr').find('.field-target-value').val());
          summary += target + ' = ' + value + '<br/>';
        }
      });
      return summary;
    });
  }
};

})(jQuery);
