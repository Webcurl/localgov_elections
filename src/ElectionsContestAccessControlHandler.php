<?php

namespace Drupal\localgov_elections;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the elections contest entity type.
 */
class ElectionsContestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {


    $contest_state = $entity->get('moderation_state')->getString();

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf($contest_state != "draft")
          ->andIf(AccessResult::allowedIfHasPermission($account, 'view elections contest'))
          ->orIf(AccessResult::allowedIfHasPermission($account, 'administer elections'));

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
   * Prevent access to certain fields before contest result declared.
   *
   * @param string $operation
   * @param FieldDefinitionInterface $field_definition
   * @param AccountInterface $account
   * @param FieldItemListInterface|null $items
   * @return AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultInterface|\Drupal\Core\Access\AccessResultNeutral|\Drupal\Core\Access\AccessResultReasonInterface
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL)
  {

    if (!$items) {
      // Allow access to field in principle. (doesn't bother checking individual items if this is neutral)
      return AccessResult::allowed();
    }

    $results_fields = [
      'field_turnout',
      'field_turnout_percentage',
      'field_rejected_papers'
    ];

    if (in_array($field_definition->getName(), $results_fields)) {

      $contest = $items->getEntity();
      $contest_state = $contest->get('moderation_state')->getString();
      $permissionCheck = AccessResult::allowedIfHasPermission($account, 'administer elections')
        ->cachePerPermissions();

      return $permissionCheck
        ->orIf(
          AccessResult::allowedIf($contest_state == 'declared')->addCacheableDependency($contest)
        );
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create elections contest', 'administer elections'], 'OR');
  }

}
