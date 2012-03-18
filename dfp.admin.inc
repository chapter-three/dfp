<?php

/**
 * Form builder for the global DFP settings form.
 */
function dfp_admin_settings($form, $form_state) {
  // Make sure that all the necessary files are included even on ajax callbacks.
  form_load_include($form_state, 'inc', 'dfp', 'plugins/export_ui/dfp_ctools_export_ui');

  $form['settings'] = array(
    '#type' => 'vertical_tabs',
  );

  $form['#submit'][] = 'dfp_admin_form_submit';

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
  $form['global_display_options']['dfp_collapse_empty_divs'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hide ad slots if no ad is served'),
    '#default_value' => variable_get('dfp_collapse_empty_divs', 1),
  );

  // Global targeting options.
  $form['targeting_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Global Targeting'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $existing_targeting = _dfp_targeting_get_existing($form_state, variable_get('dfp_targeting', array()));
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
 * Submit handler ensures that targeting values are saved with the "dfp" prefix.
 */
function dfp_admin_form_submit($form, &$form_state) {
  // Since the targeting form is reusable it isn't already in the settings
  // array so we grab it here.
  $form_state['values']['dfp_targeting'] = $form_state['values']['targeting'];
  unset($form_state['values']['targeting']);
}

/**
 * Validation function used by the targeting form.
 */
function dfp_target_form_validate($element, &$form_state, $form) {
  if (empty($element['target']['#value']) && !empty($element['value']['#value'])) {
    form_error($element['target'], t('The target cannot be empty if a value exists.'));
  }
  elseif (!empty($element['target']['#value']) && empty($element['value']['#value'])) {
    form_error($element['value'], t('The value cannot be empty if a target exists.'));
  }
}

/**
 * Submit handler ensures that empty targeting values are not saved.
 */
function dps_targeting_form_submit($form, &$form_state) {
  if (array_key_exists('targeting', $form_state['values'])) {
    _dfp_targeting_remove_empty($form_state['values']['targeting']);
  }
  else {
    // Use the #array_parents property to walk through form_state['values'].
    // This should only be necessary if the #parents property was set for
    // the targeting form.
    $targeting = _dfp_get_form_item('targeting', $form);
    $item = &$form_state['values'];
    foreach ($targeting['#array_parents'] as $parent) {
      if (array_key_exists($parent, $item)) {
        $item = &$item[$parent];
      }
      else {
        $item = array();
        break;
      }
    }

    _dfp_targeting_remove_empty($item);
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
 * Ajax callback for adding targets to the targeting form.
 */
function dfp_more_targets_js($form, $form_state) {
  return $form['targeting_settings']['targeting'];
}

/**
 * Helper form builder for the targeting form.
 */
function _dfp_targeting_form(&$targeting_form, &$form, $existing_targeting = array()) {
  if (isset($form['#submit']) && is_array($form['#submit'])) {
    array_unshift($form['#submit'], 'dps_targeting_form_submit');
  }

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
  $targeting_form['tokens'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array('dfp_tag', 'node', 'term', 'user'), // The token types that have specific context. Can be multiple token types like 'term' and/or 'user'
    '#global_types' => TRUE, // A boolean TRUE or FALSE whether to include 'global' context tokens like [current-user:*] or [site:*]. Defaults to TRUE.
    '#click_insert' => TRUE, // A boolean whether to include the 'Click this token to insert in into the the focused textfield' JavaScript functionality. Defaults to TRUE.
  );
}

/**
 * Helper form builder for an individual target.
 */
function _dfp_target_form(&$form, $key, $data = array()) {
  $form['targeting'][$key] = array(
    '#prefix' => '<div class="target" id="target-' . $key . '">',
    '#suffix' => '</div>',
    '#element_validate' => array('dfp_target_form_validate'),
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
    $form['targeting'][$key]['target']['#description'] = t('Example: color');
    $form['targeting'][$key]['value']['#description'] = t('Example: red,white,blue');
  }
}

/**
 * Returns the current targets. The default value will be used unless an "input"
 * exists in the form_state variable, in which case that will be used.
 */
function _dfp_targeting_get_existing($form_state, $default = array()) {
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
 * Helper function to remove empty target values from a given array.
 */
function _dfp_targeting_remove_empty(&$target) {
  foreach ($target as $key => $val) {
    if (empty($val['target']) && empty($val['value'])) {
      unset($target[$key]);
    }
  }
  // Reset the target array's indexes to prevents strange behavior when users
  // leave a blank row in the targeting form followed by a filled-in row. In
  // this case clicking "Add another target" will not work as expected.
  $target = array_values($target);
}

/**
 * Helper function returns the a value from a givne multidimensional array given
 * a specific key. If the key exists in more than one place, the first occurance
 * is used.
 */
function _dfp_get_form_item($needle_key, $array) {
  $children = element_children($array);

  foreach ($children as $child) {
    if($child == $needle_key) {
      return $array[$child];
    }
    elseif(is_array($array[$child])) {
      $result = _dfp_get_form_item($needle_key, $array[$child]);
      if($result) {
        return $result;
      }
    }
  }

  return FALSE;
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
function theme_dfp_adsense_color_settings($variables) {
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

  $output = '  <label>' . t('Color Settings for Text Ads') . '</label>';
  $output .= theme('table', array('attributes' => array('id' => 'color-settings'), 'header' => $header, 'rows' => $rows));

  return $output;
}










