<div <?php print drupal_attributes($wrapper_attributes) ?>>
  <?php if ($tag->slug) { ?>
    <div class="slug"><?php print $tag->slug ?></div>
  <?php } ?>
  <script type="text/javascript">
    googletag.cmd.push(function() {
      googletag.display("<?php print $tag->wrapper_id ?>");
    });
  </script>
</div>
