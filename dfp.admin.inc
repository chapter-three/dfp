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
  $form['global_tag_settings']['dfp_default_adsense_channel_ids'] = array(
    '#type' => 'textfield',
    '#title' => t('AdSense Channel ID(s)'),
    '#default_value' => variable_get('dfp_default_adsense_channel_ids', ''),
    '#required' => FALSE,
    '#description' => t('Example: 271828183+314159265'),
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
  $form['global_display_options']['dfp_default_slug'] = array(
    '#type' => 'textfield',
    '#title' => t('Global Slug'),
    '#default_value' => variable_get('dfp_default_slug', ''),
    '#required' => FALSE,
    '#description' => t('Slug all ad tags with this label. Example: Advertisement', array('@tag' => '<none>')),
  );
  // $form['global_display_options']['dfp_use_noscript'] = array(
  //   '#type' => 'checkbox',
  //   '#title' => t('include @tag tags', array('@tag' => '<noscript>')),
  //   '#default_value' => variable_get('dfp_use_noscript', 0),
  //   '#description' => t('Include @tag tags along with the javascript tags.', array('@tag' => '<noscript>')),
  // );
  $form['global_display_options']['dfp_collapse_empty_divs'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hide ad slots if no ad is served'),
    '#default_value' => variable_get('dfp_collapse_empty_divs', 1),
  );

  // Global display options.
  // //@todo: put this in a sep dfp_taxonomy module
  // $form['targeting_settings']['dfp_targeting_terms'] = array(
  //   '#type' => 'checkbox',
  //   '#title' => t('Automatically target taxonomy terms on node and term pages'),
  //   '#default_value' => variable_get('dfp_collapse_empty_divs', 1),
  //   '#description' => t('The target will be "terms" and the value will be the term name(s).'),
  // );

  $form['targeting_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Global Targeting'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $existing_targeting = _dfp_get_existing_targeting($form_state, variable_get('dfp_targeting', array()));
  _dfp_targeting_form($form['targeting_settings'], $form, $existing_targeting);

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
  foreach ($form_state['values']['targeting'] as $key => $value) {
    if (empty($value['target']) && !empty($value['value'])) {
      form_set_error('targeting][' . $key . '][target', t('The target cannot be empty if a value exists.'));
    }
    elseif (!empty($value['target']) && empty($value['value'])) {
      form_set_error('targeting][' . $key . '][value', t('The value cannot be empty if a target exists.'));
    }
    elseif (empty($value['target']) && empty($value['value'])) {
      unset($form_state['values']['targeting'][$key]);
    }
  }
}

/**
 * Submit handler to add more targets to an ad tag.
 */
function dfp_more_targets_submit($form, &$form_state) {
  $form_state['targeting'] = $form_state['input']['targeting'];
  $form_state['rebuild'] = TRUE;
}

/**
 * Ajax callback for adding new messages to the broadcast messages form.
 */
function dfp_more_targets_js($form, $form_state) {
  return $form['targeting_settings']['targeting'];
}

/**
 * Helper form builder for the targeting form.
 */
function _dfp_targeting_form(&$targeting_form, &$form, $existing_targeting = array()) {
  $form['#validate'][] = 'dfp_targeting_form_validate';

  // Display settings.
  $targeting_form['targeting'] = array(
    '#tree' => FALSE,
    '#prefix' => '<div id="dfp-targeting-wrapper">',
    '#suffix' => '</div>',
    '#theme' => 'dfp_target_settings',
  );

  // Add existing targets to the form.
  foreach ($existing_targeting as $key => $data) {
    _dfp_target_form($targeting_form, $key, $data);
  }
  // Add one blank set of target fields.
  _dfp_target_form($targeting_form, count($existing_targeting) + 1);

  $targeting_form['targeting']['dfp_more_targets'] = array(
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
  $form['targeting'][$key] = array(
    '#prefix' => '<div class="target" id="target-' . $key . '">',
    '#suffix' => '</div>',
  );
  $form['targeting'][$key]['target'] = array(
    '#type' => 'textfield',
    '#title_display' => 'invisible',
    '#title' => t('Target Name'),
    '#size' => 10,
    '#default_value' => isset($data['target']) ? $data['target'] : '',
    '#parents' => array('targeting', $key, 'target'),
    '#attributes' => array('class' => array('field-target-target')),
  );
  $form['targeting'][$key]['value'] = array(
    '#type' => 'textfield',
    '#title_display' => 'invisible',
    '#title' => t('Target Value'),
    '#size' => 20,
    '#default_value' => isset($data['value']) ? $data['value'] : '',
    '#parents' => array('targeting', $key, 'value'),
    '#attributes' => array('class' => array('field-target-value')),
  );
  if (empty($data)) {
    $form['targeting'][$key]['target']['#description'] = t('Example: state');
    $form['targeting'][$key]['value']['#description'] = t('Example: ny, nj, ct');
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

/**
 * Theme function for the "target" form.
 */
function theme_dfp_target_settings($variables) {
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
 * Theme function for the "target" form.
 */
function theme_dfp_color_settings($variables) {
  $form = $variables['form'];

  $caption = t('AdSense Color Settings');
  $header = array(t('Color Setting'), t('Hex color value'), t('Sample'));
  $rows = array();
  foreach (element_children($form) as $key) {
    $rows[] = array(
      $form[$key]['#title'],
      drupal_render($form[$key]),
      "<div class='color-sample' style='background-color: #" . $form[$key]['#default_value'] . ";'>&nbsp;</div>",
    );
  }

  $output = '<div class="form-item">';
  $output .= '  <label>' . t('Color Settings') . '</label>';
  $output .= '  <div class="description">' . t('Primarily used for text ads these settings can be used to override the color settings defined in the DFP tool.') . '</div>';
  $output .= theme('table', array('attributes' => array('id' => 'color-settings'), 'header' => $header, 'rows' => $rows));
  $output .= '</div>';

  return $output;
}










