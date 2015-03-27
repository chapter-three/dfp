<?php

/**
 * @file
 * The 'DFP Panel' custom content type.
 */

/**
 * Callback function to supply a list of content types.
 */
$plugin = array(
  'title' => t('DFP Pane'),
  'description' => t('Custom Implementation for DFP Ads.'),
  'category' => t('Custom'),
  'single' => TRUE,
  'edit form' => 'dfp_dfp_pane_content_type_edit_form',
  'render callback' => 'dfp_dfp_pane_render',
);

/**
 * Output function for the 'DFP Panel' content type.
 */
function dfp_dfp_pane_render($subtype, $conf, $panel_args, $context) {
  $content = array();

  foreach ($conf['dfp_ads'] as $key => $ad) {
    if (!empty($ad)) {
      $content[$key] = dfp_tag($ad);
    }
  }

  $block = new stdClass();
  $block->content = $content;
  return $block;
}

/**
 * Returns an edit form for the custom type.
 */
function dfp_dfp_pane_content_type_edit_form($form, &$form_state) {
  $tags = dfp_tag_load_all();

  $tag_options = array();
  foreach ($tags as $tag) {
    $tag_options[$tag->machinename] = $tag->slot;
  }

  $form['dfp_ads'] = array(
    '#type' => 'checkboxes',
    '#options' => $tag_options,
    '#default_value' => $form_state['conf']['dfp_ads'],
    '#title' => t('Choose the ad slots to show in this location.'),
  );

  return $form;
}

/**
 * Submit function for the content type.
 */
function dfp_dfp_pane_content_type_edit_form_submit(&$form, &$form_state) {
  $form_state['conf']['dfp_ads'] = $form_state['values']['dfp_ads'];
}
