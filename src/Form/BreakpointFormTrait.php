<?php

/**
 * @file
 * Contains \Drupal\dfp\Form\BreakpointFormTrait.
 */

namespace Drupal\dfp\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form for adding breakpoints to a DFP tag.
 */
trait BreakpointFormTrait {

  /**
   * Validation function used by an individual breakpoint.
   */
  public static function breakpointFormValidate(array $element, FormStateInterface &$form_state) {
    if (empty($element['browser_size']['#value']) && !empty($element['ad_sizes']['#value'])) {
      $form_state->setError($element['browser_size'], t('The browser size cannot be empty if ad size(s) exists.'));
    }
    elseif (!empty($element['browser_size']['#value']) && empty($element['ad_sizes']['#value'])) {
      $form_state->setError($element['ad_sizes'], t('The ad size(s) cannot be empty if a browser size exists.'));
    }
    if (!empty($element['browser_size']['#value']) && !empty($element['ad_sizes']['#value'])) {
      if (preg_match('/[^x|0-9]/', $element['browser_size']['#value'])) {
        $form_state->setError($element['browser_size'], t('The browser size can only contain numbers and the character x.'));
      }
      elseif (preg_match('/[^x|,|0-9]/', $element['ad_sizes']['#value'])) {
        $form_state->setError($element['ad_sizes'], t('The ad size(s) can only contain numbers, the character x and commas.'));
      }
    }
  }

  /**
   * Validation function used by the breakpoints form.
   */
  public static function breakpointsFormValidate(array &$element, FormStateInterface &$form_state) {
    if ($form_state->getTriggeringElement()['#name'] != 'dfp_more_breakpoints') {
      self::breakpointsTrim($form_state->getValues());
    }
  }

  /**
   * Helper function that removes empty breakpoints from form values.
   */
  protected static function breakpointsTrim(array &$values, $parent = 'breakpoints') {
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
        self::breakpointsTrim($val, $parent);
      }
    }
  }

  /**
   * Submit handler to add more breakpoints to an ad tag.
   */
  public function moreBreakpointsSubmit(array $form, FormStateInterface &$form_state) {
    $form_state->setValue('breakpoints', $form_state->getUserInput()['breakpoints']);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for adding breakpoints to the breakpoint form.
   */
  public function moreBreakpointsJs(array $form, FormStateInterface $form_state) {
    return $form['breakpoint_settings']['breakpoints'];
  }

  /**
   * Helper form builder for the breakpoints form.
   */
  protected function addBreakpointsForm(array &$breakpoints_form, array $existing_breakpoints = []) {
    // Display settings.
    $breakpoints_form['breakpoints'] = [
      '#type' => 'markup',
      '#tree' => FALSE,
      '#prefix' => '<div id="dfp-breakpoints-wrapper">',
      '#suffix' => '</div>',
      '#element_validate' => [[get_class($this), 'breakpointsFormValidate']],
    ];

    $breakpoints_form['breakpoints']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Browser Size'),
        $this->t('Ad Size(s)'),
      ],
    ];

    $breakpoints_form['breakpoints']['help'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#markup' => $this->t('These breakpoints are set to implement DFP responsive mappings. See <a href="https://support.google.com/dfp_premium/answer/3423562?hl=en">this support article</a> for more information.'),
      '#suffix' => '</p>',
    ];

    // Add existing breakpoints to the form unless they are empty.
    foreach ($existing_breakpoints as $key => $data) {
      $this->addBreakpointForm($breakpoints_form, $key, $data);
    }
    // Add one blank set of breakpoint fields.
    $this->addBreakpointForm($breakpoints_form, count($existing_breakpoints));

    $breakpoints_form['breakpoints']['dfp_more_breakpoints'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another breakpoint'),
      '#submit' => [[get_class($this), 'moreBreakpointsSubmit']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [get_class($this), 'moreBreakpointsJs'],
        'wrapper' => 'dfp-breakpoints-wrapper',
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Helper form builder for an individual breakpoint.
   */
  protected function addBreakpointForm(array &$form, $key, array $data = []) {
    $form['breakpoints']['table'][$key] = [
      '#prefix' => '<div class="breakpoint" id="breakpoint-' . $key . '">',
      '#suffix' => '</div>',
      '#element_validate' => [[get_class($this), 'breakpointFormValidate']],
    ];
    $form['breakpoints']['table'][$key]['browser_size'] = [
      '#type' => 'textfield',
      '#title_display' => 'invisible',
      '#title' => $this->t('Minimum Browser Size'),
      '#size' => 10,
      '#default_value' => isset($data['browser_size']) ? $data['browser_size'] : '',
      '#parents' => ['breakpoints', $key, 'browser_size'],
      '#attributes' => ['class' => ['field-breakpoint-browser-size']],
    ];
    $form['breakpoints']['table'][$key]['ad_sizes'] = [
      '#type' => 'textfield',
      '#title_display' => 'invisible',
      '#title' => $this->t('Ad Sizes'),
      '#size' => 20,
      '#default_value' => isset($data['ad_sizes']) ? $data['ad_sizes'] : '',
      '#parents' => ['breakpoints', $key, 'ad_sizes'],
      '#attributes' => ['class' => ['field-breakpoint-ad-sizes']],
    ];
    if (empty($data)) {
      $form['breakpoints']['table'][$key]['browser_size']['#description'] = $this->t('Example: 1024x768');
      $form['breakpoints']['table'][$key]['ad_sizes']['#description'] = $this->t('Example: 300x600,300x250');
    }
  }

  /**
   * Returns the current breakpoints.
   *
   * @return array
   *   The default value will be used unless an "input" exists in the form_state
   *   variable, in which case that will be used.
   */
  protected function getExistingBreakpoints(FormStateInterface $form_state, array $breakpoints = []) {
    $user_input = $form_state->getUserInput();
    if (isset($user_input['breakpoints'])) {
      $breakpoints = $user_input['breakpoints'];
    }
    return $breakpoints;
  }

}
