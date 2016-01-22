<?php

/**
 * @file
 * Contains \Drupal\dfp\HtmlResponseSubscriber.
 */

namespace Drupal\dfp\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\dfp\TokenInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to add DFP scripts to HTML responses.
 */
class HtmlResponseSubscriber implements EventSubscriberInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The DFP token service.
   *
   * @var \Drupal\dfp\TokenInterface
   */
  protected $token;

  /**
   * HtmlResponseSubscriber constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\dfp\TokenInterface $token
   *   The DFP token service.
   */
  public function __construct(RendererInterface $renderer, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, TokenInterface $token) {
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->token = $token;
  }

  /**
   * Processes attachments for HtmlResponse responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof HtmlResponse) {
      return;
    }

    $attachments = $response->getAttachments();
    if (isset($attachments['html_head'])) {
      $last = $first = FALSE;
      foreach ($attachments['html_head'] as $item) {
        list($data, $key) = $item;
        if (strpos($key, 'dfp-slot-definition-') === 0) {
          if (!$first) {
            $first = $data['#markup'];
          }
          $last = $data['#markup'];
        }
      }
      if ($first && $last) {
        $content = $response->getContent();
        $content = str_replace($first, $this->getHeadTop() . $first, $content);
        $content = str_replace($last, $last . $this->getHeadBottom(), $content);
        $response->setContent($content);
        $event->setResponse($response);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Running after core's Drupal\Core\EventSubscriber\HtmlResponseSubscriber.
    $events[KernelEvents::RESPONSE][] = ['onRespond', '-1'];
    return $events;
  }

  /**
   * Gets the javascript to add before the slot definitions.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  protected function getHeadTop() {
    $build = [
      '#theme' => 'dfp_js_head_top',
      '#google_tag_services_url' => DFP_GOOGLE_TAG_SERVICES_URL,
    ];
    return $this->renderer->renderPlain($build);
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
    $targeting = \Drupal\dfp\View\TagView::formatTargeting($targeting, $this->token, $this->moduleHandler);
    $build = [
      '#theme' => 'dfp_js_head_bottom',
      '#async_rendering' => $global_settings->get('async_rendering'),
      '#single_request' => $global_settings->get('single_request'),
      '#collapse_empty_divs' => $global_settings->get('collapse_empty_divs'),
      '#disable_init_load' => $global_settings->get('disable_init_load'),
      '#targeting' => $targeting,
    ];
    return $this->renderer->renderPlain($build);
  }

}
