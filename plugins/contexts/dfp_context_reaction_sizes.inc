<?php

/**
 * @file
 * Context reaction plugin for DFP ads.
 */

/**
 * Expose DFP tags as context reactions.
 */
class dfp_context_reaction_sizes extends context_reaction {
  /**
   * Allow admins to choose what DFP adunit to override.
   */
  function options_form($context) {
    $form = array(
      '#theme' => 'dfp_size_settings',
    );

    // Get existing values for this form.
    $values = $this->fetch_from_context($context);

    // Get a list of all DART tags.
    $tags = dfp_tag_load_all();
    foreach ($tags as $tag) {
      $form[$tag->machinename . '_size'] = array(
        '#type' => 'textfield',
        '#title' => check_plain($tag->slot),
        '#title_display' => 'invisible',
        '#description' => t('Example: 300x600,300x250'),
        '#default_value' => isset($values[$tag->machinename . '_size']) ? $values[$tag->machinename . '_size'] : '',
      );
    }

    return $form;
  }

  /**
   * override all tags based on context.
   */
  function execute(&$tag) {
    // Check each currently set context to see if the DART tag specified by
    // machinename should be overridden or not.
    foreach ($this->get_contexts() as $context_name => $context) {
      $new_size = isset($context->reactions['dfp_adsize'][$tag->machinename . '_size']) ? $context->reactions['dfp_adsize'][$tag->machinename . '_size'] : '';
      $tag->size = !empty($new_size) ? $new_size : $tag->size;
    }
  }
}
