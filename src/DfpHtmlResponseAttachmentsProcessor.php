<?php

/**
 * @file
 * Contains \Drupal\dfp\DfpResponseAttachmentsProcessor.
 */

namespace Drupal\dfp;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;
use Drupal\Core\Render\RendererInterface;
use Drupal\dfp\Entity\TagInterface;
use Drupal\dfp\View\TagView;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Processes attachments of HTML responses with Dfp slot attachments.
 *
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 * @see \Drupal\dfp\View\TagViewBuilder
 */
class DfpHtmlResponseAttachmentsProcessor extends HtmlResponseAttachmentsProcessor {

  /**
   * The HTML response attachments processor service.
   *
   * @var \Drupal\Core\Render\AttachmentsResponseProcessorInterface
   */
  protected $htmlResponseAttachmentsProcessor;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The DFP token service.
   *
   * @var \Drupal\dfp\TokenInterface
   */
  protected $token;

  /**
   * Constructs a DfpResponseAttachmentsProcessor object.
   *
   * @param \Drupal\Core\Render\AttachmentsResponseProcessorInterface $html_response_attachments_processor
   *   The HTML response attachments processor service.
   * @param \Drupal\Core\Asset\AssetResolverInterface $asset_resolver
   *   An asset resolver.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $css_collection_renderer
   *   The CSS asset collection renderer.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $js_collection_renderer
   *   The JS asset collection renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(AttachmentsResponseProcessorInterface $html_response_attachments_processor, TokenInterface $token, AssetResolverInterface $asset_resolver, ConfigFactoryInterface $config_factory, AssetCollectionRendererInterface $css_collection_renderer, AssetCollectionRendererInterface $js_collection_renderer, RequestStack $request_stack, RendererInterface $renderer, ModuleHandlerInterface $module_handler) {
    $this->htmlResponseAttachmentsProcessor = $html_response_attachments_processor;
    $this->configFactory = $config_factory;
    $this->token = $token;
    parent::__construct($asset_resolver, $config_factory, $css_collection_renderer, $js_collection_renderer, $request_stack, $renderer, $module_handler);
  }

  /**
   * {@inheritdoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    // @todo Convert to assertion once https://www.drupal.org/node/2408013 lands
    if (!$response instanceof HtmlResponse) {
      throw new \InvalidArgumentException('\Drupal\Core\Render\HtmlResponse instance expected.');
    }

    // First, render the actual placeholders. This may add attachments so this
    // is a bit of unfortunate but necessary duplication.
    // This is copied verbatim from
    // \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::processAttachments.
    try {
      $response = $this->renderPlaceholders($response);
    }
    catch (EnforcedResponseException $e) {
      return $e->getResponse();
    }

    // Extract DFP slots; HtmlResponseAttachmentsProcessor does not
    // know (nor need to know) how to process those.
    $attachments = $response->getAttachments();

    if (isset($attachments['dfp_slot'])) {
      $attachments['html_head'][] = [
        $this->getHeadTop(),
        'dfp-js-head-top',
      ];

      /** @var \Drupal\dfp\View\TagView $tag */
      foreach ($attachments['dfp_slot'] as $tag_view) {
        $attachments['html_head'][] = [
          [
            // Use a fake #type to prevent
            // HtmlResponseAttachmentsProcessor::processHead() adding one.
            '#type' => 'dfp_script',
            '#theme' => 'dfp_slot_definition_js',
            '#tag' => $tag_view,
          ],
          'dfp-slot-definition-' . $tag_view->id(),
        ];
      }

      $attachments['html_head'][] = [
        $this->getHeadBottom(),
        'dfp-js-head-bottom',
      ];

      unset($attachments['dfp_slot']);
    }
    $response->setAttachments($attachments);

    // Call HtmlResponseAttachmentsProcessor to process all other attachments.
    return $this->htmlResponseAttachmentsProcessor->processAttachments($response);
  }

  /**
   * Gets the javascript to add before the slot definitions.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  protected function getHeadTop() {
    return [
      // Use a fake #type to prevent
      // HtmlResponseAttachmentsProcessor::processHead() adding one.
      '#type' => 'dfp_script',
      '#theme' => 'dfp_js_head_top',
      '#google_tag_services_url' => TagInterface::GOOGLE_TAG_SERVICES_URL,
    ];
  }

  /**
   * Gets the javascript to add after the slot definitions.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  protected function getHeadBottom() {
    $global_settings = $this->configFactory->get('dfp.settings');
    $targeting = $global_settings->get('targeting');
    $this->moduleHandler->alter('dfp_global_targeting', $targeting);
    $targeting = TagView::formatTargeting($targeting, $this->token, $this->moduleHandler);
    return [
      // Use a fake #type to prevent
      // HtmlResponseAttachmentsProcessor::processHead() adding one.
      '#type' => 'dfp_script',
      '#theme' => 'dfp_js_head_bottom',
      '#async_rendering' => $global_settings->get('async_rendering'),
      '#single_request' => $global_settings->get('single_request'),
      '#collapse_empty_divs' => $global_settings->get('collapse_empty_divs'),
      '#disable_init_load' => $global_settings->get('disable_init_load'),
      '#targeting' => $targeting,
    ];
  }

}
