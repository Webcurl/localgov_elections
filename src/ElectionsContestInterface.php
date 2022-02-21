<?php

namespace Drupal\localgov_elections;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an elections contest entity type.
 */
interface ElectionsContestInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the elections contest creation timestamp.
   *
   * @return int
   *   Creation timestamp of the elections contest.
   */
  public function getCreatedTime();

  /**
   * Sets the elections contest creation timestamp.
   *
   * @param int $timestamp
   *   The elections contest creation timestamp.
   *
   * @return \Drupal\localgov_elections\ElectionsContestInterface
   *   The called elections contest entity.
   */
  public function setCreatedTime($timestamp);

}
