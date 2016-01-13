<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 12/01/16
 * Time: 15:44
 */

namespace Drupal\dfp\View;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\dfp\Entity\TagInterface;
use Drupal\dfp\TokenInterface;

/**
 * A value object to combine a DFP tag with global settings for display.
 */
class TagView {

  protected $shortTagQueryString;

  protected $adUnit;

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
}
