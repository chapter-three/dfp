<?php

/**
 * @file
 * Contains \Drupal\dfp\Token.
 */

namespace Drupal\dfp;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token as CoreToken;
use Drupal\dfp\View\TagView;

/**
 * A DFP token service to wrap core's service.
 */
class Token implements TokenInterface {

  /**
   * Drupal core's token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $coreToken;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Token constructor.
   *
   * @param \Drupal\Core\Utility\Token $core_token
   *   Drupal core's token service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   */
  public function __construct(CoreToken $core_token, RouteMatchInterface $route_match, AccountInterface $account) {
    $this->coreToken = $core_token;
    $this->routeMatch = $route_match;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function replace($text, TagView $tag, array $options = array(), BubbleableMetadata $bubbleable_metadata = NULL) {
    $data = [
      'dfp_tag' => $tag,
      'user' => $this->account,
    ];

    // Determine other data from the RouteMatch object.
    $node = $this->routeMatch->getParameter('node');
    if ($node) {
      $data['node'] = $node;
    }
    $term = $this->routeMatch->getParameter('taxonomy_term');
    if ($term) {
      $data['term'] = $term;
    }

    return $this->coreToken->replace($text, $data, $options, $bubbleable_metadata);
  }

}
