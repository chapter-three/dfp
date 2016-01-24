<?php

/**
 * @file
 * Hooks provided by the DFP module.
 *
 * This file is divided into static hooks (hooks with string literal names) and
 * dynamic hooks (hooks with pattern-derived string names).
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter a targeting key|value pair from a DFP tag.
 *
 * @param array $targeting
 *   The DFP tag's targeting key|value pair.
 */
function hook_dfp_target_alter(&$targeting) {
  // @todo Add example.
}

/**
 * Alter the global targeting key|value pairs.
 *
 * @param array $targeting
 *   The global targeting key|value pairs.
 */
function hook_dfp_global_targeting_alter(&$targeting) {
  // @todo Fix example to be Drupal 8 relevant.
  // The following example adds the URL arguments array to the targeting.
  $arg = arg();
  if (!empty($arg)) {
    $targeting[] = [
      'target' => 'arg',
      'value' => $arg,
    ];
  }
}

/**
 * Alters the key values array used during the construction of a short tag.
 *
 * @param array $key_values
 *   The key values array used during the construction of a short tag.
 */
function hook_dfp_short_tag_keyvals_alter(&$key_values) {
  // @todo Add example.
}

/**
 * @} End of "addtogroup hooks".
 */
