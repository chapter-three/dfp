<?php

namespace Drupal\dfp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface TagInterface extends ConfigEntityInterface {

  const ADUNIT_PATTERN_VALIDATION_REGEX = '@[^a-zA-Z0-9\/\-_\.\[\]\:]+@';
  const ADSENSE_TEXT_IMAGE = 'text_image';
  const ADSENSE_IMAGE = 'image';
  const ADSENSE_TEXT = 'text';


  /**
   * @return string
   */
  public function slot();

  /**
   * @return string
   */
  public function size();

  /**
   * @return string
   */
  public function pattern();

  /**
   * @return string
   */
  public function slug();

  /**
   * @return bool
   */
  public function hasBlock();

  /**
   * @return string
   */
  public function shortTag();

  public function targeting();

  public function adsenseAdTypes();

  public function adsenseChannelIds();

  public function adsenseColor($item);

  public function breakpoints();
}
