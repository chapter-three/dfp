<?php

/**
 * @file
 * Contains \Drupal\dfp\TokenInterface.
 */

namespace Drupal\dfp;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\dfp\View\TagView;

interface TokenInterface {

  /**
   * Replaces all tokens in a given string with appropriate values.
   *
   * @param string $text
   *   An HTML string containing replaceable tokens.
   * @param \Drupal\dfp\View\TagView $tag
   *   An TagView object that merges values of the Tag and global settings.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the token
   *   replacement process.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata|null
   *   (optional) An object to which static::generate() and the hooks and
   *   functions that it invokes will add their required bubbleable metadata.
   *
   * @return string
   *   The token result is the entered HTML text with tokens replaced. The
   *   caller is responsible for choosing the right escaping / sanitization. If
   *   the result is intended to be used as plain text, using
   *   PlainTextOutput::renderFromHtml() is recommended. If the result is just
   *   printed as part of a template relying on Twig autoescaping is possible,
   *   otherwise for example the result can be put into #markup, in which case
   *   it would be sanitized by Xss::filterAdmin().
   *
   * @see \Drupal\Core\Utility\Token::replace()
   */
  public function replace($text, TagView $tag, array $options = array(), BubbleableMetadata $bubbleable_metadata = NULL);

}
