<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 12/01/16
 * Time: 15:44
 */

namespace Drupal\dfp\View;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\dfp\Entity\TagInterface;
use Drupal\dfp\TokenInterface;

/**
 * A value object to combine a DFP tag with global settings for display.
 */
class TagView {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $globalSettings;

  /**
   * @var \Drupal\dfp\Entity\TagInterface
   */
  protected $tag;

  public function __construct(TagInterface $tag, ImmutableConfig $global_settings, TokenInterface $token) {
    $this->tag = $tag;
    $this->globalSettings = $global_settings;
    $this->token = $token;
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
    $adunit = $this->tag->adunit();
    if (empty($adunit)) {
      $adunit = $this->globalSettings->get('default_pattern');
    }
    return $this->token->replace('/[dfp_tag:network_id]/' . $adunit, $this, ['clear' => TRUE]);
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
}
