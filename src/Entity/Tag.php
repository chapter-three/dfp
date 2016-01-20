<?php

/**
 * @file
 * Contains \Drupal\dfp\Entity\Tag.
 */

namespace Drupal\dfp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the DFP Ad tag configuration entity class.
 *
 * @todo list_cache_tags
 *
 * @ConfigEntityType(
 *   id = "dfp_tag",
 *   config_prefix = "tag",
 *   label = @Translation("DFP tag"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\dfp\Form\Tag",
 *       "edit" = "Drupal\dfp\Form\Tag",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\dfp\View\TagList",
 *     "view_builder" = "Drupal\dfp\View\TagViewBuilder",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/dfp_ads/tags/manage/{tag}",
 *     "delete-form" = "/admin/structure/dfp_ads/tags/manage/{tag}/delete",
 *     "collection" = "/admin/structure/dfp_ads/tags",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "slot"
 *   },
 *   admin_permission = "Administer DFP",
 *   list_cache_tags = { "rendered" },
 *   config_export = {
 *     "id",
 *     "slot",
 *     "size",
 *     "adunit",
 *     "slug",
 *     "block",
 *     "short_tag",
 *     "breakpoints",
 *     "targeting",
 *     "adsense_backfill"
 *   },
 * )
 */
class Tag extends ConfigEntityBase implements TagInterface {

  /**
   * The unique tag ID.
   *
   * Limited to d7 machine name regex.
   *
   * @var string
   */
  protected $id;

  /**
   * The ad slot name.
   *
   * @var string
   */
  protected $slot;

  /**
   * Out of page (interstitial) ad slot.
   *
   * Use Context module to place the Ad slot on the page.
   *
   * @var boolean
   *
   * @todo Not used at present. Regression from D7.
   */
  protected $out_of_page;

  /**
   * Size(s).
   *
   * Example: 300x600,300x250. For Out Of Page slots, use 0x0
   *
   * @var string
   */
  protected $size;

  /**
   * The default Brightcove player to use with this client.
   *
   * Ad Unit Patterns can only include letters, numbers, hyphens, dashes,
   * periods, slashes and tokens.
   *
   * @var string
   */
  protected $adunit;

  /**
   * Slug.
   *
   * Override the default slug for this ad tag. Use <none> for no slug. Leave
   * this field empty to use the default slug. Example: Advertisement.
   *
   * @var string
   */
  protected $slug;

  /**
   * Create a block for this ad tag.
   *
   * @var boolean
   */
  protected $block = TRUE;

  /**
   * Render this tag without javascript.
   *
   * Use this option for ads included in emails.
   *
   * @var boolean
   */
  protected $short_tag = FALSE;

  /**
   * The breakpoints.
   *
   * @var array
   */
  protected $breakpoints = [];

  /**
   * The ad targeting.
   *
   * @var array
   */
  protected $targeting = [];

  /**
   * Settings used when AdSense ads are used for backfill.
   *
   * @var array
   */
  protected $adsense_backfill = [];

  /**
   * {@inheritdoc}
   */
  public function slot() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function size() {
    return $this->size;
  }

  /**
   * {@inheritdoc}
   */
  public function adunit() {
    return $this->adunit;
  }

  /**
   * {@inheritdoc}
   */
  public function slug() {
    return $this->slug;
  }

  /**
   * {@inheritdoc}
   */
  public function hasBlock() {
    return $this->block;
  }

  /**
   * {@inheritdoc}
   */
  public function shortTag() {
    return $this->short_tag;
  }

  /**
   * {@inheritdoc}
   */
  public function targeting() {
    return $this->targeting;
  }

  /**
   * {@inheritdoc}
   */
  public function breakpoints() {
    return $this->breakpoints;
  }

  /**
   * {@inheritdoc}
   */
  public function adsenseAdTypes() {
    return isset($this->adsense_backfill['ad_types']) ? $this->adsense_backfill['ad_types'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function adsenseChannelIds() {
    return isset($this->adsense_backfill['channel_ids']) ? $this->adsense_backfill['channel_ids'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function adsenseColor($setting) {
    return isset($this->adsense_backfill['color'][$setting]) ? $this->adsense_backfill['color'][$setting] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function adsenseColors() {
    return isset($this->adsense_backfill['color']) ? $this->adsense_backfill['color'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Invalidate the block cache to update DFP ad tag-based derivatives.
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

}
