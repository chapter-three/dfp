<?php

/**
 * Form builder for the global DFP settings form.
 */
function dfp_admin_settings($form, $form_state) {
  // Make sure that all the necessary files are included even on ajax callbacks.
  form_load_include($form_state, 'inc', 'dfp', 'plugins/export_ui/dfp_ctools_export_ui');

  $form['#submit'][] = 'dfp_admin_form_submit';

  $form['settings'] = array(
    '#type' => 'vertical_tabs',
  );

  // Default tag settings.
  $form['global_tag_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Global Tag Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
    '#attached' => array(
      'js' => array(
        'vertical-tabs' => drupal_get_path('module', 'dfp') . '/dfp.admin.js',
      ),
    ),
  );
  $form['global_tag_settings']['dfp_network_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Network ID'),
    '#default_value' => variable_get('dfp_network_id', ''),
    '#required' => TRUE,
    '#description' => t('The Network ID to use on all tags.'),
  );
  $form['global_tag_settings']['dfp_default_adunit'] = array(
    '#type' => 'textfield',
    '#title' => t('Default Ad Unit'),
    '#default_value' => variable_get('dfp_default_adunit', ''),
    '#required' => FALSE,
    '#description' => t('Enter a default value to use only if a tag has no Ad Unit specified.'),
  );
  $form['global_tag_settings']['dfp_async_rendering'] = array(
    '#type' => 'checkbox',
    '#title' => t('Render ads asynchronously'),
    '#default_value' => variable_get('dfp_async_rendering', 1),
    '#description' => t('This can speed up page rendering time by loading page content without waiting for ads to load.'),
  );
  $form['global_tag_settings']['dfp_single_request'] = array(
    '#type' => 'checkbox',
    '#title' => t('Combine all ad requests into a single request'),
    '#default_value' => variable_get('dfp_single_request', 1),
    '#description' => t('This can speed up page rendering time by limiting the number of external requests.'),
  );

  // Global display options.
  $form['global_display_options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Global Display Options'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $form['global_display_options']['dfp_slug'] = array(
    '#type' => 'textfield',
    '#title' => t('Global Slug'),
    '#default_value' => variable_get('dfp_slug', '<none>'),
    '#required' => FALSE,
    '#description' => t('Slug all ad tags with this label. Use @tag for no slug. Example: Advertisement', array('@tag' => '<none>')),
  );
  $form['global_display_options']['dfp_use_noscript'] = array(
    '#type' => 'checkbox',
    '#title' => t('include @tag tags', array('@tag' => '<noscript>')),
    '#default_value' => variable_get('dfp_use_noscript', 0),
    '#description' => t('Include @tag tags along with the javascript tags.', array('@tag' => '<noscript>')),
  );
  $form['global_display_options']['dfp_collapse_empty_divs'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hide ad slots if no ad is served'),
    '#default_value' => variable_get('dfp_collapse_empty_divs', 1),
    '#description' => t('This can speed up page rendering time by limiting the number of external requests.'),
  );

  // Global display options.
   $existing_targeting = _dfp_get_existing_targeting($form_state, variable_get('dfp_targeting', array()));
  _dfp_targeting_form($form, $form_state, $existing_targeting);

  // Javascript.
  $form['javascript'] = array(
    '#type' => 'fieldset',
    '#title' => t('Inject Javascript'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => 20,
    '#group' => 'settings',
  );
  $form['javascript']['dfp_injected_js'] = array(
    '#type' => 'textarea',
    '#title' => t('Inject javascript'),
    '#description' => t('Inject this javascript into the @tag on every page request. The googletag object is available to use in this code.', array('@tag' => '<head>')),
    '#default_value' => variable_get('dfp_injected_js', ''),
    '#rows' => 5,
  );

  return system_settings_form($form);
}

/**
 * Form submit function for the DFP admin form.
 */
function dfp_admin_form_submit($form, &$form_state) {
  // Rename the targeting variable to avoid namespace collisions.
  $form_state['values']['dfp_targeting'] = $form_state['values']['targeting'];
  unset($form_state['values']['targeting']);
}

/**
 * Validation function used by the targeting form.
 */
function dfp_targeting_form_validate($form, &$form_state) {
  // Remove empty targeting values before storing them in the database.
  foreach ($form_state['values']['targeting'] as $key => $target) {
    if (empty($target['target']) && empty($target['value'])) {
      unset($form_state['values']['targeting'][$key]);
    }
  }
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
  return $form['targeting_settings']['targeting'];
}

/**
 * Theme function for the "target" form.
 */
function theme_dfp_target($variables) {
  $form = $variables['form'];

  $more_button = drupal_render($form['dfp_more_targets']);
  unset($form['dfp_more_targets']);

  $headers = array(t('Target'), t('Value(s)'));
  $rows = array();
  foreach (element_children($form) as $key) {
    $rows[] = array(drupal_render($form[$key]['target']), drupal_render($form[$key]['value']));
  }

  return theme('table', array('header' => $headers, 'rows' => $rows)) . $more_button;
}

/**
 * Helper form builder for the targeting form.
 */
function _dfp_targeting_form(&$form, &$form_state, $existing_targeting = array()) {
  $form['#validate'][] = 'dfp_targeting_form_validate';

  // Display settings.
  $form['targeting_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Targeting'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $form['targeting_settings']['targeting'] = array(
    '#tree' => FALSE,
    '#prefix' => '<div id="dfp-targeting-wrapper">',
    '#suffix' => '</div>',
    '#theme' => 'dfp_target',
  );

  // Add existing targets to the form.
  foreach ($existing_targeting as $key => $data) {
    _dfp_target_form($form, $key, $data);
  }
  // Add one blank set of target fields.
  _dfp_target_form($form, count($existing_targeting) + 1);

  $form['targeting_settings']['targeting']['dfp_more_targets'] = array(
    '#type' => 'submit',
    '#value' => t('Add another target'),
    '#submit' => array('dfp_more_targets_submit'),
    '#limit_validation_errors' => array(),
    '#ajax' => array(
      'callback' => 'dfp_more_targets_js',
      'wrapper' => 'dfp-targeting-wrapper',
      'effect' => 'fade',
      'file' => 'plugins/export_ui/dfp_ctools_export_ui.inc',
    ),
  );
}

/**
 * Helper form builder for an individual target.
 */
function _dfp_target_form(&$form, $key, $data = array()) {
  $form['targeting_settings']['targeting'][$key] = array(
    '#prefix' => '<div class="target" id="target-' . $key . '">',
    '#suffix' => '</div>',
  );
  $form['targeting_settings']['targeting'][$key]['target'] = array(
    '#type' => 'textfield',
    '#title_display' => 'invisible',
    '#title' => t('Target Name'),
    '#size' => 10,
    '#default_value' => isset($data['target']) ? $data['target'] : '',
    '#parents' => array('targeting', $key, 'target'),
    '#attributes' => array('class' => array('field-target-target')),
  );
  $form['targeting_settings']['targeting'][$key]['value'] = array(
    '#type' => 'textfield',
    '#title_display' => 'invisible',
    '#title' => t('Target Value'),
    '#size' => 20,
    '#default_value' => isset($data['value']) ? $data['value'] : '',
    '#parents' => array('targeting', $key, 'value'),
    '#attributes' => array('class' => array('field-target-value')),
  );
  if (empty($data)) {
    $form['targeting_settings']['targeting'][$key]['target']['#description'] = t('Example: state');
    $form['targeting_settings']['targeting'][$key]['value']['#description'] = t('Example: ny, nj, ct');
  }
}

/**
 * Returns the current targets. The default value will be used unless an "input"
 * exists in the form_state variable, in which case that will be used.
 */
function _dfp_get_existing_targeting($form_state, $default = array()) {
  $targeting = array();
  if (isset($form_state['input']['targeting'])) {
    $targeting = $form_state['input']['targeting'];
  }
  elseif (!empty($default)) {
    $targeting = $default;
  }

  return $targeting;
}






