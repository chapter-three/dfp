<?php

/**
 * @file
 * Contains \Drupal\dfp\Tests\DisplayTagTest.
 */

namespace Drupal\dfp\Tests;

use Drupal\Component\Utility\Unicode;

/**
 * Tests display of DFP ad tag.
 *
 * @group dfp
 */
class DisplayTagTest extends DfpTestBase {

  /**
   * Tests display a DFP tag in a block.
   */
  public function testDisplayTag() {
    // Create a simple tag as a block.
    $tag = $this->dfpCreateTag();
    $tag_view = $this->dfpTagToTagView($tag);
    $this->drupalGet('<front>');
    $this->assertRaw('googletag.defineSlot("' . $tag_view->getAdUnit() . '", ' . $tag_view->getSize() . ', "' . $tag_view->getPlaceholderId() . '")');

    // Create a tag with an ID longer than 32 characters.
    $edit = ['id' => Unicode::strtolower($this->randomMachineName(64))];
    $tag = $this->dfpCreateTag($edit);
    $tag_view = $this->dfpTagToTagView($tag);
    $this->drupalGet('<front>');
    $this->assertRaw('googletag.defineSlot("' . $tag_view->getAdUnit() . '", ' . $tag_view->getSize() . ', "' . $tag_view->getPlaceholderId() . '")');
  }

  /**
   * Tests breakpoint mappings.
   *
   * @todo add multiple breakpoint configs to the tag.
   */
  public function testDisplayTagWithMapping() {
    $edit = $this->dfpBasicTagEditValues();

    // Create a simple tag with a mapping. Verify javascript on page.
    $tag = $this->dfpCreateTag($edit);
    $this->drupalGet('<front>');
    $mapping_sizes = explode(',', $edit['breakpoints[0][ad_sizes]']);
    $size_count = count($mapping_sizes);
    // Calculate addSize mappings.
    $mapping_tag = '.addSize(';
    $mapping_tag .= '[' . str_replace('x', ', ', $edit['breakpoints[0][browser_size]']) . '], ';
    $mapping_tag .= ($size_count > 1) ? '[' : '';
    for ($i = 0; $i < $size_count; $i++) {
      $mapping_sizes[$i] = '[' . str_replace('x', ', ', $mapping_sizes[$i]) . ']';
      $mapping_tag .= ($i + 1 !== $size_count) ? $mapping_sizes[$i] . ', ' : $mapping_sizes[$i];
    }
    $mapping_tag .= ($size_count > 1) ? '])' : ')';
    $this->assertRaw('googletag.sizeMapping()', 'The ad slot correctly attaches size mapping.');
    $this->assertRaw('.defineSizeMapping(mapping)', 'The ad slot correctly defines size mapping.');
    $this->assertRaw($mapping_tag, 'The ad slot correctly defines specific size mappings.');

    // Create a tag with invalid browser size mappings.
    $edit['breakpoints[0][browser_size]'] = '100y100';
    $this->dfpEditTag($tag->id(), $edit);
    $this->assertText(t('The browser size can only contain numbers and the character x.'), 'An error was correctly thrown when invalid characters.');

    // Create a tag with invalid ad size mappings.
    $edit['breakpoints[0][browser_size]'] = $this->dfpGenerateSize();
    $edit['breakpoints[0][ad_sizes]'] = '100y100,200x200';
    $this->dfpEditTag($tag->id(), $edit);
    $this->assertText(t('The ad size(s) can only contain numbers, the character x and commas.'), 'An error was correctly thrown when invalid characters.');
  }

