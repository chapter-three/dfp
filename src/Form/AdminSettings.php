<?php

/**
 * @file
 * Contains \Drupal\dfp\Form\AdminSettings.
 */

namespace Drupal\dfp\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\dfp\Entity\TagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures DFP global settings.
 */
class AdminSettings extends ConfigFormBase {
  use TargetingFormTrait;

  /**
   * Entity bundle information.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a \Drupal\dfp\Form\AdminSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $bundle_info) {
    parent::__construct($config_factory);
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dfp_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dfp.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('dfp.settings');

    // @todo vertical tabs?

    // Default tag settings.
    $form['global_tag_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Tag Settings'),
      '#open' => TRUE,
    ];
    $form['global_tag_settings']['network_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Network ID'),
      '#default_value' => $config->get('network_id'),
      '#required' => TRUE,
      '#description' => t('The Network ID to use on all tags. According to Google this value should begin with a /.'),
    ];
    $form['global_tag_settings']['adunit_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Ad Unit Pattern'),
      '#required' => FALSE,
      '#maxlength' => 255,
      '#default_value' => $config->get('adunit_pattern'),
      '#description' => $this->t('Use the tokens below to define how the default ad unit should display. This can be overridden on each tag. The network id will be included automatically. Example: [current-page:url:args:value:0]/[current-page:url:args:value:1]/[dfp_tag:slot]'),
    ];
    $form['global_tag_settings']['click_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DFP Click URL (Sync mode only)'),
      '#default_value' => $config->get('click_url'),
      '#description' => $this->t('Sets a click URL on each DFP tag. Useful for intercepting ad click calls for reporting. Values beginning with http:// are treated as absolute; otherwise relative to current site domain.'),
      '#states' => [
        'enabled' => [
          'input[name="async_rendering"]' => ['checked' => FALSE],
        ],
      ],
    ];
    // @todo: Add back token browser.

    $form['global_tag_settings']['async_rendering'] = [
      '#type' => 'checkbox',
      '#title' => t('Render ads asynchronously'),
      '#default_value' => $config->get('async_rendering'),
      '#description' => $this->t('This can speed up page rendering time by loading page content without waiting for ads to load.'),
    ];
    $form['global_tag_settings']['disable_init_load'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable initial ad load'),
      '#default_value' => $config->get('disable_init_load'),
      '#description' => $this->t('(Async mode only) Disables the initial fetch of ads from Google when the page is first loaded. Calls to refresh() can still be used to fetch ads.'),
      '#states' => [
        'enabled' => [
          'input[name="async_rendering"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['global_tag_settings']['single_request'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine all ad requests into a single request'),
      '#default_value' => $config->get('single_request'),
      '#description' => $this->t('This can speed up page rendering time by limiting the number of external requests.'),
    ];

    $ad_categories_bundles = $config->get('ad_categories_bundles');
    $form['global_tag_settings']['enable_ad_categories'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable DFP Ad Categories'),
      '#default_value' => !empty($ad_categories_bundles),
      '#description' => $this->t('Example: if you have an "animals" vocabulary and you want to target the same ads to "dogs", "cats" and "hamsters" you can edit each of those terms and set the DFP Ad Category to "pets". Whenever the taxonomy terms are included as targeting values, anything tagged "cats" will target "pets" instead.'),
    ];

    $bundles = $this->bundleInfo->getBundleInfo('taxonomy_term');
    $options = [];
    foreach ($bundles as $key => $bundle) {
      if ($key != 'dfp_ad_categories') {
        $options[$key] = (string) $bundle['label'];
      }
    }
    // @todo react to bundle
    $form['global_tag_settings']['ad_categories_bundles'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $ad_categories_bundles,
      '#title' => $this->t('Select the vocabularies on which DFP Ad Categories should be enabled.'),
      '#states' => [
        'visible' => [
          'input[name="enable_ad_categories"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Global display options.
    $form['global_display_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Display Options'),
      '#open' => TRUE,
    ];
    $form['global_display_options']['default_slug'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Global Slug'),
      '#default_value' => $config->get('default_slug'),
      '#required' => FALSE,
      '#description' => $this->t('Slug all ad tags with this label. Example: Advertisement'),
    ];
    $form['global_display_options']['collapse_empty_divs'] = [
      '#type' => 'radios',
      '#title' => $this->t('Collapse empty divs'),
      '#default_value' => $config->get('collapse_empty_divs'),
      '#options' => [
        0 => $this->t('Never'),
        1 => $this->t('Collapse only if no ad is served'),
        2 => $this->t('Expand only if an ad is served'),
      ],
      '#description' => $this->t('<dl><dt>Never</dt><dd>Never collapse ad slots.</dd><dt>Collapse only</dt><dd>Collapse before any ad is loaded. Useful if ad slots will get filled most of the time.</dd><dt>Expand only</dt><dd>Collapse all divs on the page before any ads are fetched and expand if an ad is loaded into the ad slot. Useful if ad slots will stay empty most of the time.</dd></dl>'),
    ];
    $form['global_display_options']['hide_slug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide slug if no ad is served (recommended)'),
      '#default_value' => $config->get('hide_slug'),
      '#states' => [
        'visible' => [
          'input[name="collapse_empty_divs"]' => ['!value' => 0],
        ],
      ],
    ];

    // Global targeting options.
    $form['targeting_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Targeting'),
      '#open' => TRUE,
    ];
    $existing_targeting = $this->getExistingTargeting($form_state, $config->get('targeting'));
    $this->addTargetingForm($form['targeting_settings'], $existing_targeting);

    // AdTest Settings.
    $form['adtest'] = [
      '#type' => 'details',
      '#title' => $this->t('Ad Test Settings'),
      '#open' => TRUE,
    ];
    $form['adtest']['adtest_adunit_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ad Unit Pattern for Ad Tests'),
      '#description' => $this->t('Override the Ad Unit value for all the ad tags on a page by adding ?adtest=true to the URL. Use the tokens below to define how the ad unit should display. Example: [dfp_tag:network_id]/test/[dfp_tag:slot]'),
      '#default_value' => $config->get('adtest_adunit_pattern'),
    ];
    // @todo Add back token browser.
    // @todo Drupal 7 also had the ability to inject Javascript. Discuss whether
    //   or not this should be implemented.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('click_url') && $form_state->getValue('async_rendering')) {
      $form_state->setErrorByName('click_url', $this->t('Setting a click URL does not work with async rendering.'));
    }
    if (preg_match(TagInterface::ADUNIT_PATTERN_VALIDATION_REGEX, $form_state->getValue('adunit_pattern'))) {
      $form_state->setErrorByName('adunit_pattern', $this->t('Ad Unit Patterns can only include letters, numbers, hyphens, dashes, periods, slashes and tokens.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!$values['enable_ad_categories']) {
      $values['ad_categories_bundles'] = [];
    }
    if (!$values['async_rendering']) {
      $values['disable_init_load'] = FALSE;
    }

    // @todo Add fields to selected vocabularies.
    // Attach (or delete) an instance of the dfp_ad_categories term_reference
    // field for each vocabulary that should (or should not) have DFP Ad
    // Categories enabled.
    //    foreach ($form_state['values']['dfp_enable_ad_categories_bundles'] as $bundle => $enable) {
    //      $existing_instance = field_read_instance('taxonomy_term', 'field_dfp_ad_categories', $bundle);
    //      $enable = $enable && !$existing_instance && $form_state['values']['dfp_enable_ad_categories'];
    //      if ($enable) {
    //        $instance = array(
    //          'field_name' => 'field_dfp_ad_categories',
    //          'entity_type' => 'taxonomy_term',
    //          'label' => t('DFP Ad Category'),
    //          'bundle' => $bundle,
    //          'required' => FALSE,
    //          'widget' => array(
    //            'type' => 'options_select'
    //          ),
    //        );
    //        field_create_instance($instance);
    //      }
    //      elseif (!$enable && $existing_instance) {
    //        // Delete this field instance, but be certain not to delete the field.
    //        field_delete_instance($existing_instance, FALSE);
    //      }
    //    }

    $this->config('dfp.settings')
      ->set('network_id', $values['network_id'])
      ->set('adunit_pattern', $values['adunit_pattern'])
      ->set('click_url', $values['click_url'])
      ->set('async_rendering', $values['async_rendering'])
      ->set('disable_init_load', $values['disable_init_load'])
      ->set('single_request', $values['single_request'])
      ->set('ad_categories_bundles', $values['ad_categories_bundles'])
      ->set('default_slug', $values['default_slug'])
      ->set('collapse_empty_divs', $values['collapse_empty_divs'])
      ->set('adtest_adunit_pattern', $values['adtest_adunit_pattern'])
      ->set('targeting', $values['targeting'])
      ->set('hide_slug', $values['hide_slug'])
      ->save();
  }

}
