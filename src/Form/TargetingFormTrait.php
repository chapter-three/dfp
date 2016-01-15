<?php

/**
 * @file
 * Contains \Drupal\dfp\Form\TargetingFormTrait.
 */

namespace Drupal\dfp\Form;

use Drupal\Core\Form\FormStateInterface;

trait TargetingFormTrait {

  /**
   * Returns the current targets.
   *
   * The default value will be used unless an "input" exists in the form_state
   * variable, in which case that will be used.
   */
  protected function getExistingTargeting(FormStateInterface $form_state, array $targeting = array()) {
    $user_input = $form_state->getUserInput();
    if (isset($user_input['targeting'])) {
      $targeting = $user_input['targeting'];
    }
    return $targeting;
  }

  /**
   * Helper form builder for the targeting form.
   */
  protected function addTargetingForm(array &$targeting_form, array $existing_targeting = array()) {
    // Display settings.
    $targeting_form['targeting'] = array(
      '#type' => 'markup',
      '#tree' => FALSE,
      '#prefix' => '<div id="dfp-targeting-wrapper">',
      '#suffix' => '</div>',
      //'#theme' => 'dfp_target_settings',
      '#element_validate' => [[get_class($this), 'targetingFormValidate']],
    );

    // Add existing targets to the form unless they are empty.
    foreach ($existing_targeting as $key => $data) {
      $this->addTargetForm($targeting_form, $key, $data);
    }
    // Add one blank set of target fields.
    $this->addTargetForm($targeting_form, count($existing_targeting));

    $targeting_form['targeting']['dfp_more_targets'] = array(
      '#type' => 'submit',
      '#value' => t('Add another target'),
      '#submit' => [get_class($this), 'targetingFormMoreTargetsSubmit'],
      '#limit_validation_errors' => array(),
      '#ajax' => array(
        'callback' => [get_class($this), 'moreTargetsJs'],
        'wrapper' => 'dfp-targeting-wrapper',
        'effect' => 'fade',
      ),
    );
    // @todo
//  $targeting_form['tokens'] = array(
//    '#theme' => 'token_tree',
//    '#token_types' => array('dfp_tag', 'node', 'term', 'user'),
//    '#global_types' => TRUE,
//    '#click_insert' => TRUE,
//    '#dialog' => TRUE,
//  );
  }

  /**
   * Validation function used by the targeting form.
   */
  public static function targetingFormValidate(array &$element, FormStateInterface &$form_state) {
    if ($form_state->getTriggeringElement()['#name'] != 'dfp_more_targets') {
      self::trimTargetingValues($form_state->getValues());
    }
  }

  /**
   * Submit handler to add more targets to an ad tag.
   */
  public function targetingFormMoreTargetsSubmit(array $form, FormStateInterface &$form_state) {
    $form_state->set('targeting', $form_state->getUserInput()['targeting']);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for adding targets to the targeting form.
   */
  public function moreTargetsJs(array $form, FormStateInterface $form_state) {
    return $form['targeting_settings']['targeting'];
  }

  /**
   * Helper form builder for an individual target.
   */
  protected function addTargetForm(array &$form, $key, array $data = array()) {
    $form['targeting'][$key] = array(
      '#prefix' => '<div class="target" id="target-' . $key . '">',
      '#suffix' => '</div>',
      '#element_validate' => [[get_class($this), 'targetFormValidate']],
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
   * Validation function used by an individual target in the targeting form.
   */
  public static function targetFormValidate(array $element, FormStateInterface &$form_state) {
    if (empty($element['target']['#value']) && !empty($element['value']['#value'])) {
      $form_state->setError($element['target'], t('The target cannot be empty if a value exists.'));
    }
    elseif (!empty($element['target']['#value']) && empty($element['value']['#value'])) {
      $form_state->setError($element['value'], t('The value cannot be empty if a target exists.'));
    }
  }

  /**
   * Helper function that takes a form_state['values'] and removes empty targets.
   */
  protected static function trimTargetingValues(&$values, $parent = 'targeting') {
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
        self::trimTargetingValues($val, $parent);
      }
    }
  }
}
