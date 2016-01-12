<?php

/**
 * @file
 * Contains \Drupal\block_content\Plugin\Block\BlockContentBlock.
 */

namespace Drupal\dfp\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic DFP Ad block type.
 *
 * @Block(
 *  id = "dfp_ad",
 *  admin_label = @Translation("DFP Ad block"),
 *  category = @Translation("Custom"),
 *  deriver = "Drupal\dfp\Plugin\Derivative\TagBlock"
 * )
 */
class TagBlock extends BlockBase implements ContainerFactoryPluginInterface {

//  /**
//   * The Plugin Block Manager.
//   *
//   * @var \Drupal\Core\Block\BlockManagerInterface.
//   */
//  protected $blockManager;
//
  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The Drupal account to use for checking for access to block.
   *
   * @var \Drupal\Core\Session\AccountInterface.
   */
  protected $account;
//
//  /**
//   * The block content entity.
//   *
//   * @var \Drupal\block_content\BlockContentInterface
//   */
//  protected $blockContent;
//
//  /**
//   * The URL generator.
//   *
//   * @var \Drupal\Core\Routing\UrlGeneratorInterface
//   */
//  protected $urlGenerator;
//
  /**
   * Constructs a new BlockContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('current_user')
    );
  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function defaultConfiguration() {
//    return array(
//      'status' => TRUE,
//      'info' => '',
//      'view_mode' => 'full',
//    );
//  }
//
//  /**
//   * Overrides \Drupal\Core\Block\BlockBase::blockForm().
//   *
//   * Adds body and description fields to the block configuration form.
//   */
//  public function blockForm($form, FormStateInterface $form_state) {
//    $uuid = $this->getDerivativeId();
//    $block = $this->entityManager->loadEntityByUuid('block_content', $uuid);
//    $options = $this->entityManager->getViewModeOptionsByBundle('block_content', $block->bundle());
//
//    $form['view_mode'] = array(
//      '#type' => 'select',
//      '#options' => $options,
//      '#title' => $this->t('View mode'),
//      '#description' => $this->t('Output the block in this view mode.'),
//      '#default_value' => $this->configuration['view_mode'],
//      '#access' => (count($options) > 1),
//    );
//    $form['title']['#description'] = $this->t('The title of the block as shown to the user.');
//    return $form;
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public function blockSubmit($form, FormStateInterface $form_state) {
//    // Invalidate the block cache to update custom block-based derivatives.
//    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
//    $this->blockManager->clearCachedDefinitions();
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  protected function blockAccess(AccountInterface $account) {
//    if ($this->getEntity()) {
//      return $this->getEntity()->access('view', $account, TRUE);
//    }
//    return AccessResult::forbidden();
//  }
//
  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($tag = $this->getEntity()) {
      return [
        '#markup' => 'Tag: ' . $tag->slot(),
      ];
      //return $this->entityManager->getViewBuilder($block->getEntityTypeId())->view($block, $this->configuration['view_mode']);
    }
    else {
      return [
        '#markup' => $this->t('Block with uuid %uuid does not exist. <a href=":url">Add custom block</a>.', [
          '%uuid' => $this->getDerivativeId(),
          ':url' => Url::fromRoute('entity.dfp_tag.add_form')->toString()
        ]),
        '#access' => $this->account->hasPermission('administer DFP')
      ];
    }
  }

  /**
   * Loads the block content entity of the block.
   *
   * @return \Drupal\dfp\Entity\TagInterface|null
   *   The block content entity.
   */
  protected function getEntity() {
    $uuid = $this->getDerivativeId();
    if (!isset($this->tag)) {
      $this->tag = $this->entityRepository->loadEntityByUuid('dfp_tag', $uuid);
    }
    return $this->tag;
  }

}
