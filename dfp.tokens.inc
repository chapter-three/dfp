<?php

/**
 * Implements hook_token_info().
 */
function dfp_token_info() {
  $type = array(
    'name' => t('DFP Ad Tags'),
    'description' => t('Tokens related to a given DFP ad tag.'),
    'needs-data' => 'tag',
  );

  $tag['slot'] = array(
    'name' => t('Slot Name'),
    'description' => t("The name of the ad slot defined by this tag."),
  );
  $tag['network_id'] = array(
    'name' => t("Network ID"),
    'description' => t("The unique ID provided by Google."),
  );
  $tag['ad_categories'] = array(
    'name' => t("DFP Ad Categories"),
    'description' => t("The DFP Ad Categories or uncategorized taxonomy terms attached to the entities currently being displayed to the user."),
  );

  return array(
    'types' => array('dfp_tag' => $type),
    'tokens' => array('dfp_tag' => $tag),
  );
}

/**
 * Implements hook_tokens().
 */
function dfp_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();

  if ($type == 'dfp_tag') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
      case 'slot':
        // @todo
        if (!empty($data['tag'])) {
          $replacements[$original] = $data['tag']->slot;
        }
        break;

      case 'network_id':
        $replacements[$original] = \Drupal::config('dfp.settings')->get('network_id');
        break;

      case 'ad_categories':
        // @todo what to do here
        $term_names = &drupal_static('dfp_entity_targeting_terms', array());
        $replacements[$original] = implode(',', $term_names);
        break;
      }
    }
  }

  return $replacements;
}
