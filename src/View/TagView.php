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

  public function __construct(TagInterface $tag, ImmutableConfig $global_settings) {
    $this->tag = $tag;
    $this->globalSettings = $global_settings;
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

}
