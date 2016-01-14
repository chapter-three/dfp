<?php

/**
 * Created by PhpStorm.
 * User: alex
 * Date: 11/01/16
 * Time: 18:34
 */

namespace Drupal\dfp\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dfp\Entity\TagInterface;

class Tag extends EntityForm {
  use BreakpointFormTrait;
  use TargetingFormTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\dfp\Entity\TagInterface $tag */
    $tag = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add DFP tag');
    }
    else {
      $form['#title'] = $this->t('Edit %label DFP tag', array('%label' => $tag->label()));
    }

    // Tag settings.
    $form['tag_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Tag Settings'),
      '#open' => TRUE,
//      '#attached' => array(
//        'js' => array(
//          'vertical-tabs' => drupal_get_path('module', 'dfp') . '/dfp.admin.js',
//        ),
//      ),
    );

    $form['tag_settings']['slot'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ad Slot Name'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#default_value' => $tag->slot(),
      '#description' => $this->t('Example: leaderboard or box1'),
    );

    $form['tag_settings']['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => 128,
      '#default_value' => $tag->id(),
      '#description' => $this->t('A unique machine-readable name for this DFP tag. Only use letters, numbers and underscores. Example: top_banner'),
      '#machine_name' => array(
        'exists' => ['Drupal\dfp\Entity\Tag', 'load'],
        'source' => ['tag_settings', 'slot'],
      ),
    );

//    $form['tag_settings']['out_of_page'] = array(
//      '#type' => 'checkbox',
//      '#title' => $this->t('Out of page (interstitial) ad slot'),
//      '#description' => $this->t('Use Context module to place the Ad slot on the page.'),
//      '#default_value' => isset($tag->settings['out_of_page']) ? $tag->settings['out_of_page'] : 0,
//      '#parents' => array('settings', 'out_of_page'),
//      '#weight' => 0,
//    );
    $form['tag_settings']['size'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Size(s)'),
      '#description' => $this->t('Example: 300x600,300x250. For Out Of Page slots, use 0x0'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#default_value' => $tag->size(),
    );
    $form['tag_settings']['adunit'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ad Unit Pattern'),
      '#required' => FALSE,
      '#maxlength' => 255,
      '#default_value' => $tag->adunit(),
      '#description' => $this->t('Use the tokens below to define how the ad unit should display. The network id will be included automatically. Example: [dfp_tag:url_parts:4]/[dfp_tag:slot]. Leave this field empty to use the default ad unit adunit as defined in <a href=":url">Global DFP Settings</a>.', array(':url' => Url::fromRoute('dfp.admin_settings')->toString())),
    );
//    $form['tag_settings']['tokens'] = array(
//      '#theme' => 'token_tree',
//      '#token_types' => array('dfp_tag', 'node', 'term', 'user'),
//      '#global_types' => TRUE,
//      '#click_insert' => TRUE,
//      '#dialog' => TRUE,
//    );

    // Global Display settings.
    $form['tag_display_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Display Options'),
      '#open' => TRUE,
    );
    $form['tag_display_options']['slug'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Slug'),
      '#description' => $this->t('Override the default slug for this ad tag. Use @none for no slug. Leave this field empty to use the default slug. Example: Advertisement', array('@none' => '<none>')),
      '#required' => FALSE,
      '#maxlength' => 64,
      '#default_value' => $tag->slug(),
    );
    $form['tag_display_options']['block'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create a block for this ad tag'),
      '#description' => $this->t('Display this ad in a block configurable. <a href=":url">Place the block</a>.', array(':url' => Url::fromRoute('block.admin_display')->toString())),
      '#default_value' => $tag->hasBlock(),
    );
    $form['tag_display_options']['short_tag'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Render this tag without javascript'),
      '#description' => $this->t('Use this option for ads included in emails.'),
      '#default_value' => $tag->shortTag(),
    );

    // Responsive settings.
    $form['breakpoint_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Responsive Settings'),
      '#open' => TRUE,
    );
    $existing_breakpoints = $this->getExistingBreakpoints($form_state, $tag->breakpoints());
    $this->addBreakpointsForm($form['breakpoint_settings'], $existing_breakpoints);

    // Targeting options.
    $form['targeting_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Targeting'),
      '#open' => TRUE,
    );
    $existing_targeting = $this->getExistingTargeting($form_state, $tag->targeting());
    $this->addTargetingForm($form['targeting_settings'], $existing_targeting);

    // Backfill ad settings options.
    $form['adsense_backfill'] = array(
      '#type' => 'details',
      '#title' => $this->t('Backfill Ad Settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    );
    $form['adsense_backfill']['ad_types'] = array(
      '#type' => 'select',
      '#title' => $this->t('AdSense Ad Type'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#default_value' => $tag->adsenseAdTypes(),
      '#options' => array(
//        '' => t('- None -'),
        TagInterface::ADSENSE_TEXT_IMAGE => $this->t('Both image and text ads'),
        TagInterface::ADSENSE_IMAGE => $this->t('Only image ads'),
        TagInterface::ADSENSE_TEXT => $this->t('Only text ads'),
      ),
      '#description' => $this->t('Choose what type of ads this tag can display when AdSense ads are used for backfill.'),
    );
    $form['adsense_backfill']['channel_ids'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('AdSense Channel ID(s)'),
      '#default_value' => $tag->adsenseChannelIds(),
      '#required' => FALSE,
      '#description' => $this->t('Example: 271828183+314159265'),
        '#states' => array(
          '!visible' => array(
            array(':input[name="adsense_backfill[ad_types]"]' => array('value' => '')),
          ),
        )
    );
    $form['adsense_backfill']['color_settings'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('form-item')),
      //'#theme' => 'dfp_adsense_color_settings',
      '#states' => array(
        'visible' => array(
          array(':input[name="adsense_backfill[ad_types]"]' => array('value' => TagInterface::ADSENSE_TEXT)),
          array(':input[name="adsense_backfill[ad_types]"]' => array('value' => TagInterface::ADSENSE_TEXT_IMAGE)),
        ),
      ),
//      '#attached' => array(
//        'js' => array(
//          'vertical-tabs' => drupal_get_path('module', 'dfp') . '/dfp.admin.js',
//        ),
//      ),
    );
    $adsense_color_settings = array(
      'background' => $this->t('Background color'),
      'border' => $this->t('Border color'),
      'link' => $this->t('Link color'),
      'text' => $this->t('Text color'),
      'url' => $this->t('URL color'),
    );
    foreach ($adsense_color_settings as $setting => $title) {
      $form['adsense_backfill']['color'][$setting] = array(
        '#type' => 'textfield',
        '#title' => $title,
        '#attributes' => array('class' => array('color-setting')),
        '#field_prefix' => '#',
        '#title_display' => 'invisible',
        '#default_value' => $tag->adsenseColor($setting),
        '#size' => 6,
      );
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
    $values = $form_state->getValues();
    $status = $tag->save();
    $t_args['%slot'] = $tag->label();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The DFP tag %slot has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The DFP tag %slot has been added.', $t_args));
      $context = array_merge($t_args, array('link' => $tag->toLink($this->t('View'))->toString()));
      $this->logger('dfp')->notice('Added DFP tag %slot.', $context);
    }

    $form_state->setRedirectUrl($tag->urlInfo('collection'));
  }

}
