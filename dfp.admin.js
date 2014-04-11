(function ($) {

Drupal.behaviors.dfpAdminColors = {
  attach: function (context) {

    var reg = /^(#)?([0-9a-fA-F]{3})([0-9a-fA-F]{3})?$/;
    $('#color-settings .color-setting', context).bind('change keyup', function (context) {
      hexcolor = reg.test($(this).val()) ? '#' + $(this).val() : 'transparent';
      $(this).closest('tr').find('.color-sample').css('background-color', hexcolor);
    });
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
      var slug = Drupal.checkPlain($('#edit-dfp-default-slug', context).val());
      var noscript = Drupal.checkPlain($('#edit-dfp-use-noscript', context).is(':checked'));
      var collapse = Drupal.checkPlain($('input[name="dfp_collapse_empty_divs"]:checked', context).val());

      summary = 'Global Slug: ' + slug + '<br/>';
      switch (collapse) {
        case '0':
          summary += exmark + ' Never collapse empty divs';
          break;
        case '1':
          summary += checkmark + ' Collapse divs only if empty';
          break;
        case '2':
          summary += checkmark + ' Expand divs only if non-empty';
          break;
      }

      return summary;
    });

    $('fieldset#edit-backfill-settings', context).drupalSetSummary(function (context) {
      var adType = Drupal.checkPlain($('#edit-settings-adsense-ad-types option:selected', context).text());
      var adTypeVal = Drupal.checkPlain($('#edit-settings-adsense-ad-types', context).val());

      summary = adTypeVal !== '' ? Drupal.t('Ad Type: ') + adType : '';
      return summary;
    });

    $('fieldset#edit-tag-settings', context).drupalSetSummary(function (context) {
      var slot = Drupal.checkPlain($('#edit-slot', context).val());
      var size = Drupal.checkPlain($('#edit-size', context).val());

      summary = slot !== '' ?  slot  + ' [' + size + ']' : '';
      return summary;
    });

    $('fieldset#edit-tag-display-options', context).drupalSetSummary(function (context) {
      summary = Drupal.t('Configure how the ad will be displayed');
      return summary;
    });

    $('fieldset#edit-breakpoint-settings', context).drupalSetSummary(function (context) {
      var summary = 'Configure DFP mappings.';
      return summary;
    });

    $('fieldset#edit-targeting-settings', context).drupalSetSummary(function (context) {
      var summary = '';
      $('.field-target-target', context).each(function (context) {
        target = Drupal.checkPlain($(this).val());
        if (target !== '') {
          value = Drupal.checkPlain($(this).closest('tr').find('.field-target-value').val());
          summary += target + ' = ' + value + '<br/>';
        }
      });
      return summary;
    });
  }
};

})(jQuery);
