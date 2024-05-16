<?php

namespace Drupal\localgov_elections;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts parameters for upcasting database record IDs to full std objects.
 *
 * @DCG
 * To use this converter specify parameter type in a relevant route as follows:
 * @code
 * localgov_elections.localgov_elections_party_short_name_parameter_converter:
 *   path: example/{record}
 *   defaults:
 *     _controller: '\Drupal\localgov_elections\Controller\LocalgovElectionsController::build'
 *   requirements:
 *     _access: 'TRUE'
 *   options:
 *     parameters:
 *       record:
 *        type: localgov_elections_party_short_name
 * @endcode
 *
 * Note that for entities you can make use of existing parameter converter
 * provided by Drupal core.
 * @see \Drupal\Core\ParamConverter\EntityConverter
 */
class PartyShortNameParamConverter implements ParamConverterInterface {

  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new PartyShortNameParamConverter.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The default database connection.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // Return NULL if record not found to trigger 404 HTTP error.
    $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery();
    $query->accessCheck(TRUE);
    $tids = $query->condition('field_short_name', $value)->execute();

    if ($tids) {
      return $this->entityTypeManager->getStorage('taxonomy_term')->load(reset($tids));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] == 'localgov_elections_party_short_name';
  }

}
