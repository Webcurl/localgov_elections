<?php

namespace Drupal\localgov_elections_import\Plugin\migrate\process;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an election_party_lookup plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: election_party_lookup
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "election_party_lookup"
 * )
 */
class ElectionPartyLookup extends EntityGenerate {

  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration = NULL) {

    // Pre-set configuration related to election party taxonomy.
    $configuration['bundle'] = 'localgov_elections_party';
    $configuration['bundle_key'] = 'vid';
    $configuration['value_key'] = 'name';
    $configuration['entity_type'] = 'taxonomy_term';

    return parent::create($container, $configuration, $pluginId, $pluginDefinition, $migration);
  }

  /**
   * Override entity_lookup's query to match both title and alias fields.
   */
  protected function query($value) {

    $query = $this->entityTypeManager->getStorage($this->lookupEntityType)
      ->getQuery();

    $conditions =  $query->orConditionGroup()
      ->condition('name', $value)
      ->condition('field_aliases', $value);

    $query
      ->accessCheck($this->accessCheck)
      ->condition($conditions)
      ->condition($this->lookupBundleKey, $this->lookupBundle);

    $results = $query->execute();

    if (empty($results)) {
      return NULL;
    }

    return reset($results);
  }

}
