<?php

/**
 * @file
 * Definition of RealmStorageControllerStub.
 */

namespace Drupal\wow_realm\Mocks;

use WoW\Realm\Entity\Realm;
use WoW\Realm\Entity\RealmStorageController;

/**
 * Storage controller stub for realms.
 */
class RealmStorageControllerStub extends RealmStorageController {

  public $entities = array();
  public $idsBySlug = array();

  public function __construct() {}

  public function create(array $values = array()) {
    end($this->entities);
    $id = empty($this->entities) ? 1 : key($this->entities) + 1;

    $defaults = array('rid' => $id, 'region' => 'local');
    $entity = new Realm($values + $defaults, 'wow_realm');

    $this->idsBySlug[$entity->region][$entity->slug] = $id;
    return $this->entities[$id] = $entity;
  }

  public function loadIdsBySlug($region, array $slugs = array()) {
    return $this->idsBySlug[$region];
  }

  public function load($ids = array(), $conditions = array()) {
    if (!empty($ids)) {
      return array_intersect_key($this->entities, array_flip($ids));
    }
    else {
      return $this->entities;
    }
  }

  public function save($entity) {
    $id = $entity->rid;
    $is_new = isset($this->entities[$id]);

    $this->entities[$id] = $entity;
    $this->idsBySlug[$entity->region][$entity->slug] = $id;

    return $is_new ? SAVED_NEW : SAVED_UPDATED;
  }

  public function delete($ids) {
    foreach ($ids as $id) {
      $entity = $this->entities[$id];
      unset($this->idsBySlug[$entity->region][$entity->slug]);
      unset($this->entities[$id]);
    }
  }

}
