<?php

namespace Drupal\localgov_elections;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an elections candidate entity type.
 */
interface ElectionsCandidateInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the elections candidate creation timestamp.
   *
   * @return int
   *   Creation timestamp of the elections candidate.
   */
  public function getCreatedTime();

  /**
   * Sets the elections candidate creation timestamp.
   *
   * @param int $timestamp
   *   The elections candidate creation timestamp.
   *
   * @return \Drupal\localgov_elections\ElectionsCandidateInterface
   *   The called elections candidate entity.
   */
  public function setCreatedTime($timestamp);

}
