<?php

namespace  Drupal\encrypt\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a EncryptionMethod annotation object.
 *
 * @ingroup encrypt
 *
 * @Annotation
 */
class EncryptionMethod extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the encryption method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * The description shown to users.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description = '';

  /**
   * Define key type(s) this encryption method should be restricted to.
   *
   * Return an array of KeyType plugin IDs that restrict the allowed key types
   * for usage with this encryption method.
   */
  public $key_type = [];

  /**
   * Define if the encryption method can also decrypt.
   *
   * In some scenario the key linked to the encryption method may not be able
   * to decrypt, i.e. for asymmetrical encryption methods, where the key is a
   * public key.
   *
   * @var bool
   */
  public $can_decrypt = TRUE;

}
