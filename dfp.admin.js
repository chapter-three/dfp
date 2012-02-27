(function ($) {

Drupal.behaviors.dfpAdminColors = {
  attach: function (context) {

    var reg = /^(#)?([0-9a-fA-F]{3})([0-9a-fA-F]{3})?$/;
    $('#color-settings .color-setting', context).bind('change keyup', function (context) {
      hexcolor = reg.test($(this).val()) ? '#' + $(this).val() : 'transparent';
      $(this).closest('tr').find('.color-sample').css('background-color', hexcolor);
    });
    console.log($('#color-settings .color-setting', context));
  }
};

/**
 * Custom summary for the module vertical tab.
 */
Drupal.behaviors.dfpVerticalTabs = {
  attach: function (context) {

    var checkmark = '&#10003;';
    var exmark = '&#10008;';

    $('fieldset#edit-global-tag-settings', context).drupalSetSummary(function (context) {
      var networkId = Drupal.checkPlain($('#edit-dfp-network-id', context).val());
      var adUnit = Drupal.checkPlain($('#edit-dfp-default-adunit', context).val());
      var async = Drupal.checkPlain($('#edit-dfp-async-rendering', context).is(':checked'));
      var single = Drupal.checkPlain($('#edit-dfp-single-request', context).is(':checked'));

      summary = 'Network Id: ' + networkId + '<br/>';
      summary += (async == "true" ? checkmark : exmark) + ' Load ads asyncronously' + '<br/>';
      summary += (single == "true" ? checkmark : exmark) + ' Use a single request';

      return summary;
    });

    $('fieldset#edit-global-display-options', context).drupalSetSummary(function (context) {
      var slug = Drupal.checkPlain($('#edit-dfp-slug', context).val());
      var noscript = Drupal.checkPlain($('#edit-dfp-use-noscript', context).is(':checked'));
      var collapse = Drupal.checkPlain($('#edit-dfp-collapse-empty-divs', context).is(':checked'));

      summary = 'Global Slug: ' + slug + '<br/>';
      summary += (noscript == "true" ? checkmark : exmark) + ' Include <code>&lt;noscript&gt;</code> tags' + '<br/>';
      summary += (collapse == "true" ? checkmark : exmark) + ' Hide ad slots if no ad is served';

      return summary;
    });

    $('fieldset#edit-tag-settings', context).drupalSetSummary(function (context) {
      var summary = Drupal.t('General configuration options');
      var name = Drupal.checkPlain($('#edit-name', context).val());
      var size = Drupal.checkPlain($('#edit-size', context).val());
      return summary + (name != '' ? '<br/>' + name  + ' [' + size + ']' : '');
    });

    $('fieldset#edit-tag-display-options', context).drupalSetSummary(function (context) {
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
