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
  // 'handler' => array(
  //   'class' => 'dfp_ad_tag_ui',
  //   'parent' => 'ctools_export_ui',
  // ),

  // Define the names of the functions that provide the add/edit forms.

  'form' => array(
    'settings' => 'dfp_ad_tag_form',
    'submit' => 'dfp_ad_tag_form_submit',
    'validate' => 'dfp_ad_tag_form_validate',
  ),
);

/**
 * Form builder; Create an ad tag.
 */
function dfp_ad_tag_form(&$form, &$form_state) {
  $tag = $form_state['item'];

  // Make sure that this file is included even on aajx callbacks.
  form_load_include($form_state, 'inc', 'dfp', 'plugins/export_ui/dfp_ctools_export_ui');

  $form['settings'] = array(
    '#type' => 'vertical_tabs',
  );

  // Tag settings.
  $form['settings']['tag_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Tag Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['settings']['tag_settings']['machinename'] = $form['info']['machinename'];
  $form['settings']['tag_settings']['machinename']['#title'] = t('Unique Name');
  $form['settings']['tag_settings']['machinename']['#maxlength'] = 128;
  $form['settings']['tag_settings']['machinename']['#description'] = t('Only use lowercase letters, numbers and dashes. This name will be used as the "id" attribute in your markup. Example: top-banner');
  unset($form['info']);

  $form['settings']['tag_settings']['size'] = array(
    '#type' => 'textfield',
    '#title' => t('Size(s)'),
    '#description' => t('Example: 300x600,300x250'),
    '#required' => TRUE,
    '#maxlength' => 64,
    '#default_value' => $tag->size,
  );

  // Display settings.
  $form['settings']['display_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Display Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['settings']['display_settings']['slug'] = array(
    '#type' => 'textfield',
    '#title' => t('Slug'),
    '#description' => t('Override the default slug for this ad tag. Use !none for no slug. Leave this field empty to use the default slug. Example: Advertisement', array('!none' => check_plain('<none>'))),
    '#required' => FALSE,
    '#maxlength' => 64,
    '#default_value' => $tag->settings['overrides']['slug'],
  );
  $form['settings']['display_settings']['block'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a block for this ad tag'),
    '#description' => t('Create a block (configurable at !url) in which this ad tag is displayed.', array('!url' => l('admin/build/block', 'admin/build/block'))),
    '#default_value' => $tag->block,
  );
  $form['settings']['display_settings']['scriptless'] = array(
    '#type' => 'checkbox',
    '#title' => t('Render this tag without javascript'),
    '#description' => t('Use this option for ads included in emails.'),
    '#default_value' => $tag->settings['options']['scriptless'],
  );

  // Display settings.
  $form['settings']['targeting_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Targeting'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['settings']['targeting_settings']['targeting'] = array(
    '#tree' => FALSE,
    '#prefix' => '<div id="dfp-targeting-wrapper">',
    '#suffix' => '</div>',
    '#theme' => 'dfp_targeting',
  );
  // Add existing targets to the form.
  $targeting = isset($form_state['values']['targeting']) ?$form_state['values']['targeting'] : array();
  foreach ($targeting as $key => $data) {
    _dfp_targeting_form($form, $key, $data);
  }
  // Add one blank set of target fields.
  _dfp_targeting_form($form, count($targeting) + 1);

  $form['settings']['targeting_settings']['targeting']['dfp_more_targets'] = array(
    '#type' => 'submit',
    '#value' => t('Add another target'),
    '#submit' => array('dfp_more_targets_submit'),
    '#ajax' => array(
      'callback' => 'dfp_more_targets_js',
      'wrapper' => 'dfp-targeting-wrapper',
      'effect' => 'fade',
      'file' => 'plugins/export_ui/dfp_ctools_export_ui.inc',
    ),
  );

}

/**
 * Form validation for the dart_tag form.
 */
function dfp_ad_tag_form_validate(&$form, &$form_state) {
  // Do not validate unless the save button is pressed.
  if ($form_state['values']['op'] != 'Save') {
    return;
  }
}

/**
 * Form submission for the dart_tag form.
 */
function dfp_ad_tag_form_submit(&$form, &$form_state) {

}

/**
 * Submit handler to add more targets to an ad tag.
 */
function dfp_more_targets_submit($form, &$form_state) {
  $form_state['targeting'] = $form_state['values']['targeting'];
  $form_state['rebuild'] = TRUE;
}

/**
 * Ajax callback for adding new messages to the broadcast messages form.
 */
function dfp_more_targets_js($form, $form_state) {
  return $form['settings']['targeting_settings']['targeting'];
}

function _dfp_targeting_form(&$form, $key, $data = array()) {
  $form['settings']['targeting_settings']['targeting'][$key] = array(
    '#prefix' => '<div class="target" id="target-' . $key . '">',
    '#suffix' => '</div>',
  );
  $form['settings']['targeting_settings']['targeting'][$key]['target'] = array(
    '#type' => 'textfield',
    '#title_display' => 'invisible',
    '#title' => t('Target Name'),
    '#size' => 40,
    '#default_value' => isset($data['target']) ? $data['target'] : '',
    '#parents' => array('targeting', $key, 'name'),
  );
  $form['settings']['targeting_settings']['targeting'][$key]['value'] = array(
    '#type' => 'textfield',
    '#title_display' => 'invisible',
    '#title' => t('Target Value'),
    '#default_value' => isset($data['value']) ? $data['value'] : '',
    '#parents' => array('targeting', $key, 'message'),
  );
}



