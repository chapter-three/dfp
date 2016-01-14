<?php

namespace Drupal\dfp\View;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\dfp\Entity\TagInterface;
use Drupal\dfp\TokenInterface;

/**
 * A value object to combine a DFP tag with global settings for display.
 */
class TagView {
  use DependencySerializationTrait;

  /**
   * @var string
   */
  protected $shortTagQueryString;

  /**
   * @var string
   */
  protected $adUnit;

  /**
   * @var array
   */
  protected $targets;

  /**
   * @var array
   */
  protected $breakpoints;

  /**
   * @var string
   */
  protected static $clickUrl;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $globalSettings;

  /**
   * @var \Drupal\dfp\Entity\TagInterface
   */
  protected $tag;

  /**
   * @var \Drupal\dfp\TokenInterface
   */
  protected $token;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(TagInterface $tag, ImmutableConfig $global_settings, TokenInterface $token, ModuleHandlerInterface $module_handler) {
    $this->tag = $tag;
    $this->globalSettings = $global_settings;
    $this->token = $token;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlug() {
    $slug = $this->tag->slug();
    if (empty($slug)) {
      $slug = $this->globalSettings->get('default_slug');
    }
    if ($slug == '<none>') {
      $slug = "";
    }
    return $slug;
  }

  public function getPlaceholderId() {
    return 'js-dfp-tag-' . $this->tag->id();
  }

  public function getAdUnit() {
    if (is_null($this->adUnit)) {
      $adunit = $this->tag->adunit();
      if (empty($adunit)) {
        $adunit = $this->globalSettings->get('default_pattern');
      }
      $this->adUnit = $this->token->replace('/[dfp_tag:network_id]/' . $adunit, $this, ['clear' => TRUE]);
    }
    return $this->adUnit;
  }

  public function getRawSize() {
    return $this->tag->size();
  }

  public function getRawTargetting() {
    return $this->tag->targeting();
  }

  public function getSlot() {
    return $this->tag->slot();
  }

  public function getShortTagQueryString() {
    if (is_null($this->shortTagQueryString)) {
      // Build a key|vals array and allow third party modules to modify it.
      $keyvals = [
        'iu' => $this->getAdUnit(),
        'sz' => str_replace(',', '|', $this->getRawSize()),
        'c' => rand(10000, 99999),
      ];

      $targets = array();
      foreach ($this->getRawTargetting() as $data) {
        $targets[] = $data['target'] . '=' . $data['value'];
      }
      if (!empty($targets)) {
        $keyvals['t'] = implode('&', $targets);
      }
      $this->moduleHandler->alter('dfp_short_tag_keyvals', $keyvals);
      $this->shortTagQueryString = UrlHelper::buildQuery($keyvals);
    }
    return $this->shortTagQueryString;
  }

  public function isShortTag() {
    return $this->tag->shortTag();
  }

  public function id() {
    return $this->tag->id();
  }

  public function getTargets() {
    if (is_null($this->targets)) {
      $targets = $this->tag->targeting();
      foreach ($targets as $key => &$target) {
        $target['value'] = $this->token->replace($target['value'], $this, ['clear' => TRUE]);
        // The target value could be blank if tokens are used. If so, removed it.
        if (empty($target['value'])) {
          unset($targets[$key]);
          continue;
        }

        // Allow other modules to alter the target.
        $this->moduleHandler->alter('dfp_target', $target);

        // Convert the values into an array and trim the whitespace from each value.
        $target['value'] = explode(',', $target['value']);

      }
      $this->targets = $targets;
    }
    return $this->targets;
  }

  public function getBreakpoints() {
    if (is_null($this->breakpoints)) {
      $this->breakpoints = array_map(function ($breakpoint) {
        return [
          'browser_size' => self::formatSize($breakpoint['browser_size']),
          'ad_sizes' => self::formatSize($breakpoint['ad_sizes']),
        ];
      },  $this->tag->breakpoints());
    }
    return $this->breakpoints;
  }

  public function getSize() {
    return self::formatSize($this->tag->size());
  }

  public static function formatSize($size) {
    $formatted_sizes = [];

    $sizes = explode(',', $size);
    foreach ($sizes as $size) {
      $formatted_size = explode('x', trim($size));
      $formatted_sizes[] = '[' . implode(', ', $formatted_size) . ']';
    }

    return count($formatted_sizes) == 1 ? $formatted_sizes[0] : '[' . implode(', ', $formatted_sizes) . ']';
  }

  public function getAdsenseAdTypes() {
    return $this->tag->adsenseAdTypes();
  }

  public function getAdsenseChannelIds() {
    return $this->tag->adsenseChannelIds();
  }

  public function getAdSenseColors() {
    return array_filter($this->tag->adsenseColors());
  }

  public function getClickUrl() {
    // Since this can't change during a request statically cache it.
    if (is_null(self::$clickUrl)) {
      self::$clickUrl = (string) $this->globalSettings->get('click_url');
      if (self::$clickUrl && !preg_match("/^https?:\/\//", self::$clickUrl)) {
        self::$clickUrl = Url::fromUserInput(self::$clickUrl, ['absolute' => TRUE])->toString();
      }
    }
    return self::$clickUrl;
  }

  public function getSlugPlacement() {
    return $this->globalSettings->get('slug_placement');
  }

}
