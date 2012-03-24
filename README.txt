-- Information --
The DFP module integrates Google Publisher Tags (GPT) as supported by the new Doubleclick for Publishers (DFP). This module evolved from the DART module in order to support Google's major overhaul of how Ad Tags work.

-- Requirements & Enhancements --
Some of the information required by this module must be obtained from Doubleclick. See doubleclick.com for more details.

* ctools
* Drupal version >= 7.13. The "backfill ad" form did not appear properly until this commit: http://drupalcode.org/project/drupal.git/commit/bb3f7e9

-- Usage --
You can create DFP tags and display them either as a block or by using the dart_tag($machinename); function in your code.

The new Google Publisher Tags morphed the concept of a site/zone into a 5-teired hierarchical string known as the AdUnitName. A typical ad unit name might be sports/yankees.

The new Google Publisher Tags also introduce a concept of targeting to replace the old key|value pairs used by the old DART tags. Targets have a "target" and a "value" where value can be a single string or a comma-separated list.

- Global Targeting -
Use the global dfp settings form to set targeting values that should be applied to all ad tags on all pages.

- Tag-Specific Targeting -
Each tag can include individual targeting values that will apply only to the given tag.

The DFP module introduces several tokens that can be used to create more dynamic targetng and/or AdUnitNames. These tokens include:
* slot - the current tag's slot name (ex. topbanner)
* network_id - the id provided by DoubleClick (usually a 4-10 digit number)
* url_parts - this allows users to include n parts from the current URL (ex. from mysite.com/foo/bar/baz, you can include foo/bar)
* ad_categories - this one is a bit complicated: The DFP module creates a new vocabulary called dfp_ad_categories and adds some admin settings to allow users to attach this vocab to taxonomy terms in other vocabs (thats right folks, we're tagging tags here). We do this so that taxonomy terms can be grouped separately for ad targeting purposes thus separating the responsibilities of content creators and ad trafficers. A content creater will nt need to consider advertising when creating content and an ad trafficer will never need to edit content directly. For example, lets say with have vocab called "animals" => (dogs, cats, hamsters, elephants, giraffes, chickens, sheep). Now lets say that you want to target your ads to "pets", "farm animals" and "zoo animals." In this case, you can edit the terms in the "animals" vocab and assign each of them an DFP Ad Category. Basically this feature allows you to completely separate the duties of your editorial folks and your ad trafficking team. If you dont bother with this, then your terms will be added as-is. One final note: there is no longer an issue with length (or spaces) in your ad tags like there was with old DART tags so we use the full term name.

-- Hooks --
For more complex implementations module developers and themers can take advantage of these hooks to customize the dfp settings on each page request:

* hook_dfp_tag_load_alter(&$tag) - this is called immediately after a tag is loaded
* hook_dfp_tag_alter(&$tag) - this after a tag is loaded and has been processed
* hook_dfp_global_targeting_alter(&$targeting) - this allows other modules to add global targeting values
* hook_dfp_short_tag_keyvals_alter(&$keyvals) - alter shorttags which are tags used by DFP when javascript is unavailable and they look like the old DART tags

