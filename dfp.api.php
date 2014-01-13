<?php

/**
 * @file
 * Hooks provided by the Chaos Tool Suite.
 *
 * This file is divided into static hooks (hooks with string literal names) and
 * dynamic hooks (hooks with pattern-derived string names).
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the raw tag object just after it is loaded from the database.
 *
 * @param object $tag
 */
function hook_dfp_tag_load_alter(&$tag) {

}

/**
 * Alter the tag object just after it is loaded and the settings have been
 * loaded properly.
 *
 * @param object $tag
 */
function hook_dfp_tag_alter(&$tag) {

}

/**
 * Alter a targeting key|value pair.
 *
 * @param array $target
 */
function hook_dfp_target_alter(&$target) {

}

/**
 * Alter the global targeting key|value pairs.
 *
 * @param array $target
 */
function hook_dfp_global_targeting_alter(&$target) {

}

/**
 * Alter the keyvals array that is about to be used during the construction of
 * a short tag.
 *
 * @param array $keyvals
 */
function hook_dfp_short_tag_keyvals_alter(&$keyvals) {

}

/**
 * @} End of "addtogroup hooks".
 */