  /**
   * Tests slug display.
   */
  public function testSlug() {
    $this->config('dfp.settings')->set('hide_slug', FALSE)->save();
    $edit = $this->dfpBasicTagEditValues();

    // Create a tag without a slug, display it and ensure the default slug is
    // displayed.
    $edit['slug'] = '';
    $tag = $this->dfpCreateTag($edit);
    $this->drupalGet('<front>');
    $this->assertText('Global DFP slug', 'The default slug is correctly used when no slug exists for an individual tag.');

    // Change the slug to <none> and ensure that no slug is displayed.
    $edit['slug'] = '<none>';
    $this->dfpEditTag($tag->id(), $edit);
    $this->drupalGet('<front>');
    $this->assertNoText('Global DFP slug', 'No slug is appearing when "<none>" is used.');

    // Specify a slug and check that it shows instead of the default slug.
    $edit['slug'] = 'Tag specific slug';
    $this->dfpEditTag($tag->id(), $edit);
    $this->drupalGet('<front>');
    $this->assertText('Tag specific slug');

    // Set the slug to be hidden. Use admin UI and the cache tags added in
    // \Drupal\dfp\View\TagViewBuilder::viewMultiple() are tested.
    $edit = [
      'hide_slug' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/dfp/settings', $edit, t('Save configuration'));
    $this->drupalGet('<front>');
    $this->assertNoText('Tag specific slug');
  }

  /**
   * Tests targeting display.
   */
  public function testTargeting() {
    $edit = $this->dfpBasicTagEditValues();

    // Create a tag with a target with only one value.
    $tag = $this->dfpCreateTag($edit);
    $this->drupalGet('<front>');
    $this->assertPropertySet('Targeting', $edit['targeting[0][target]'], $edit['targeting[0][value]']);

    // Create a tag with a target with multiple values.
    $values = [
      $this->randomMachineName(),
      $this->randomMachineName(),
      $this->randomMachineName(),
    ];
    $edit['targeting[0][target]'] = $this->randomMachineName();
    $edit['targeting[0][value]'] = implode(', ', $values);
    $this->dfpEditTag($tag->id(), $edit);
    $this->drupalGet('<front>');
    $this->assertPropertySet('Targeting', $edit['targeting[0][target]'], implode("','", $values));

    // Create a tag with a target but no value.
    $edit['targeting[0][target]'] = $this->randomMachineName();
    $edit['targeting[0][value]'] = '';
    $this->dfpEditTag($tag->id(), $edit);
    $this->assertText(t('The value cannot be empty if a target exists.'));

    // Create a tag with an empty target, but a value.
    $edit['targeting[0][target]'] = '';
    $edit['targeting[0][value]'] = $this->randomMachineName();
    $this->dfpEditTag($tag->id(), $edit);
    $this->assertText(t('The target cannot be empty if a value exists.'));

    // Create a tag with multiple targets.
    $count = 3;
    for ($i = 0; $i < $count; $i++) {
      $edit['targeting[' . $i . '][target]'] = $this->randomMachineName();
      $edit['targeting[' . $i . '][value]'] = $this->randomMachineName();
      $this->dfpEditTag($tag->id(), $edit);
    }
    $this->drupalGet('<front>');
    for ($i = 0; $i < $count; $i++) {
      $this->assertPropertySet('Targeting', $edit['targeting[' . $i . '][target]'], $edit['targeting[' . $i . '][value]']);
    }

    // Test that target can be removed and does not result in empty values.
    $old_target = $edit['targeting[0][target]'];
    $old_value = $edit['targeting[0][value]'];
    $edit['targeting[0][target]'] = '';
    $edit['targeting[0][value]'] = '';
    $this->dfpEditTag($tag->id(), $edit);
    $this->drupalGet('<front>');
    $this->assertPropertyNotSet('Targeting', $old_target, $old_value);
    $this->assertPropertyNotSet('Targeting', '', '');

    // Create a tag that uses the slot token in a target.
    $edit = $this->dfpBasicTagEditValues();
    $test_slot = $this->randomMachineName();
    $edit['slot'] = $test_slot;
    $edit['targeting[0][target]'] = 'slot';
    $edit['targeting[0][value]'] = '[dfp_tag:slot]';
    $this->dfpCreateTag($edit);
    $this->drupalGet('<front>');
    $this->assertPropertySet('Targeting', 'slot', $test_slot);

    // Create a tag that uses the network ID token in a target.
    $edit = $this->dfpBasicTagEditValues();
    $edit['targeting[0][target]'] = 'network id';
    $edit['targeting[0][value]'] = '[dfp_tag:network_id]';
    $this->dfpCreateTag($edit);
    $this->drupalGet('<front>');
    $this->assertPropertySet('Targeting', 'network id', '12345');
  }

  /**
   * Tests Adsense backfill settings.
   */
  public function testBackfill() {
    $edit = $this->dfpBasicTagEditValues();

    // Create a tag with backfill settings.
    $colors = ['background', 'border', 'link', 'text', 'url'];

    $edit['adsense_backfill[ad_types]'] = 'text_image';
    $edit['adsense_backfill[channel_ids]'] = $this->randomMachineName();
    foreach ($colors as $color) {
      $edit['adsense_backfill[color][' . $color . ']'] = Unicode::strtoupper($this->randomMachineName(6));
    }
    $this->dfpCreateTag($edit);
    $this->drupalGet('<front>');
    $this->assertPropertySet('', 'adsense_ad_types', $edit['adsense_backfill[ad_types]']);
    $this->assertPropertySet('', 'adsense_channel_ids', $edit['adsense_backfill[channel_ids]']);
    foreach ($colors as $color) {
      $this->assertPropertySet('', 'adsense_' . $color . '_color', '#' . $edit['adsense_backfill[color][' . $color . ']']);
    }
  }

}
