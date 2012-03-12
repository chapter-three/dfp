<div <?php print drupal_attributes($placeholder_attributes) ?>>
  <script type="text/javascript">
    googletag.cmd.push(function() {
      googletag.display("<?php print $tag->placeholder_id ?>");
    });
  </script>
</div>
