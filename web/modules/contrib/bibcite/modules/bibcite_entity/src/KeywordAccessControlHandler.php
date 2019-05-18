<?php

namespace Drupal\bibcite_entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Keyword entity.
 *
 * @see \Drupal\bibcite_entity\Entity\Keyword.
 */
class KeywordAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\bibcite_entity\Entity\KeywordInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view bibcite_keyword');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit bibcite_keyword');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete bibcite_keyword');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create bibcite_keyword');
  }

}
