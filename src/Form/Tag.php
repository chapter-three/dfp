<?php

/**
 * @file
 * Contains \Drupal\dfp\Form\Tag.
 */

namespace Drupal\dfp\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dfp\Entity\TagInterface;

/**
 * Form to edit and add DFP tags.
 */
class Tag extends EntityForm {
  use BreakpointFormTrait;
  use TargetingFormTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // @todo Implement vertical tabs like D7 module.
    // @todo Implement out_of_page setting like D7 module.
    $form = parent::form($form, $form_state);

    /** @var \Drupal\dfp\Entity\TagInterface $tag */
    $tag = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add DFP tag');
    }
    else {
      $form['#title'] = $this->t('Edit %label DFP tag', ['%label' => $tag->label()]);
    }

    // Tag settings.
    $form['tag_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Tag Settings'),
      '#open' => TRUE,
    ];

    $form['tag_settings']['slot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ad Slot Name'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#default_value' => $tag->slot(),
      '#description' => $this->t('Example: leaderboard or box1'),
    ];

    $form['tag_settings']['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#default_value' => $tag->id(),
      '#description' => $this->t('A unique machine-readable name for this DFP tag. Only use letters, numbers and underscores. Example: top_banner'),
      '#machine_name' => [
        'exists' => ['Drupal\dfp\Entity\Tag', 'load'],
        'source' => ['tag_settings', 'slot'],
      ],
    ];

    $form['tag_settings']['size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Size(s)'),
      '#description' => $this->t('Example: 300x600,300x250. For Out Of Page slots, use 0x0'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#default_value' => $tag->size(),
    ];
    $form['tag_settings']['adunit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ad Unit Pattern'),
      '#required' => FALSE,
      '#maxlength' => 255,
      '#default_value' => $tag->adunit(),
      '#description' => $this->t('Use the tokens below to define how the ad unit should display. The network id will be included automatically. Example: [dfp_tag:url_parts:4]/[dfp_tag:slot]. Leave this field empty to use the default ad unit adunit as defined in <a href=":url">Global DFP Settings</a>.', [':url' => Url::fromRoute('dfp.admin_settings')->toString()]),
    ];
    // @todo Add token browser.

    // Global Display settings.
    $form['tag_display_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Options'),
      '#open' => TRUE,
    ];
    $form['tag_display_options']['slug'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slug'),
      '#description' => $this->t('Override the default slug for this ad tag. Use @none for no slug. Leave this field empty to use the default slug. Example: Advertisement', ['@none' => '<none>']),
      '#required' => FALSE,
      '#maxlength' => 64,
      '#default_value' => $tag->slug(),
    ];
    $form['tag_display_options']['block'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create a block for this ad tag'),
      '#description' => $this->t('Display this ad in a block configurable. <a href=":url">Place the block</a>.', [':url' => Url::fromRoute('block.admin_display')->toString()]),
      '#default_value' => $tag->hasBlock(),
    ];
    $form['tag_display_options']['short_tag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Render this tag without javascript'),
      '#description' => $this->t('Use this option for ads included in emails.'),
      '#default_value' => $tag->shortTag(),
    ];

    // Responsive settings.
    $form['breakpoint_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Responsive Settings'),
      '#open' => TRUE,
    ];
    $existing_breakpoints = $this->getExistingBreakpoints($form_state, $tag->breakpoints());
    $this->addBreakpointsForm($form['breakpoint_settings'], $existing_breakpoints);

    // Targeting options.
    $form['targeting_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Targeting'),
      '#open' => TRUE,
    ];
    $existing_targeting = $this->getExistingTargeting($form_state, $tag->targeting());
    $this->addTargetingForm($form['targeting_settings'], $existing_targeting);

    // Backfill ad settings options.
    $form['adsense_backfill'] = [
      '#type' => 'details',
      '#title' => $this->t('Backfill Ad Settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['adsense_backfill']['ad_types'] = [
      '#type' => 'select',
      '#title' => $this->t('AdSense Ad Type'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#default_value' => $tag->adsenseAdTypes(),
      '#options' => [
        TagInterface::ADSENSE_TEXT_IMAGE => $this->t('Both image and text ads'),
        TagInterface::ADSENSE_IMAGE => $this->t('Only image ads'),
        TagInterface::ADSENSE_TEXT => $this->t('Only text ads'),
      ],
      '#description' => $this->t('Choose what type of ads this tag can display when AdSense ads are used for backfill.'),
    ];
    $form['adsense_backfill']['channel_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AdSense Channel ID(s)'),
      '#default_value' => $tag->adsenseChannelIds(),
      '#required' => FALSE,
      '#description' => $this->t('Example: 271828183+314159265'),
      '#states' => [
        '!visible' => [
          [':input[name="adsense_backfill[ad_types]"]' => ['value' => '']],
        ],
      ],
    ];
    $form['adsense_backfill']['color'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Color Settings for Text Ads'),
      '#attributes' => ['class' => ['form-item']],
      '#states' => [
        'visible' => [
          [':input[name="adsense_backfill[ad_types]"]' => ['value' => TagInterface::ADSENSE_TEXT]],
          [':input[name="adsense_backfill[ad_types]"]' => ['value' => TagInterface::ADSENSE_TEXT_IMAGE]],
        ],
      ],
    ];
    $adsense_color_settings = [
      'background' => $this->t('Background color'),
      'border' => $this->t('Border color'),
      'link' => $this->t('Link color'),
      'text' => $this->t('Text color'),
      'url' => $this->t('URL color'),
    ];
    foreach ($adsense_color_settings as $setting => $title) {
      // @todo integrate color picker if color module enabled.
      $form['adsense_backfill']['color'][$setting] = [
        '#type' => 'textfield',
        '#title' => $title,
        '#attributes' => ['class' => ['color-setting']],
        '#field_prefix' => '#',
        '#default_value' => $tag->adsenseColor($setting),
        '#size' => 6,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (preg_match(TagInterface::ADUNIT_PATTERN_VALIDATION_REGEX, $form_state->getValue('adunit'))) {
      $form_state->setErrorByName('adunit', $this->t('Ad Unit Patterns can only include letters, numbers, hyphens, dashes, periods, slashes and tokens.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $tag = $this->entity;
    $status = $tag->save();
    $t_args['%slot'] = $tag->label();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The DFP tag %slot has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The DFP tag %slot has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $tag->toLink($this->t('Edit DFP tag'), 'edit-form')->toString()]);
      $this->logger('dfp')->notice('Added DFP tag %slot.', $context);
    }

    $form_state->setRedirectUrl($tag->toUrl('collection'));
  }

}
