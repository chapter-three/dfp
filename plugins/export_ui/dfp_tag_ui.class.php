<?php

/**
 * @file
 * A custom Ctools Export UI class for DART Tags.
 */

/**
 * Customizations of the DART Tags UI.
 */
class dfp_tag_ui extends ctools_export_ui {

  /**
   * Prepare the item object before the edit form is rendered.
   */
  function edit_form(&$form, &$form_state) {
    $form_state['item']->settings = unserialize($form_state['item']->settings);

    parent::edit_form($form, $form_state);
  }

  /**
   * Prepare the tag values before they are added to the database.
   */
  function edit_form_submit(&$form, &$form_state) {
    $settings = $form_state['item']->settings;

    // Remove empty targeting values before storing them in the database.
    foreach ($form_state['values']['targeting'] as $key => $target) {
      if (empty($target['target']) && empty($target['value'])) {
        unset($form_state['values']['targeting'][$key]);
      }
    }

    $settings['targeting'] = $form_state['values']['targeting'];
    $settings['slug'] = $form_state['values']['slug'];
    $settings['block'] = $form_state['values']['block'];
    $settings['scriptless'] = $form_state['values']['scriptless'];

    $form_state['values']['settings'] = serialize($settings);
    parent::edit_form_submit($form, $form_state);
  }

}
