<?php

namespace Drupal\localgov_elections;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\localgov_elections\Entity\ElectionsContest;

/**
 * Defines the access control handler for the elections candidate entity type.
 */
class ElectionsCandidateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view elections candidate');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit elections candidate', 'administer elections'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete elections candidate', 'administer elections'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {

    if ($operation == 'view') {

      $results_fields = [
        'field_votes_won',
        'field_elected',
      ];

      if (in_array($field_definition->getName(), $results_fields)) {

        // Find parent entity
        // FIXME: add field storing parent to avoid this.
        if (!$items) {
          // Allow access to field in principle? (doesn't bother checking individual items if this is neutral)
          return AccessResult::allowed();
        }

        $id = $items ? $items->getEntity()->id() : NULL;
        $permissionCheck = AccessResult::allowedIfHasPermission($account, 'administer elections')->cachePerPermissions();

        if (!$id) {
          return $permissionCheck;
        }

        $contests = \Drupal::entityTypeManager()
          ->getStorage('localgov_elections_contest')
          ->loadByProperties(['field_candidates' => $id]);

        /** @var ElectionsContest $parent */
        $contest = reset($contests);
        if (!$contest) {
          return $permissionCheck;
        }
        $contest_state = $contest->get('moderation_state')->getString();

        return $permissionCheck
        ->orIf(AccessResult::allowedIf($contest_state == 'declared')->addCacheableDependency($contest));
      }

      //// The mail field is hidden from non-admins.
      //if ($field_definition
      //    ->getName() == 'mail') {
      //  return AccessResult::allowedIfHasPermission($account, 'administer comments');
      //}
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create elections candidate', 'administer elections'], 'OR');
  }

}
