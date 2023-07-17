<?php

namespace Drupal\islandora_google_scholar;

use Drupal\node\NodeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Alteration service for type-filtered metatags.
 */
class MetatagAlterer {

  /**
   * Configuration for the module.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Entity type manager interface.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Performs the metatag alteration from configuration.
   *
   * @param array $metatags
   *   The array of metatag definitions for the entity.
   * @param Drupal\node\NodeInterface $entity
   *   The entity metatags are being altered for.
   */
  public function alter(array &$metatags, NodeInterface $entity) {
    $alterations = $this->getAlterationsFor($entity);
    // Case where the type is absent and "purge_if_absent" was true.
    if ($alterations === FALSE) {
      foreach ($metatags as $metatag => $value) {
        if (substr($metatag, 0, 9) == 'citation_') {
          unset($metatags[$metatag]);
        }
      }
    }
    else {
      foreach ($alterations as $metatag => $value) {
        if (empty($value)) {
          unset($metatags[$metatag]);
        }
        else {
          $metatags[$metatag] = $value;
        }
      }
    }
  }

  /**
   * Gets the value of the type field for the given entity.
   *
   * @param Drupal\node\NodeInterface $entity
   *   The entity to check.
   *
   * @return array|bool
   *   The alterations to perform. If we can't get to the point where we would
   *   get a list of alterations for a referenced type, an empty array is
   *   returned. If the field is found on the referenced entity but its value is
   *   not included in the alteration list, and 'purge_if_absent' is TRUE for
   *   the alteration, FALSE will be returned.
   */
  protected function getAlterationsFor(NodeInterface $entity) {
    $config = $this->config->get("islandora_google_scholar.metatag_alterations.{$entity->bundle()}");
    if (!$config) {
      return [];
    }
    $type_info = $config->get();
    if (!$type_info) {
      return [];
    }

    if (!$entity->hasField($type_info['reference_field'])) {
      return [];
    }

    $referenced_entities = $entity->get($type_info['reference_field'])->referencedEntities();
    $reference = reset($referenced_entities);
    if (!$reference) {
      return [];
    }
    if ($reference->hasField($type_info['reference_target'])) {
      $type = $reference->get($type_info['reference_target'])->first()->getString();
      if (isset($type_info['alterations'][$type])) {
        return $type_info['alterations'][$type];
      }
      elseif ($config->get('purge_if_absent')) {
        return FALSE;
      }
    }
    return [];
  }

}
