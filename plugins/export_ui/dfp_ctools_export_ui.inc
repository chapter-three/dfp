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

  $form['machinename'] = $form['info']['machinename'];
  $form['machinename']['#title'] = t('Unique Name');
  $form['machinename']['#maxlength'] = 128;
  $form['machinename']['#description'] = t('Only use lowercase letters, numbers and dashes. This name will be used as the "id" attribute in your markup.');
  unset($form['info']);

  $form['size'] = array(
    '#type' => 'textfield',
    '#title' => t('Size(s)'),
    '#description' => t('Example: 300x600,300x250'),
    '#required' => TRUE,
    '#maxlength' => 64,
    '#default_value' => $tag->size,
  );

  $form['slug'] = array(
    '#type' => 'textfield',
    '#title' => t('Slug'),
    '#description' => t('Override the default slug for this ad tag. Use !none for no slug. Leave this field empty to use the default slug. Example: Advertisement', array('!none' => check_plain('<none>'))),
    '#required' => FALSE,
    '#maxlength' => 64,
    '#default_value' => $tag->settings['overrides']['slug'],
  );

  // Display options.
  $form['block'] = array(
    '#type' => 'checkbox',
    '#title' => t('Create a block for this ad tag'),
    '#description' => t('Create a block (configurable at !url) in which this ad tag is displayed.', array('!url' => l('admin/build/block', 'admin/build/block'))),
    '#default_value' => $tag->block,
  );
  $form['scriptless'] = array(
    '#type' => 'checkbox',
    '#title' => t('Render this tag without javascript'),
    '#description' => t('Use this option for ads included in emails.'),
    '#default_value' => $tag->settings['options']['scriptless'],
  );

  // Key/val pairs.
  // @todo: add the ability to dynamically add more key|val form elements (D7)
  // $key_val_count = max(DART_MAX_KEY_VALS, count($tag->settings['key_vals']) + 5);

  // $form['settings']['key_vals_group'] = array(
  //   '#type' => 'fieldset',
  //   '#title' => t('Key|Value Pairs'),
  //   '#attributes' => array('#id' => 'key_vals'),
  //   '#collapsible' => TRUE,
  //   '#collapsed' => FALSE,
  // );
  // $form['settings']['key_vals_group']['key_vals_form_count'] = array(
  //   '#type' => 'hidden',
  //   '#value' => $key_val_count,
  // );
  // // Container for the key_value forms.
  // $form['settings']['key_vals_group']['key_vals'] = array(
  //   '#theme' => 'dart_key_vals_form',
  // );
  // for ($i = 0; $i < $key_val_count; $i++) {
  //   $form['settings']['key_vals_group']['key_vals'][$i] = _dart_key_val_form($i, isset($tag->settings['key_vals'][$i]) ? $tag->settings['key_vals'][$i] : null);
  // }

  dpm ($form);

}

/**
 * Form validation for the dart_tag form.
 */
function dfp_ad_tag_form_validate(&$form, &$form_state) {

}

/**
 * Form submission for the dart_tag form.
 */
function dfp_ad_tag_form_submit(&$form, &$form_state) {

}

