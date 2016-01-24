<?php

/**
 * @file
 * Contains \Drupal\dfp\View\TagView.
 */

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
   * The short tag query string.
   *
   * @var string
   */
  protected $shortTagQueryString;

  /**
   * The ad unit with tokens replaced.
   *
   * @var string
   */
  protected $adUnit;

  /**
   * The targeting, altered and tokens replaced.
   *
   * @var array
   */
  protected $targeting;

  /**
   * The breakpoints.
   *
   * @var array
   */
  protected $breakpoints;

  /**
   * The click URL shared across object instances.
   *
   * @var string
   */
  protected static $clickUrl;

  /**
   * The global DFP configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $globalSettings;

  /**
   * The DFP tag.
   *
   * @var \Drupal\dfp\Entity\TagInterface
   */
  protected $tag;

  /**
   * The DFP token service.
   *
   * @var \Drupal\dfp\TokenInterface
   */
  protected $token;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * TagView constructor.
   *
   * @param \Drupal\dfp\Entity\TagInterface $tag
   *   The DFP tag.
   * @param \Drupal\Core\Config\ImmutableConfig $global_settings
   *   The DFP global configuration.
   * @param \Drupal\dfp\TokenInterface $token
   *   The DFP token service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(TagInterface $tag, ImmutableConfig $global_settings, TokenInterface $token, ModuleHandlerInterface $module_handler) {
    $this->tag = $tag;
    $this->globalSettings = $global_settings;
    $this->token = $token;
    $this->moduleHandler = $module_handler;
  }

  /**
   * The gets the slug.
   *
   * @return string
   *   The slug.
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

  /**
   * Gets the placeholder ID.
   *
   * @return string
   *   The placeholder ID
   */
  public function getPlaceholderId() {
    return 'js-dfp-tag-' . $this->tag->id();
  }

  /**
   * Gets the ad unit.
   *
   * @return string
   *   The ad unit.
   */
  public function getAdUnit() {
    if (is_null($this->adUnit)) {
      $adunit = $this->tag->adunit();
      if (empty($adunit)) {
        $adunit = $this->globalSettings->get('default_pattern');
      }
      $this->adUnit = '/' . $this->globalSettings->get('network_id') . '/' . $this->token->replace($adunit, $this, ['clear' => TRUE]);
    }
    return $this->adUnit;
  }

  /**
   * Gets the raw size from the DFP tag.
   *
   * @return string
   *   The ad size or sizes. Example: 300x600,300x250.
   */
  public function getRawSize() {
    return $this->tag->size();
  }

  /**
   * Gets the raw ad targeting from the DFP tag.
   *
   * @return array[]
   *   Each value is a array containing two keys: 'target' and 'value'. Both
   *   values are strings. Multiple value values are delimited by a comma.
   */
  public function getRawTargeting() {
    return $this->tag->targeting();
  }

  /**
   * Gets the ad slot.
   *
   * @return string
   *   The ad slot. This is the same as the label for the configuration entity.
   */
  public function getSlot() {
    return $this->tag->slot();
  }

  /**
   * Gets the short tag query string.
   *
   * @return string
   *   The short tag query string.
   *
   * @see https://support.google.com/dfp_sb/answer/2623168
   */
  public function getShortTagQueryString() {
    if (is_null($this->shortTagQueryString)) {
      // Build a key|values array and allow third party modules to modify it.
      $key_values = [
        'iu' => $this->getAdUnit(),
        'sz' => str_replace(',', '|', $this->getRawSize()),
        'c' => mt_rand(10000, 99999),
      ];

      $targets = [];
      foreach ($this->getRawTargeting() as $data) {
        $targets[] = $data['target'] . '=' . $data['value'];
      }
      if (!empty($targets)) {
        $key_values['t'] = implode('&', $targets);
      }
      $this->moduleHandler->alter('dfp_short_tag_keyvals', $key_values);
      $this->shortTagQueryString = UrlHelper::buildQuery($key_values);
    }
    return $this->shortTagQueryString;
  }

  /**
   * Determines whether to display the tag as a short tag.
   *
   * @return bool
   *   TRUE to display the tag as a short tag, FALSE if not.
   */
  public function isShortTag() {
    return $this->tag->shortTag();
  }

  /**
   * Gets the DFP ad tag identifier.
   *
   * @return string
   *   The DFP ad tag identifier.
   */
  public function id() {
    return $this->tag->id();
  }

  /**
   * Gets the ad targeting.
   *
   * @return array[]
   *   Each value is a array containing two keys: 'target' and 'value'. The
   *   'target' value is a string and the 'value' value is an array of strings.
   */
  public function getTargeting() {
    if (is_null($this->targeting)) {
      $this->targeting = self::formatTargeting($this->tag->targeting(), $this->token, $this->moduleHandler, $this);
    }
    return $this->targeting;
  }

  /**
   * Formats a targeting array.
   *
   * @param array $targeting
   *   The targeting array. An array of arrays. Each each has two keys 'target'
   *   and 'value'. The 'target' value is a string. The 'value' value is a
   *   string with multiple values delimited by a comma.
   * @param \Drupal\dfp\TokenInterface $token
   *   The DFP token service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\dfp\View\TagView|NULL $tag_view
   *   (optional) The TagView object. Defaults to NULL.
   *
   * @return array
   *   An array of arrays. Each each has two keys 'target' and 'value'. The
   *   'target' value is a trimmed string. The 'value' value is an array of
   *   strings, also trimmed.
   */
  public static function formatTargeting(array $targeting, TokenInterface $token, ModuleHandlerInterface $module_handler, TagView $tag_view = NULL) {
    foreach ($targeting as $key => &$target) {
      $target['target'] = trim($target['target']);
      $target['value'] = $token->replace($target['value'], $tag_view, ['clear' => TRUE]);
      // The target value could be blank if tokens are used. If so, remove it.
      if (empty($target['value'])) {
        unset($targeting[$key]);
        continue;
      }

      // Allow other modules to alter the target.
      $module_handler->alter('dfp_target', $target);

      // Convert the values into an array.
      $target['value'] = array_map('trim', explode(',', $target['value']));
    }
    return $targeting;
  }

  /**
   * Gets the breakpoints.
   *
   * @return array[]
   *   Each value is a array containing two keys: 'browser_size' and 'ad_sizes'.
   *   The 'browser_size' is a value such as '[1024,768]'. The 'ad_sizes' value
   *   contains a list of ad sizes to be be used at this 'browser_size' such as
   *   '[[300,600],[300,250]]'.
   *
   * @see \Drupal\dfp\View\TagView::formatSize()
   */
  public function getBreakpoints() {
    if (is_null($this->breakpoints)) {
      $this->breakpoints = array_map(function ($breakpoint) {
        return [
          'browser_size' => self::formatSize($breakpoint['browser_size']),
          'ad_sizes' => self::formatSize($breakpoint['ad_sizes']),
        ];
      }, $this->tag->breakpoints());
    }
    return $this->breakpoints;
  }

  /**
   * Gets the ad size or sizes.
   *
   * @return string
   *   The ad size or sizes. Example: 300x600,300x250.
   */
  public function getSize() {
    return self::formatSize($this->tag->size());
  }

  /**
   * Formats a size or sizes for javascript.
   *
   * @param string $size
   *   A size to format. Multiple sizes delimited by comma. Example:
   *   '300x600,300x250'.
   *
   * @return string
   *   A string representing sizes that can be used in javascript. Example:
   *   '[[300,600],[300,250]]'.
   */
  public static function formatSize($size) {
    $formatted_sizes = [];

    $sizes = explode(',', $size);
    foreach ($sizes as $size) {
      $formatted_size = explode('x', trim($size));
      $formatted_sizes[] = '[' . implode(', ', $formatted_size) . ']';
    }

    return count($formatted_sizes) == 1 ? $formatted_sizes[0] : '[' . implode(', ', $formatted_sizes) . ']';
  }

  /**
   * Gets the type of ads displayed when AdSense ads are used for backfill.
   *
   * @return string
   *   The type of ads displayed when AdSense ads are used for backfill.
   */
  public function getAdsenseAdTypes() {
    return $this->tag->adsenseAdTypes();
  }

  /**
   * Gets the Adsense channel ID(s) when AdSense ads are used for backfill.
   *
   * @return string
   *   The Adsense channel ID(s) when AdSense ads are used for backfill.
   *   Multiple IDs are delimited by a + sign.
   */
  public function getAdsenseChannelIds() {
    return $this->tag->adsenseChannelIds();
  }

  /**
   * Gets the colors used when AdSense ads are used for backfill.
   *
   * @return string[]
   *   An array keyed by setting with hex colors as values.
   */
  public function getAdSenseColors() {
    return array_filter($this->tag->adsenseColors());
  }

  /**
   * Gets the click URL.
   *
   * @return string
   *   The click URL.
   */
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

  /**
   * Gets whether the slug should be hidden.
   *
   * @return bool
   *   TRUE if the slug should be hidden, FALSE if not.
   */
  public function isSlugHidden() {
    return $this->globalSettings->get('hide_slug');
  }

}
