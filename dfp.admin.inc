<?php

/**
 * @file
 * Admin forms and functinality for DFP ads.
 */

/**
 * Form builder for the global DFP settings form.
 */
function dfp_admin_settings($form, $form_state) {
  // Make sure that all the necessary files are included even on ajax callbacks.
  form_load_include($form_state, 'inc', 'dfp', 'plugins/export_ui/dfp_ctools_export_ui');

  $form['settings'] = array(
    '#type' => 'vertical_tabs',
  );

  $form['#validate'][] = 'dfp_admin_form_validate';
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
    '#description' => t('The Network ID to use on all tags. According to Google this value should begin with a /.'),
  );
  $form['global_tag_settings']['dfp_default_adunit'] = array(
    '#type' => 'textfield',
    '#title' => t('Default Ad Unit Pattern'),
    '#required' => FALSE,
    '#maxlength' => 255,
    '#default_value' => variable_get('dfp_default_adunit', ''),
    '#description' => t('Use the tokens below to define how the default ad unit should display. This can be overridden on each tag. The network id will be included automatically. Example: [current-page:url:args:value:0]/[current-page:url:args:value:1]/[dfp_tag:slot]'),
  );
  $form['global_tag_settings']['dfp_click_url'] = array(
    '#type' => 'textfield',
    '#title' => t('DFP Click URL (Sync mode only)'),
    '#default_value' => variable_get('dfp_click_url', ''),
    '#description' => t('Sets a click URL on each DFP tag. Useful for intercepting ad click calls for reporting. Values beginning with http:// are treated as absolute; otherwise relative to current site domain.'),
    '#states' => array(
      'enabled' => array(
        'input[name="dfp_async_rendering"]' => array('checked' => FALSE),
      ),
    ),
  );
  $form['global_tag_settings']['tokens'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array('dfp_tag', 'node', 'term', 'user'),
    '#global_types' => TRUE,
    '#click_insert' => TRUE,
    '#dialog' => TRUE,
  );
  $form['global_tag_settings']['dfp_async_rendering'] = array(
    '#type' => 'checkbox',
    '#title' => t('Render ads asynchronously'),
    '#default_value' => variable_get('dfp_async_rendering', 1),
    '#description' => t('This can speed up page rendering time by loading page content without waiting for ads to load.'),
  );
  $form['global_tag_settings']['dfp_disable_init_load'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable initial ad load'),
    '#default_value' => variable_get('dfp_disable_init_load', 0),
    '#description' => t('(Async mode only) Disables the initial fetch of ads from Google when the page is first loaded. Calls to refresh() can still be used to fetch ads.'),
     '#states' => array(
      'enabled' => array(
        'input[name="dfp_async_rendering"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['global_tag_settings']['dfp_single_request'] = array(
    '#type' => 'checkbox',
    '#title' => t('Combine all ad requests into a single request'),
    '#default_value' => variable_get('dfp_single_request', 1),
    '#description' => t('This can speed up page rendering time by limiting the number of external requests.'),
  );
  $form['global_tag_settings']['dfp_enable_ad_categories'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable DFP Ad Categories'),
    '#default_value' => variable_get('dfp_enable_ad_categories', 0),
    '#description' => t('Example: if you have an "animals" vocabulary and you want to target the same ads to "dogs", "cats" and "hamsters" you can edit each of those terms and set the DFP Ad Category to "pets". Whenever the taxonomy terms are included as targeting values, anything tagged "cats" will target "pets" instead.'),
  );
  if (!module_exists('taxonomy')) {
    $form['global_tag_settings']['dfp_enable_ad_categories']['disabled'] = TRUE;
    $form['global_tag_settings']['dfp_enable_ad_categories']['#default_value'] = 0;
    $form['global_tag_settings']['dfp_enable_ad_categories']['#description'] .= t('This requires the taxonomy module which is currently disabled.');
  }
  $bundles = field_info_bundles('taxonomy_term');
  $options = array();
  foreach ($bundles as $key => $value) {
    if ($key != 'dfp_ad_categories') {
      $options[$key] = $value['label'];
    }
  }
  $form['global_tag_settings']['dfp_enable_ad_categories_bundles'] = array(
    '#type' => 'checkboxes',
    '#options' => $options,
    '#prefix' => '<div style="margin-left: 20px">',
    '#suffix' => '</div>',
    '#default_value' => variable_get('dfp_enable_ad_categories_bundles', array()),
    '#description' => t('Select the vocabularies on which DFP Ad Categories should be enabled.'),
    '#states' => array(
      'visible' => array(
        'input[name="dfp_enable_ad_categories"]' => array('checked' => TRUE),
      ),
    ),
  );
  $form['global_tag_settings']['dfp_token_cache_lifetime'] = array(
    '#type' => 'textfield',
    '#title' => t("Token cache lifetime"),
    '#default_value' => variable_get("dfp_token_cache_lifetime", 0),
    '#description' => t('The time, in seconds, that the DFP token cache will be valid for. The token cache will always be cleared at the next system cron run after this time period, or when this form is saved.'),
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
    '#type' => 'radios',
    '#title' => t('Collapse empty divs'),
    '#default_value' => variable_get('dfp_collapse_empty_divs', 1),
    '#options' => array(
      0 => t('Never'),
      1 => t('Collapse only if no ad is served'),
      2 => t('Expand only if an ad is served'),
    ),
    '#description' => t('<dl><dt>Never</dt><dd>Never collapse ad slots.</dd><dt>Collapse only</dt><dd>Collapse before any ad is loaded. Useful if ad slots will get filled most of the time.</dd><dt>Expand only</dt><dd>Collapse all divs on the page before any ads are fetched and expand if an ad is loaded into the ad slot. Useful if ad slots will stay empty most of the time.</dd></dl>'),
  );
  $form['global_display_options']['dfp_slug_placement'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hide slug if no ad is served (recommended)'),
    '#default_value' => variable_get('dfp_slug_placement', 1),
    '#states' => array(
      'visible' => array(
        'input[name="dfp_collapse_empty_divs"]' => array('!value' => 0),
      ),
    ),
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
  _dfp_targeting_form($form['targeting_settings'], $existing_targeting);

  // AdTest Settings
  $form['adtest'] = array(
    '#type' => 'fieldset',
    '#title' => t('Ad Test Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#group' => 'settings',
  );
  $form['adtest']['dfp_adtest_adunit'] = array(
    '#type' => 'textfield',
    '#title' => t('Ad Unit Pattern for Ad Tests'),
    '#description' => t('Override the Ad Unit value for all the ad tags on a page by adding ?adtest=true to the URL. Use the tokens below to define how the ad unit should display. Example: [dfp_tag:network_id]/test/[dfp_tag:slot]'),
    '#default_value' => variable_get('dfp_adtest_adunit', ''),
  );
  $form['adtest']['tokens'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array('dfp_tag', 'node', 'term', 'user'),
    '#global_types' => TRUE,
    '#click_insert' => TRUE,
    '#dialog' => TRUE,
  );

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
    '#title' => t('Inject javascript') . ' 1',
    '#description' => t('Inject this javascript into the @tag on every page request immediately after the googletag object becomes available.', array('@tag' => '<head>')),
    '#default_value' => variable_get('dfp_injected_js', ''),
    '#rows' => 5,
  );
  $form['javascript']['dfp_injected_js2'] = array(
    '#type' => 'textarea',
    '#title' => t('Inject javascript') . ' 2',
    '#description' => t('Inject this javascript into the @tag on every page request immediately before the enableServices call.', array('@tag' => '<head>')),
    '#default_value' => variable_get('dfp_injected_js2', ''),
    '#rows' => 5,
  );

  return system_settings_form($form);
}

/**
 * Submit handler for the global DFP settings form.
 */
function dfp_admin_form_validate($element, &$form_state)
{
  $settings = $element['global_tag_settings'];
  if (!empty($settings['dfp_click_url']['#value']) && $settings['dfp_async_rendering']['#value'] == 1) {
    form_error($settings['dfp_click_url'], t('Setting a click URL does not work with async rendering.'));
  }
}

/**
 * Submit handler ensures that targeting values are saved with the "dfp" prefix.
 */
function dfp_admin_form_submit($form, &$form_state) {
  // Since the targeting form is reusable it isn't already in the settings
  // array so we grab it here.
  $form_state['values']['dfp_targeting'] = $form_state['values']['targeting'];
  unset($form_state['values']['targeting']);

  // Attach (or delete) an instance of the dfp_ad_categories term_reference
  // field for each vocabulary that should (or should not) have DFP Ad
  // Categories enabled.
  foreach ($form_state['values']['dfp_enable_ad_categories_bundles'] as $bundle => $enable) {
    $existing_instance = field_read_instance('taxonomy_term', 'field_dfp_ad_categories', $bundle);
    $enable = $enable && !$existing_instance && $form_state['values']['dfp_enable_ad_categories'];
    if ($enable) {
      $instance = array(
        'field_name' => 'field_dfp_ad_categories',
        'entity_type' => 'taxonomy_term',
        'label' => t('DFP Ad Category'),
        'bundle' => $bundle,
        'required' => FALSE,
        'widget' => array(
          'type' => 'options_select'
        ),
      );
      field_create_instance($instance);
    }
    elseif (!$enable && $existing_instance) {
      // Delete this field instance, but be certain not to delete the field.
      field_delete_instance($existing_instance, FALSE);
    }
  }
}

/**
 * Validation function used by an individual target in the targeting form.
 */
function dfp_target_form_validate($element, &$form_state) {
  if (empty($element['target']['#value']) && !empty($element['value']['#value'])) {
    form_error($element['target'], t('The target cannot be empty if a value exists.'));
  }
  elseif (!empty($element['target']['#value']) && empty($element['value']['#value'])) {
    form_error($element['value'], t('The value cannot be empty if a target exists.'));
  }
}

/**
 * Validation function used by the targeting form.
 */
function dfp_targeting_form_validate(&$element, &$form_state) {
  if ($form_state['triggering_element']['#value'] != t('Add another target')) {
    dfp_targeting_trim($form_state['values']);
  }
}

/**
 * Helper function that takes a form_state['values'] and removes empty targets.
 */
function dfp_targeting_trim(&$values, $parent = 'targeting') {
  foreach ($values as $key => &$val) {
    if ($key === $parent) {
      // We found the targeting values.
      foreach ($val as $k => $v) {
        if (empty($val[$k]['target']) && empty($val[$k]['value'])) {
          unset($val[$k]);
        }
      }
      // Reset the array indexes to prevent wierd behavior caused by a target
      // being removed in the middle of the array.
      $val = array_values($val);
      break;
    }
    elseif (is_array($val)) {
      dfp_targeting_trim($val, $parent);
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
 * Ajax callback for adding targets to the targeting form.
 */
function dfp_more_targets_js($form, $form_state) {
  return $form['targeting_settings']['targeting'];
}

/**
 * Helper form builder for the targeting form.
 */
function _dfp_targeting_form(&$targeting_form, $existing_targeting = array()) {
  // Display settings.
  $targeting_form['targeting'] = array(
    '#type' => 'markup',
    '#tree' => FALSE,
    '#prefix' => '<div id="dfp-targeting-wrapper">',
    '#suffix' => '</div>',
    '#theme' => 'dfp_target_settings',
    '#element_validate' => array('dfp_targeting_form_validate'),
  );

  // Add existing targets to the form unless they are empty.
  foreach ($existing_targeting as $key => $data) {
    _dfp_target_form($targeting_form, $key, $data);
  }
  // Add one blank set of target fields.
  _dfp_target_form($targeting_form, count($existing_targeting));

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
    '#token_types' => array('dfp_tag', 'node', 'term', 'user'),
    '#global_types' => TRUE,
    '#click_insert' => TRUE,
    '#dialog' => TRUE,
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
 * Validation function used by an individual breakpoint in the breakpoints form.
 */
function dfp_breakpoint_form_validate($element, &$form_state) {
  if (empty($element['browser_size']['#value']) && !empty($element['ad_sizes']['#value'])) {
    form_error($element['browser_size'], t('The browser size cannot be empty if ad size(s) exists.'));
  }
  elseif (!empty($element['browser_size']['#value']) && empty($element['ad_sizes']['#value'])) {
    form_error($element['ad_sizes'], t('The ad size(s) cannot be empty if a browser size exists.'));
  }
  if (!empty($element['browser_size']['#value']) && !empty($element['ad_sizes']['#value'])) {
    if (preg_match('/[^x|0-9]/', $element['browser_size']['#value'])) {
      form_error($element['browser_size'], t('The browser size can only contain numbers and the character x.'));
    }
    elseif (preg_match('/[^x|,|0-9]/', $element['ad_sizes']['#value'])) {
      form_error($element['ad_sizes'], t('The ad size(s) can only contain numbers, the character x and commas.'));
    }
  }
}

/**
 * Validation function used by the breakpoints form.
 */
function dfp_breakpoints_form_validate(&$element, &$form_state) {
  if ($form_state['triggering_element']['#value'] != t('Add another breakpoint')) {
    dfp_breakpoints_trim($form_state['values']);
  }
}

/**
 * Helper function that takes a form_state['values'] and removes
 * empty breakpoints.
 */
function dfp_breakpoints_trim(&$values, $parent = 'breakpoints') {
  foreach ($values as $key => &$val) {
    if ($key === $parent) {
      // We found the browser_size values.
      foreach ($val as $k => $v) {
        if (empty($val[$k]['browser_size']) && empty($val[$k]['ad_sizes'])) {
          unset($val[$k]);
        }
      }
      // Reset the array indexes to prevent wierd behavior caused by a
      // breakpoint being removed in the middle of the array.
      $val = array_values($val);
      break;
    }
    elseif (is_array($val)) {
      dfp_breakpoints_trim($val, $parent);
    }
  }
}

/**
 * Submit handler to add more breakpoints to an ad tag.
 */
function dfp_more_breakpoints_submit($form, &$form_state) {
  $form_state['breakpoints'] = $form_state['input']['breakpoints'];
  $form_state['rebuild'] = TRUE;
}

/**
 * Ajax callback for adding breakpoints to the breakpoint form.
 */
function dfp_more_breakpoints_js($form, $form_state) {
  return $form['breakpoint_settings']['breakpoints'];
}

/**
 * Helper form builder for the breakpoints form.
 */
function _dfp_breakpoints_form(&$breakpoints_form, $existing_breakpoints = array()) {
  // Display settings.
  $breakpoints_form['breakpoints'] = array(
    '#type' => 'markup',
    '#tree' => FALSE,
    '#prefix' => '<div id="dfp-breakpoints-wrapper">',
    '#suffix' => '</div>',
    '#theme' => 'dfp_breakpoint_settings',
    '#element_validate' => array('dfp_breakpoints_form_validate'),
  );

  // Add existing breakpoints to the form unless they are empty.
  foreach ($existing_breakpoints as $key => $data) {
    _dfp_breakpoint_form($breakpoints_form, $key, $data);
  }
  // Add one blank set of breakpoint fields.
  _dfp_breakpoint_form($breakpoints_form, count($existing_breakpoints));

  $breakpoints_form['breakpoints']['dfp_more_breakpoints'] = array(
    '#type' => 'submit',
    '#value' => t('Add another breakpoint'),
    '#submit' => array('dfp_more_breakpoints_submit'),
    '#limit_validation_errors' => array(),
    '#ajax' => array(
      'callback' => 'dfp_more_breakpoints_js',
      'wrapper' => 'dfp-breakpoints-wrapper',
      'effect' => 'fade',
      'file' => 'plugins/export_ui/dfp_ctools_export_ui.inc',
    ),
  );
}

/**
 * Helper form builder for an individual breakpoint.
 */
function _dfp_breakpoint_form(&$form, $key, $data = array()) {
  $form['breakpoints'][$key] = array(
    '#prefix' => '<div class="breakpoint" id="breakpoint-' . $key . '">',
    '#suffix' => '</div>',
    '#element_validate' => array('dfp_breakpoint_form_validate'),
  );
  $form['breakpoints'][$key]['browser_size'] = array(
    '#type' => 'textfield',
    '#title_display' => 'invisible',
    '#title' => t('Minimum Browser Size'),
    '#size' => 10,
    '#default_value' => isset($data['browser_size']) ? $data['browser_size'] : '',
    '#parents' => array('breakpoints', $key, 'browser_size'),
    '#attributes' => array('class' => array('field-breakpoint-browser-size')),
  );
  $form['breakpoints'][$key]['ad_sizes'] = array(
    '#type' => 'textfield',
    '#title_display' => 'invisible',
    '#title' => t('Ad Sizes'),
    '#size' => 20,
    '#default_value' => isset($data['ad_sizes']) ? $data['ad_sizes'] : '',
    '#parents' => array('breakpoints', $key, 'ad_sizes'),
    '#attributes' => array('class' => array('field-breakpoint-ad-sizes')),
  );
  $form['breakpoints'][$key]['delete_breakpoint'] = array(
    '#type' => 'textfield',
    '#title_display' => 'invisible',
    '#title' => t('Ad Sizes'),
    '#size' => 20,
    '#default_value' => isset($data['ad_sizes']) ? $data['ad_sizes'] : '',
    '#parents' => array('breakpoints', $key, 'ad_sizes'),
    '#attributes' => array('class' => array('field-breakpoint-ad-sizes')),
  );
  if (empty($data)) {
    $form['breakpoints'][$key]['browser_size']['#description'] = t('Example: 1024x768');
    $form['breakpoints'][$key]['ad_sizes']['#description'] = t('Example: 300x600,300x250');
  }
}

/**
 * Returns the current breakpoints. The default value will be used unless an
 * "input" exists in the form_state variable, in which case that will be used.
 */
function _dfp_breakpoints_get_existing($form_state, $default = array()) {
  $breakpoints = array();
  if (isset($form_state['input']['breakpoints'])) {
    $breakpoints = $form_state['input']['breakpoints'];
  }
  elseif (!empty($default)) {
    $breakpoints = $default;
  }

  return $breakpoints;
}

/**
 * Theme function for the "breakpoint" form.
 */
function theme_dfp_breakpoint_settings($variables) {
  $form = $variables['form'];

  $more_button = drupal_render($form['dfp_more_breakpoints']);
  unset($form['dfp_more_breakpoints']);

  $headers = array(t('Browser Size'), t('Ad Size(s)'));
  $rows = array();
  foreach (element_children($form) as $key) {
    $rows[] = array(drupal_render($form[$key]['browser_size']), drupal_render($form[$key]['ad_sizes']));
  }
  $rows[] = array(array('data' => 'These breakpoints are set to implement DFP responsive mappings. See <a href="https://support.google.com/dfp_premium/answer/3423562?hl=en">this support article</a> for more information.', 'colspan' => 2));
  return theme('table', array('header' => $headers, 'rows' => $rows)) . $more_button;
}


/**
 * Helper function returns the a value from a givne multidimensional array given
 * a specific key. If the key exists in more than one place, the first occurance
 * is used.
 */
function _dfp_get_form_item($needle_key, $array) {
  $children = element_children($array);

  foreach ($children as $child) {
    if ($child == $needle_key) {
      return $array[$child];
    }
    elseif (is_array($array[$child])) {
      $result = _dfp_get_form_item($needle_key, $array[$child]);
      if ($result) {
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

/**
 * Theme function for the "size" context reaction form.
 */
function theme_dfp_size_settings($variables) {
  $form = $variables['form'];

  $headers = array(t('Ad Slot'), t('Size Override'));
  $rows = array();
  $caption = t('You can override the size(s) of the ad slot for this context by entering the new size(s) below. Leave the field blank if the size should not be overridden.');

  $children = element_children($form);
  $last = array_pop($children);

  foreach (element_children($form) as $key) {
    if ($key != $last) {
      unset($form[$key]['#description']);
    }
    $rows[] = array($form[$key]['#title'], drupal_render($form[$key]));
  }
  return theme('table', array('header' => $headers, 'rows' => $rows, 'caption' => $caption));
}
