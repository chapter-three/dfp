<?php

/**
 * @file
 * Creates a test page for DFP ads.
 */

/**
 * Page callback for the DFP test page. It displays all tags on the site.
 */
function dfp_adtest_page() {
  $output = [];
  $form = drupal_get_form('dfp_adtest_form');

  $output[] = [
    '#markup' => render($form),
    '#attached' => [
      'css' => [
        'dfp-admin' => drupal_get_path('module', 'dfp') . '/dfp.admin.css',
      ],
    ],
  ];

  $tags = dfp_tag_load_all();
  foreach ($tags as $tag) {
    $tag_slot = [
      'tag slot name' => [
        '#markup' => '<h3>' . check_plain($tag->slot) . '</h3>',
      ],
    ];
    $tag_tag = dfp_tag($tag->machinename);
    $tag_devel = [];
    if (module_exists('devel')) {
      $tag_devel['object'] = [
        '#markup' => kpr($tag, TRUE),
      ];
    }
    $output[] = [$tag_slot, $tag_tag, $tag_devel];
  }

  return $output;
}

/**
 * Build a form for display at the top of the adtest page allowing users to set
 * the adunit pattern for all dfp tags on that page.
 */
function dfp_adtest_form($form, &$form_state) {
  form_load_include($form_state, 'inc', 'dfp', 'dfp.adtest');

  $form['#method'] = 'get';
  $form['adunit_override'] = [
    '#type' => 'textfield',
    '#title' => t('Ad Unit Override'),
    '#default_value' => isset($_GET['adunit_override']) ? $_GET['adunit_override'] : '',
    '#description' => t('Override the Ad Unit value for all the ad tags below. Use the tokens below to define how the ad unit should display. Example: [dfp_tag:network_id]/test/[dfp_tag:slot]'),
  ];
  $form['tokens'] = [
    '#theme' => 'token_tree',
    '#token_types' => ['dfp_tag', 'node', 'term', 'user'],
    '#global_types' => TRUE,
    '#click_insert' => TRUE,
    '#dialog' => TRUE,
    '#weight' => 100,
  ];
  $form['submit'] = [
    '#type' => 'submit',
    '#value' => t('Override'),
  ];

  return $form;
}

/**
 * Alter tags for various adtest scenaroios.
 */
function dfp_adtest_alter_tag(&$tag) {
  // Check for a submit on adtest form.
  if (isset($_GET['adunit_override'])) {
    $tag->adunit = $_GET['adunit_override'];
  }

  // Check for an "adtest" query string variable.
  if (isset($_GET['adtest'])) {
    $tag->adunit = variable_get('dfp_adtest_adunit', '');
  }
}
