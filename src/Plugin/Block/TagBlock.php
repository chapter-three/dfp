<?php

/**
 * @file
 * Contains \Drupal\dfp\Plugin\Block\TagBlock.
 */

namespace Drupal\dfp\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic DFP Ad block type.
 *
 * @Block(
 *  id = "dfp_ad",
 *  admin_label = @Translation("DFP Ad block"),
 *  category = @Translation("Advertising"),
 *  deriver = "Drupal\dfp\Plugin\Derivative\TagBlock"
 * )
 */
class TagBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The Drupal account to use for checking for access to block.
   *
   * @var \Drupal\Core\Session\AccountInterface.
   */
  protected $account;

  /**
   * Constructs a new BlockContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entity_repository, EntityTypeManager $entity_type_manager, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => 'hidden',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($tag = $this->getEntity()) {
      return $this->entityTypeManager->getViewBuilder($tag->getEntityTypeId())->view($tag);
    }
    else {
      return [
        '#markup' => $this->t('DFP tag with uuid %uuid does not exist. <a href=":url">Add DFP tag</a>.', [
          '%uuid' => $this->getDerivativeId(),
          ':url' => Url::fromRoute('entity.dfp_tag.add_form')->toString(),
        ]),
        '#access' => $this->account->hasPermission('administer DFP'),
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
