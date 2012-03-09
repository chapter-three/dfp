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
  ),
);

/**
 * Form builder; Create an ad tag.
 */
function dfp_tag_form(&$form, &$form_state) {
  $form['#validate'][] = 'dfp_tag_form_validate';

  // Make sure that all the necessary files are included even on ajax callbacks.
  form_load_include($form_state, 'inc', 'dfp', 'plugins/export_ui/dfp_ctools_export_ui');
  form_load_include($form_state, 'inc', 'dfp', 'dfp.admin');

  $tag = ($form_state['op'] == 'add' ? $form_state['item'] : $form_state['item']->object);

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

  $form['tag_settings']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Ad Slot Name'),
    '#required' => TRUE,
    '#maxlength' => 64,
    '#default_value' => $tag->name,
    '#description' => t('Example: leaderboard or sports/baseball/Yankees'),
  );
  $form['tag_settings']['machinename'] = array(
    '#type' => 'machine_name',
    '#title' => t('Unique Name'),
    '#maxlength' => 128,
    '#default_value' => $tag->machinename,
    '#description' => t('Only use letters, numbers and underscores. Example: top-banner'),
    '#machine_name' => array(
      'exists' => 'dfp_tag_name_exists',
      'source' => array('tag_settings', 'name'),
    ),
  ) + $form['info']['machinename'];
  unset($form['info']);
  $form['tag_settings']['size'] = array(
    '#type' => 'textfield',
    '#title' => t('Size(s)'),
    '#description' => t('Example: 300x600,300x250'),
    '#required' => TRUE,
    '#maxlength' => 64,
    '#default_value' => $tag->size,
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
    '#description' => t('Display this ad in a block configurable at !url.', array('!url' => l('admin/build/block', 'admin/build/block'))),
    '#default_value' => $tag->block,
  );
  $form['tag_display_options']['short_tag'] = array(
    '#type' => 'checkbox',
    '#title' => t('Render this tag without javascript'),
    '#description' => t('Use this option for ads included in emails.'),
    '#default_value' => $tag->settings['short_tag'],
    '#parents' => array('settings', 'short_tag'),
  );

  // Targeting options.
  $form['targeting_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Targeting'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $existing_targeting = _dfp_get_existing_targeting($form_state, isset($tag->settings['targeting']) ? $tag->settings['targeting'] : array());
  _dfp_targeting_form($form['targeting_settings'], $form, $existing_targeting);

  // Backfill ad settings options.
  $form['backfill_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Backfill Ad Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
    //'#description' => t('If you are backfilling your ads with AdSense ads, override the settings defined in DFP below.'),
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
  $form['backfill_settings']['adsense_color_settings'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-item')),
    '#theme' => 'dfp_adsense_color_settings',
    '#states' => array(
      'visible' => array(
        // @todo: Find a better solution to having an "or" condition. This hack
        // was borrowed from http://bit.ly/xYgNbV.
        ':input[name="settings[adsense_ad_types]"], none' => array('!value' => ''),
        ':input[name="settings[adsense_ad_types]"], image' => array('!value' => 'image'),
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
      '#title' => $title,
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
  if (preg_match('@[^a-zA-Z0-9\/\-_\.]+@', $form_state['values']['name'])) {
    form_set_error('name', t('Ad Unit Names can only include letters, numbers, hyphens, dashes, periods and slashes.'));
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
