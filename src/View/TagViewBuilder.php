<?php

/**
 * @file
 * Contains \Drupal\dfp\View\TagViewBuilder.
 */

namespace Drupal\dfp\View;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\dfp\Entity\TagInterface;
use Drupal\dfp\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DFP Tag view builder.
 */
class TagViewBuilder extends EntityViewBuilder {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * DFP token service.
   *
   * @var \Drupal\dfp\TokenInterface
   */
  protected $token;

  /**
   * Constructs a new BlockViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\dfp\TokenInterface $token
   *   DFP token service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, TokenInterface $token) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('dfp.token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $build = $this->viewMultiple([$entity], $view_mode, $langcode);
    return reset($build);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\dfp\Entity\TagInterface[] $entities */
    $build = [];
    foreach ($entities as $tag) {
      // @todo Ensure a tag is only once on the page.
      // @todo Get cache-ability based on tokens used in TagView...
      $global_settings = $this->configFactory->get('dfp.settings');
      $tag_view = new TagView($tag, $global_settings, $this->token, $this->moduleHandler());

      $tag_id = $tag->id();
      $build[$tag_id] = [
        '#cache' => [
          'keys' => ['entity_view', 'dfp_tag', $tag_id],
        ],
      ];

      // Sort out the cache tags and contexts.
      $cacheable_metadata = CacheableMetadata::createFromObject($global_settings);
      $cacheable_metadata->merge(CacheableMetadata::createFromObject($tag));
      $cacheable_metadata->addCacheTags($this->getCacheTags());
      $cacheable_metadata->applyTo($build[$tag_id]);

      $build[$tag_id] += static::buildPreTag($tag_view);

    }

    return $build;
  }

  /**
   * Builds a #pre_render-able DFP tag render array.
   *
   * @param \Drupal\dfp\View\TagView $tag_view
   *   A DFP tag.
   *
   * @return array
   *   A render array with a #pre_render callback to render the DFP tag.
   *
   * @see \Drupal\dfp\DfpResponseAttachmentsProcessor::processAttachments()
   */
  protected static function buildPreTag(TagView $tag_view) {
    $build = [
      '#contextual_links' => [
        'dfp_tag' => [
          'route_parameters' => ['dfp_tag' => $tag_view->id()],
        ],
      ],
    ];
    if ($tag_view->isShortTag()) {
      $build['tag'] = [
        '#theme' => 'dfp_short_tag',
        '#url_jump' => 'http://' . TagInterface::GOOGLE_SHORT_TAG_SERVICES_URL . '/jump?' . $tag_view->getShortTagQueryString(),
        '#url_ad' => 'http://' . TagInterface::GOOGLE_SHORT_TAG_SERVICES_URL . '/ad?' . $tag_view->getShortTagQueryString(),
      ];
    }
    else {
      $build['tag'] = [
        '#theme' => 'dfp_tag',
      ];
      // Attach a tag. These attachments are processed by
      // \Drupal\dfp\DfpResponseAttachmentsProcessor::processAttachments().
      $build['#attached']['dfp_slot'][] = $tag_view;
    }
    $build['tag']['#tag'] = $tag_view;

    return $build;
  }

}
