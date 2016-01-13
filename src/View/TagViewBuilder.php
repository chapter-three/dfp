<?php

/**
 * @file
 * Contains \Drupal\dfp\View\Tag.
 */

namespace Drupal\dfp\View;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
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
    $build = $this->viewMultiple(array($entity), $view_mode, $langcode);
    return reset($build);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\dfp\Entity\TagInterface[] $entities */
    $build = [];
    foreach ($entities as $entity) {
      $entity_id = $entity->id();
      $cache_tags = Cache::mergeTags($this->getCacheTags(), $entity->getCacheTags());

      // Create the render array for the block as a whole.
      // @see template_preprocess_block().
      $build[$entity_id] = [
        '#cache' => [
          'keys' => ['entity_view', 'dfp_tag', $entity->id()],
          'contexts' => $entity->getCacheContexts(),
          'tags' => $cache_tags,
        ],
      ];

      $build[$entity_id] += static::buildPreRenderableBlock($entity, $this->moduleHandler(), $this->configFactory, $this->token);

      // Allow altering of cacheability metadata or setting #create_placeholder.
      // $this->moduleHandler->alter(['block_build', "block_build_" . $plugin->getBaseId()], $build[$entity_id], $plugin);
    }

    return $build;
  }

  /**
   * Builds a #pre_render-able DFP tag render array.
   *
   * @param \Drupal\dfp\Entity\TagInterface $tag
   *   A DFP tag config entity.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   *
   * @return array
   *   A render array with a #pre_render callback to render the DFP tag.
   */
  protected static function buildPreRenderableBlock(TagInterface $tag, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, TokenInterface $token) {
    $global_settings = $config_factory->get('dfp.settings');
    $tag_view = new TagView($tag, $global_settings, $token);

    $build = array(
      '#contextual_links' => [
        'dfp_tag' => [
          'route_parameters' => ['dfp_tag' => $tag->id()],
        ],
      ],
      'dfp_wrapper' => array(
        '#type' => 'container',
        // @todo should we be adding attributes here or in the template?
//        '#attributes' => array(
//          'id' => $tag->getWrapperId(),
//          'class' => array(
//            'dfp-tag-wrapper',
//          ),
//        ),
        'tag' => array(
          '#theme' => $tag->shortTag() ? 'dfp_short_tag' : 'dfp_tag',
          '#tag' => $tag_view,
        ),
      ),
    );
    // @todo I think this is actaully all handled by template_process_dfp_tag().
//    if (!empty($tag->slug()) && $global_settings->get('slug_placement') == 0) {
//      $render_array['dfp_wrapper']['slug_wrapper'] = array(
//        '#type' => 'container',
//        '#attributes' => array(
//          'class' => array(
//            'slug',
//          ),
//        ),
//        'slug' => array(
//          '#markup' => $tag_view->getSlug(),
//        ),
//        '#weight' => -1,
//      );
//    }

    // If an alter hook wants to modify the block contents, it can append
    // another #pre_render hook.
    // $module_handler->alter(['block_view', "block_view_$base_id"], $build, $plugin);

    return $build;
  }

}
