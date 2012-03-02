<?php

/**
 * Expose DART site & zone settings as context reactions.
 */
class dfp_context_reaction_settings extends context_reaction {
  // /**
  //  * Allow admins to choose what DART tags to hide.
  //  */
  // function options_form($context) {
  //   // Get existing values for this form.
  //   $values = $this->fetch_from_context($context);

  //   $form['dart_site'] = array(
  //     '#type' => 'textfield',
  //     '#title' => t('Override DART site'),
  //     '#default_value' => isset($values['dart_site']) ? $values['dart_site'] : '',
  //     '#required' => FALSE,
  //     '#maxlength' => 32,
  //   );
  //   $form['dart_zone'] = array(
  //     '#type' => 'textfield',
  //     '#title' => t('Override DART zone'),
  //     '#default_value' => isset($values['dart_zone']) ? $values['dart_zone'] : '',
  //     '#required' => FALSE,
  //     '#maxlength' => 32,
  //   );

  //   return $form;
  // }

  // /**
  //  * Return a keyed array containing the overridden values of site and zone.
  //  */
  // function execute(&$tag) {
  //   foreach ($this->get_contexts() as $context_name => $context) {
  //     if (!empty($context->reactions['dart_settings'])) {
  //       $settings = $context->reactions['dart_settings'];
  //       $tag->site = !empty($settings['dart_site']) ? check_plain($settings['dart_site']) : $tag->site;
  //       $tag->zone = !empty($settings['dart_zone']) ? check_plain($settings['dart_zone']) : $tag->zone;
  //     }
  //   }
  // }
}
