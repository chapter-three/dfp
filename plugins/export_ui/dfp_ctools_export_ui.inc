<?php

/**
 * @file
 * A Ctools Export UI plugin for DFP ads.
 */

/**
 * Define this Export UI plugin.
 */
$plugin = array(
  'schema' => 'dfp_tags',
  'access' => 'administer DFP',

  // Define the menu item.
  'menu' => array(
    'menu item' => 'dfp_ads',
    'menu prefix' => 'admin/structure',
    'menu title' => 'DFP Ad Tags',
    'menu description' => 'Create and manage your DFP ad tags.',
  ),

  // Define user interface texts.
  'title singular' => t('DFP ad'),
  'title plural' => t('DFP ads'),
  'title singular proper' => t('DFP ad'),
  'title plural proper' => t('DFP ads'),

  // Define the class to use as a handler for DFP ad tags.
  'handler' => array(
     'class' => 'dfp_tag_ui',
     'parent' => 'ctools_export_ui',
  ),

  // Define the names of the functions that provide the add/edit forms.

  'form' => array(
    'settings' => 'dfp_tag_form',
    'validate' => 'dfp_tag_form_validate',
  ),
);

/**
 * Form builder; Create an ad tag.
 */
function dfp_tag_form(&$form, &$form_state) {
  // Make sure that all the necessary files are included even on ajax callbacks.
  form_load_include($form_state, 'inc', 'dfp', 'plugins/export_ui/dfp_ctools_export_ui');
  form_load_include($form_state, 'inc', 'dfp', 'dfp.admin');

  // Create a default tag object.
  $tag = ($form_state['op'] == 'add' ? $form_state['item'] : $form_state['item']->raw);

  $form['settings'] = array(
    '#type' => 'vertical_tabs',
  );

  // Tag settings.
  $form['tag_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Tag Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
    '#attached' => array(
      'js' => array(
        'vertical-tabs' => drupal_get_path('module', 'dfp') . '/dfp.admin.js',
      ),
    ),
  );

  $form['tag_settings']['slot'] = array(
    '#type' => 'textfield',
    '#title' => t('Ad Slot Name'),
    '#required' => TRUE,
    '#maxlength' => 64,
    '#default_value' => $tag->slot,
    '#description' => t('Example: leaderboard or box1'),
  );
  $form['tag_settings']['machinename'] = array(
    '#type' => 'machine_name',
    '#title' => t('Unique Name'),
    '#maxlength' => 128,
    '#default_value' => $tag->machinename,
    '#description' => t('Only use letters, numbers and underscores. Example: top-banner'),
    '#machine_name' => array(
      'exists' => 'dfp_tag_name_exists',
      'source' => array('tag_settings', 'slot'),
    ),
  ) + $form['info']['machinename'];
  unset($form['info']);
  $form['tag_settings']['out_of_page'] = array(
    '#type' => 'checkbox',
    '#title' => t('Out of page (interstitial) ad slot'),
    '#description' => t('Use Context module to place the Ad slot on the page.'),
    '#default_value' => isset($tag->settings['out_of_page']) ? $tag->settings['out_of_page'] : 0,
    '#parents' => array('settings', 'out_of_page'),
    '#weight' => 0,
  );
  $form['tag_settings']['size'] = array(
    '#type' => 'textfield',
    '#title' => t('Size(s)'),
    '#description' => t('Example: 300x600,300x250. For Out Of Page slots, use 0x0'),
    '#required' => TRUE,
    '#maxlength' => 64,
    '#default_value' => $tag->size,
  );
  $form['tag_settings']['adunit'] = array(
    '#type' => 'textfield',
    '#title' => t('Ad Unit Pattern'),
    '#required' => FALSE,
    '#maxlength' => 255,
    '#default_value' => $tag->adunit,
    '#description' => t('Use the tokens below to define how the ad unit should display. The network id will be included automatically. Example: [dfp_tag:url_parts:4]/[dfp_tag:slot]. Leave this field empty to use the default ad unit pattern as defined in <a href="@url">Global DFP Settings</a>.', array('@url' => 'admin/structure/dfp_ads/settings')),
  );
  $form['tag_settings']['tokens'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array('dfp_tag', 'node', 'term', 'user'),
    '#global_types' => TRUE,
    '#click_insert' => TRUE,
    '#dialog' => TRUE,
  );

  // Global Display settings.
  $form['tag_display_options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Display Options'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $form['tag_display_options']['slug'] = array(
    '#type' => 'textfield',
    '#title' => t('Slug'),
    '#description' => t('Override the default slug for this ad tag. Use !none for no slug. Leave this field empty to use the default slug. Example: Advertisement', array('!none' => check_plain('<none>'))),
    '#required' => FALSE,
    '#maxlength' => 64,
    '#default_value' => $tag->settings['slug'],
    '#parents' => array('settings', 'slug'),
  );
  $form['tag_display_options']['block'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a block for this ad tag'),
    '#description' => t('Display this ad in a block configurable at !url.', array('!url' => l(t('admin/structure/block'), 'admin/structure/block'))),
    '#default_value' => $tag->block,
  );
  $form['tag_display_options']['short_tag'] = array(
    '#type' => 'checkbox',
    '#title' => t('Render this tag without javascript'),
    '#description' => t('Use this option for ads included in emails.'),
    '#default_value' => $tag->settings['short_tag'],
    '#parents' => array('settings', 'short_tag'),
  );

  // Responsive settings.
  $form['breakpoint_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Responsive Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $existing_breakpoints = _dfp_breakpoints_get_existing($form_state, isset($tag->settings['breakpoints']) ? $tag->settings['breakpoints'] : array());
  _dfp_breakpoints_form($form['breakpoint_settings'], $existing_breakpoints);

  // Targeting options.
  $form['targeting_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Targeting'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $existing_targeting = _dfp_targeting_get_existing($form_state, isset($tag->settings['targeting']) ? $tag->settings['targeting'] : array());
  _dfp_targeting_form($form['targeting_settings'], $existing_targeting);

  // Backfill ad settings options.
  $form['backfill_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Backfill Ad Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $form['backfill_settings']['adsense_ad_types'] = array(
    '#type' => 'select',
    '#title' => t('AdSense Ad Type'),
    '#empty_option' => t('- None -'),
    '#empty_value' => '',
    '#default_value' => $tag->settings['adsense_ad_types'],
    '#options' => array(
      'text_image' => t('Both image and text ads'),
      'image' => t('Only image ads'),
      'text' => t('Only text ads'),
    ),
    '#parents' => array('settings', 'adsense_ad_types'),
    '#description' => t('Choose what type of ads this tag can display when AdSense ads are used for backfill.'),
  );
  $form['backfill_settings']['adsense_channel_ids'] = array(
    '#type' => 'textfield',
    '#title' => t('AdSense Channel ID(s)'),
    '#default_value' => $tag->settings['adsense_channel_ids'],
    '#required' => FALSE,
    '#description' => t('Example: 271828183+314159265'),
    '#parents' => array('settings', 'adsense_channel_ids'),
    '#states' => array(
      '!visible' => array(
        array(':input[name="settings[adsense_ad_types]"]' => array('value' => '')),
      ),
    )
  );
  $form['backfill_settings']['adsense_color_settings'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-item')),
    '#theme' => 'dfp_adsense_color_settings',
    '#states' => array(
      'visible' => array(
        array(':input[name="settings[adsense_ad_types]"]' => array('value' => 'text')),
        array(':input[name="settings[adsense_ad_types]"]' => array('value' => 'text_image')),
      ),
    ),
    '#attached' => array(
      'js' => array(
        'vertical-tabs' => drupal_get_path('module', 'dfp') . '/dfp.admin.js',
      ),
    ),
  );
  $adsense_color_settings = array(
    'background' => t('Background color'),
    'border' => t('Border color'),
    'link' => t('Link color'),
    'text' => t('Text color'),
    'url' => t('URL color'),
  );
  foreach ($adsense_color_settings as $setting => $title) {
    $form['backfill_settings']['adsense_color_settings'][$setting] = array(
      '#type' => 'textfield',
      '#title' => check_plain($title),
      '#attributes' => array('class' => array('color-setting')),
      '#field_prefix' => '#',
      '#title_display' => 'invisible',
      '#default_value' => isset($tag->settings['adsense_colors'][$setting]) ? $tag->settings['adsense_colors'][$setting] : '',
      '#size' => 6,
      '#parents' => array('settings', 'adsense_colors', $setting),
    );
  }
}

/**
 * Form validation for the dfp_ad_tag form.
 */
function dfp_tag_form_validate(&$form, &$form_state) {
  // Ad Unit names can only have letters, numbers and slashes.¬
  if (preg_match('@[^a-zA-Z0-9\/\-_\.\[\]\:]+@', $form_state['values']['adunit'])) {
    form_set_error('adunit', t('Ad Unit Patterns can only include letters, numbers, hyphens, dashes, periods, slashes and tokens.'));
  }
}

/**
 * Check if the given machinename is unique in the dfp_tags table.
 */
function dfp_tag_name_exists($machinename) {
  $select = db_select('dfp_tags', 'dt');
  $select->addExpression('COUNT(*)');
  $select->condition('dt.machinename', $machinename);

  return $select->execute()->fetchField();
}
