<?php

namespace Drupal\localgov_elections;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the elections contest entity type.
 */
class ElectionsContestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view elections contest');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit elections contest', 'administer elections'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete elections contest', 'administer elections'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create elections contest', 'administer elections'], 'OR');
  }

}
