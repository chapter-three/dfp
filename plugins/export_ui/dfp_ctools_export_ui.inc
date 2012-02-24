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

  $tag = $form_state['item'];

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
    '#title' => t('Ad Unit Name'),
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

  // Display settings.
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
  );
  $form['tag_display_options']['block'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a block for this ad tag'),
    '#description' => t('Create a block (configurable at !url) in which this ad tag is displayed.', array('!url' => l('admin/build/block', 'admin/build/block'))),
    '#default_value' => $tag->block,
  );
  $form['tag_display_options']['scriptless'] = array(
    '#type' => 'checkbox',
    '#title' => t('Render this tag without javascript'),
    '#description' => t('Use this option for ads included in emails.'),
    '#default_value' => $tag->settings['scriptless'],
  );

  // Global display options.
  $existing_targeting = _dfp_get_existing_targeting($form_state, isset($tag->settings['targeting']) ? $tag->settings['targeting'] : array());
  _dfp_targeting_form($form, $form_state, $existing_targeting);

}

/**
 * Form validation for the dfp_ad_tag form.
 */
function dfp_tag_form_validate(&$form, &$form_state) {
  // Ad Unit names can only have letters, numbers and slashes.¬
  if (preg_match('@[^a-zA-Z0-9\/]+@', $form_state['values']['name'])) {
    form_set_error('name', t('Ad Unit Names can only include letters, numbers and slashes.'));
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
