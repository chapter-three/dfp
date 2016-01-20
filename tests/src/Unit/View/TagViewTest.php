<?php

/**
 * @file
 * Contains \Drupal\Tests\dfp\Unit\View\TagViewTest.
 */

namespace Drupal\Tests\dfp\Unit\View;

use Drupal\dfp\View\TagView;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\dfp\View\TagView
 * @group dfp
 */
class TagViewTest extends UnitTestCase {

  /**
   * @covers ::formatSize
   * @dataProvider formatSizeProvider
   */
  public function testFormatSize($size, $expected) {
    $this->assertSame($expected, TagView::formatSize($size));
  }

  /**
   * Data provider for self::testFormatSize().
   *
   * @return array
   */
  public function formatSizeProvider() {
    return [
      ['300x250 ', '[300, 250]'],
      ['300x250, 728x90 ', '[[300, 250], [728, 90]]'],
    ];
  }

}
