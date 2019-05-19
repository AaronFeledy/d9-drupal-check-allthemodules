<?php

/**
 * @file
 * Definition of RealmElementTest.
 */

namespace Drupal\wow_realm\Tests;

use WoW\Core\Service\ServiceHttp;

use WoW\Realm\Entity\RealmStorageController;

use WoW\Realm\Entity\RealmServiceController;

/**
 * Test wow_realm element.
 */
class RealmElementTest extends RealmWebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Form API wow_realm',
      'description' => 'Tests the form API wow_realm element.',
      'group' => 'WoW Realm',
    );
  }

  protected function setUp() {
    parent::setUp('wow_test_realm');
  }

  /**
   * Test that wow_realm element validate correctly user input.
   */
  function testFormRealmTextField() {
    entity_create('wow_realm', array(
      'region' => 'eu',
      'slug' => 'eldrethalas',
      'name' => "Eldre'Thalas",
      'locale' => 'fr_FR',
    ))->save();

    $edit = array();
    $edit['realm'] = "Eldre'Thalas-EU";
    $this->drupalPost('wow-test-realm/validate', $edit, 'Test');
    $this->assertText(t('The realm name is not valid.'), 'Realm name is not valid.', 'WoW Realm');

    $edit = array();
    $edit['realm'] = "Eldre'Thalas [XX]";
    $this->drupalPost('wow-test-realm/validate', $edit, 'Test');
    $this->assertText(t('The realm region does not exist.'), 'Realm region does not exist.', 'WoW Realm');

    $edit = array();
    $edit['realm'] = "Eldrs'Thalas [EU]";
    $this->drupalPost('wow-test-realm/validate', $edit, 'Test');
    $this->assertText(t('The realm name does not exist.'), 'Realm name does not exist.', 'WoW Realm');

    $edit = array();
    $edit['realm'] = "Eldre'Thalas [EU]";
    $this->drupalPost('wow-test-realm/validate', $edit, 'Test');
    $this->assertText(t('Test realm is valid.'), 'Realm is valid.', 'WoW Realm');
  }

}
