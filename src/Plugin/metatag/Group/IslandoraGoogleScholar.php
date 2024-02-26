<?php

namespace Drupal\islandora_google_scholar\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * Google scholar group for Islandora; provides PDF URLs.
 *
 * @MetatagGroup(
 *   id = "islandora_google_scholar",
 *   label = @Translation("Islandora Google Scholar"),
 *   description = @Translation("Metatags specific to the Islandora implementation of Google Scholar."),
 *   weight = 2
 * )
 */
class IslandoraGoogleScholar extends GroupBase {
  // No-op; just a standard group.
}
