<?php

/**
 * @file
 * Contains \Drupal\dfp\Tests\GlobalSettingsTest.
 */

namespace Drupal\dfp\Tests;

/**
 * Tests DFP global configuration.
 *
 * @group dfp
 *
 * @see dfp.settings.yml
 * @see \Drupal\dfp\Form\AdminSettings
 */
class GlobalSettingsTest extends DfpTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dfp'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(['administer DFP']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests \Drupal\dfp\Form\AdminSettings form and dfp_page_attachments().
   */
  public function testGlobalSettings() {
    $edit = [
      'network_id' => '123456789',
      'async_rendering' => TRUE,
      'single_request' => TRUE,
      'collapse_empty_divs' => '1',
      'targeting[0][target]' => '<em>test target</em>',
      'targeting[0][value]' => '<em>test value</em>, test value 2 ',
    ];
    $this->drupalPostForm('admin/structure/dfp/settings', $edit, t('Save configuration'));

    $this->drupalGet('<front>');
    $this->assertNoRaw('googletag', 'With no DFP tags set up there is no additional JS added');

    // Create a tag.
    $this->dfpCreateTag();

    $this->drupalGet('<front>');
    $this->assertRaw('googletag.pubads().enableAsyncRendering();', 'Asynchronous rendering is turned on.');
    $this->assertRaw('googletag.pubads().enableSingleRequest();', 'Single request is turned on.');
    $this->assertRaw('googletag.pubads().collapseEmptyDivs();', 'Collapse empty divs is turned on.');
    $this->assertRaw("googletag.pubads().setTargeting('&lt;em&gt;test target&lt;/em&gt;', ['&lt;em&gt;test value&lt;/em&gt;','test value 2']);", 'Global targeting values appear correclty in javascript.');

    $edit = [
      'network_id' => '123456789',
      'async_rendering' => FALSE,
      'single_request' => FALSE,
      'collapse_empty_divs' => '0',
      'click_url' => '/custom_click_url',
      'targeting[0][target]' => 'test target ',
      'targeting[0][value]' => 'test value 3',
      'targeting[1][target]' => 'test target 2',
      'targeting[1][value]' => 'test value 4',
    ];
    $this->drupalPostForm('admin/structure/dfp/settings', $edit, t('Save configuration'));

    $this->drupalGet('<front>');
    $this->assertNoRaw('googletag.pubads().enableAsyncRendering();', 'Asynchronous rendering is turned on.');
    $this->assertNoRaw('googletag.pubads().enableSingleRequest();', 'Single request is turned on.');
    $this->assertNoRaw('googletag.pubads().collapseEmptyDivs();', 'Collapse empty divs is turned on.');
    $this->assertRaw("googletag.pubads().setTargeting('test target', ['test value 3']);", 'Global targeting values appear correctly in javascript.');
    $this->assertRaw("googletag.pubads().setTargeting('test target 2', ['test value 4']);", 'Global targeting values appear correctly in javascript.');
    $this->assertEqual('/custom_click_url', \Drupal::config('dfp.settings')->get('click_url'));

    $edit = [
      'async_rendering' => TRUE,
      'click_url' => '/custom_click_url',
      'adunit_pattern' => '$has_an_illegal_character',
    ];
    $this->drupalPostForm('admin/structure/dfp/settings', $edit, t('Save configuration'));
    $this->assertText(t('Setting a click URL does not work with async rendering.'));
    $this->assertText(t('Ad Unit Patterns can only include letters, numbers, hyphens, dashes, periods, slashes and tokens.'));
  }

}
