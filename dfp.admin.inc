<?php

/**
 * @file
 * Admin forms and functinality for DFP ads.
 */

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
