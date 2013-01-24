<?php

/**
 * @file
 * Context reaction plugin for DFP ads.
 */

/**
 * Expose DFP tags as context reactions.
 */
class dfp_context_reaction_adunit extends context_reaction {
  /**
   * Allow admins to choose what DFP adunit to override.
   */
  function options_form($context) {
    $form = array();

    // Get existing values for this form.
    $values = $this->fetch_from_context($context);

    // Get a list of all DART tags.
    $options = array();
    $tags = dfp_tag_load_all();

    foreach ($tags as $tag) {
      $options[$tag->machinename] = $tag->slot;
    }

    $used_tags = implode(', ', $options);

    $form['adunit_override_tags'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Tags to override'),
      '#description' => t("When this context is active, the tags selected here will have their ad unit overridden."),
      '#options' => $options,
      '#default_value' => isset($values['adunit_override_tags']) ? $values['adunit_override_tags'] : array(),
    );
    $form['adunit_override'] = array(
      '#title' => t('Override these DFP tags with a custom ad unit'),
      '#description' => t('Use the tokens below to define how the ad unit should display. The network id will be included automatically. Example: [dfp_tag:url_parts:4]/[dfp_tag:slot]'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => isset($values['adunit_override']) ? $values['adunit_override'] : '',
    );
    $form['tokens'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array('dfp_tag', 'node', 'term', 'user'),
      '#global_types' => TRUE,
      '#click_insert' => TRUE,
      '#dialog' => TRUE,
    );

    return $form;
  }

  /**
   * override all tags based on context.
   */
  function execute(&$tag) {
    // Check each currently set context to see if the DART tag specified by
    // machinename should be overridden or not.
    foreach ($this->get_contexts() as $context_name => $context) {
      if (isset($context->reactions['dfp_adunit'])) {
      $tags = $context->reactions['dfp_adunit']['adunit_override_tags'];
      if (isset($tags[$tag->machinename]) && !empty($tags[$tag->machinename])) {
        $tag->adunit = $context->reactions['dfp_adunit']['adunit_override'];
      }
      break;
      }
    }
  }
}
