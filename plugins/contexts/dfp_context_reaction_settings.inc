<?php

/**
 * @file
 * Context reaction plugin for DFP ads.
 */

/**
 * Expose DFP variables as context reactions.
 */
class dfp_context_reaction_settings extends context_reaction {
  /**
   * Allow admins to choose what DART tags to hide.
   */
  function options_form($context) {
    module_load_include('inc', 'dfp', 'dfp.admin');

    // Get existing values for this form.
    $values = $this->fetch_from_context($context);

    // Build the targeting form.
    $form = array();
    _dfp_targeting_form($form, isset($values['targeting']) ? $values['targeting'] : array());

    // Modify the #parents property to make sure values are stored in the
    // location that contexts expects them.
    $parents = array('reactions', 'plugins', 'dfp_settings');
    foreach (element_children($form['targeting']) as $key) {
      if (is_numeric($key)) {
        $form['targeting'][$key]['target']['#parents'] = array_merge($parents, $form['targeting'][$key]['target']['#parents']);
        $form['targeting'][$key]['value']['#parents'] = array_merge($parents, $form['targeting'][$key]['value']['#parents']);
      }
    }
    // Modify form to call the contexts-specific versions of certain functions.
    $form['targeting']['dfp_more_targets']['#submit'] = array('dfp_contexts_more_targets_submit');
    $form['targeting']['dfp_more_targets']['#ajax']['callback'] = 'dfp_contexts_more_targets_js';

    return $form;
  }

  /**
   * Return a keyed array containing the settings.
   */
  function execute(&$tag) {
    foreach ($this->get_contexts() as $context_name => $context) {
      foreach ($context->reactions['dfp_settings']['targeting'] as $key => $value) {
        $tag->settings['targeting'][] = $value;
      }
    }
  }
}

/**
 * Submit handler to add more targets to an ad tag.
 */
function dfp_contexts_more_targets_submit($form, &$form_state) {
  $form_state['targeting'] = $form_state['input']['reactions']['plugins']['dfp_settings']['targeting'];
  $form_state['rebuild'] = TRUE;
}

/**
 * Ajax callback for add more targets to an ad tag.
 */
function dfp_contexts_more_targets_js($form, $form_state) {
  return $form['reactions']['plugins']['dfp_settings']['targeting'];
}

