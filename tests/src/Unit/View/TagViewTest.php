<?php

/**
 * @file
 * Contains \Drupal\Tests\dfp\Unit\View\TagViewTest.
 */

namespace Drupal\Tests\dfp\Unit\View;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\dfp\TokenInterface;
use Drupal\dfp\Entity\TagInterface;
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
   */
  public function formatSizeProvider() {
    return [
      ['300x250 ', '[300, 250]'],
      ['300x250, 728x90 ', '[[300, 250], [728, 90]]'],
    ];
  }

  /**
   * @covers ::getSlug
   * @dataProvider getSlugProvider
   */
  public function testGetSlug($tag_slug, $default_slug, $expected_slug) {
    $tag = $this->prophesize(TagInterface::class);
    $tag->slug()->willReturn($tag_slug);
    $config_factory = $this->getConfigFactoryStub(['dfp.settings' => ['default_slug' => $default_slug]]);
    $token = $this->prophesize(TokenInterface::class)->reveal();
    $module_handler = $this->prophesize(ModuleHandlerInterface::class)->reveal();
    $tag_view = new TagView($tag->reveal(), $config_factory->get('dfp.settings'), $token, $module_handler);
    $this->assertSame($expected_slug, $tag_view->getSlug());
  }

  /**
   * Data provider for self::testGetSlug().
   */
  public function getSlugProvider() {
    return [
      ['slug', 'default_slug', 'slug'],
      ['', 'default_slug', 'default_slug'],
      ['<none>', 'default_slug', ''],
    ];
  }

  /**
   * @covers ::getAdUnit
   * @dataProvider getAdUnitProvider
   */
  public function testGetAdUnit($tag_ad_unit, $default_ad_unit, $network_id, $expected_adunit) {
    $tag = $this->prophesize(TagInterface::class);
    $tag->adunit()->willReturn($tag_ad_unit);
    $config_factory = $this->getConfigFactoryStub(['dfp.settings' => ['default_pattern' => $default_ad_unit, 'network_id' => $network_id]]);
    $token = $this->getMock(TokenInterface::class);
    $token->method('replace')->willReturnArgument(0);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class)->reveal();
    $tag_view = new TagView($tag->reveal(), $config_factory->get('dfp.settings'), $token, $module_handler);
    $this->assertSame($expected_adunit, $tag_view->getAdUnit());
  }

  /**
   * Data provider for self::testGetAdUnit().
   */
  public function getAdUnitProvider() {
    return [
      ['adunit', 'default_adunit', '12345', '/12345/adunit'],
      ['', 'default_adunit', '67890', '/67890/default_adunit'],
    ];
  }

}
