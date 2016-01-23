<?php

/**
 * @file
 * Contains \Drupal\dfp\Entity\TagInterface.
 */

namespace Drupal\dfp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * An interface for the DFP Ad tag configuration entity.
 */
interface TagInterface extends ConfigEntityInterface {

  const ADUNIT_PATTERN_VALIDATION_REGEX = '@[^a-zA-Z0-9\/\-_\.\[\]\:]+@';
  const ADSENSE_TEXT_IMAGE = 'text_image';
  const ADSENSE_IMAGE = 'image';
  const ADSENSE_TEXT = 'text';
  const GOOGLE_SHORT_TAG_SERVICES_URL = 'pubads.g.doubleclick.net/gampad';
  const GOOGLE_TAG_SERVICES_URL = 'www.googletagservices.com/tag/js/gpt.js';

  /**
   * Gets the ad slot.
   *
   * @return string
   *   The ad slot. This is the same as the label for the configuration entity.
   */
  public function slot();

  /**
   * Gets the ad size or sizes.
   *
   * @return string
   *   The ad size or sizes. Example: 300x600,300x250.
   */
  public function size();

  /**
   * Gets ad unit pattern.
   *
   * @return string
   *   The ad unit pattern. May contain tokens.
   */
  public function adunit();

  /**
   * Gets the slug.
   *
   * @return string
   *   The slug.
   */
  public function slug();

  /**
   * Determines whether the tag provides a block plugin.
   *
   * @return bool
   *   TRUE if the tag provides a block plugin, FALSE if not.
   *
   * @see \Drupal\dfp\Plugin\Derivative\TagBlock
   */
  public function hasBlock();

  /**
   * Determines whether to display the tag as a short tag.
   *
   * @return bool
   *   TRUE to display the tag as a short tag, FALSE if not.
   */
  public function shortTag();

  /**
   * Gets the ad targeting.
   *
   * @return array[]
   *   Each value is a array containing two keys: 'target' and 'value'. Both
   *   values are strings. Multiple value values are delimited by a comma.
   */
  public function targeting();

  /**
   * Gets the type of ads displayed when AdSense ads are used for backfill.
   *
   * @return string
   *   The type of ads displayed when AdSense ads are used for backfill.
   */
  public function adsenseAdTypes();

  /**
   * Gets the Adsense channel ID(s) when AdSense ads are used for backfill.
   *
   * @return string
   *   The Adsense channel ID(s) when AdSense ads are used for backfill.
   *   Multiple IDs are delimited by a + sign.
   */
  public function adsenseChannelIds();

  /**
   * Gets the color for a setting when AdSense ads are used for backfill.
   *
   * @param string $setting
   *   The setting to get the color for. Either: 'background', 'border', 'link',
   *   'text' or 'url'.
   *
   * @return string
   *   The color for a setting when AdSense ads are used for backfill. For
   *   example: FFFFFF.
   */
  public function adsenseColor($setting);

  /**
   * Gets the colors used when AdSense ads are used for backfill.
   *
   * @return string[]
   *   An array keyed by setting with hex colors as values.
   */
  public function adsenseColors();

  /**
   * Gets the breakpoints.
   *
   * @return array[]
   *   Each value is a array containing two keys: 'browser_size' and 'ad_sizes'.
   *   The 'browser_size' is a value such as '1024x768'. The 'ad_sizes' value
   *   contains a list of ad sizes to be be used at this 'browser_size'.
   */
  public function breakpoints();

}
