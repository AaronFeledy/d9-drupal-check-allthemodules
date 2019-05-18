<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Entity\ContentEntityTypeInterface;

/**
 * Defines the BotConversation schema handler.
 */
class BotConversationStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);
    $base_table = $entity_type->getBaseTable();
    $indexes = [
      'uid_complete' => [
        'uid',
        'complete',
      ],
    ];
    $schema[$base_table]['indexes'] = array_merge($schema[$base_table]['indexes'], $indexes);
    return $schema;
  }

}
