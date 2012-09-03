<?php

/**
 * @file
 * Context reaction plugin for DFP ads.
 */

/**
 * Expose DFP tags as context reactions.
 */
class dfp_context_reaction_tags extends context_reaction {
  /**
   * Allow admins to choose what DFP tags to hide.
   */
  function options_form($context) {
    // Get existing values for this form.
    $values = $this->fetch_from_context($context);

    // Get a list of all DART tags.
    $options = array();
    $tags = dfp_tag_load_all();
    foreach ($tags as $tag) {
      $options[$tag->machinename] = $tag->slot;
    }

    $form = array(
      '#title' => t('Hide these DFP tags'),
      '#description' => t('The following DFP tags will not be displayed.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => isset($values) ? $values : array(),
    );

    return $form;
  }

  /**
   * Disable any tags that should be disabled based on context.
   */
  function execute(&$tag) {
    // Check each currently set context to see if the DART tag specified by
    // machinename should be displayed or not.
    foreach ($this->get_contexts() as $context_name => $context) {
      if (isset($context->reactions['dfp_tags']) && in_array($tag->machinename, $context->reactions['dfp_tags'], TRUE)) {
        $tag->disabled = TRUE;
        break;
      }
    }
  }
}
