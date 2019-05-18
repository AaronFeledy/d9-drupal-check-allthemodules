<?php

/**
 * @file
 * Contains Drupal\file_chooser_field\Plugins\Example.
 */

namespace Drupal\file_chooser_field\Plugins;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file_chooser_field\Plugins;

/**
 * File Chooser Field plugin example.
 */
class Example extends Plugins\FileChooserFieldPlugin {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('Example');
  }

  /**
   * {@inheritdoc}
   */
  public function cssClass() {
    return 'example-picker';
  }

  /**
   * {@inheritdoc}
   */
  public function attributes($info) {
    return [
      'plugin'       => get_class($this), // Required
      'cardinality'  => $info['cardinality'],
      'description'  => strip_tags($info['description']),
      'max-filesize' => $info['upload_validators']['file_validate_size'][0],
      'multiselect'  => $info['multiselect'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function assets($config) {
    return [
      'js' => [
        '/js/file_chooser_field.example.js' => []
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm($config) {

    $form['example_client_id'] = [
      '#title'         => $this->t('Example variable'),
      '#type'          => 'textfield',
      '#default_value' => $config->get('example_client_id'),
      '#description'   => $this->t('Description of the configraiont option.'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm($config, $form_state) {
    $config->set('example_client_id', $form_state->getValue('example_client_id'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function downloadFile($element, $destination, $url) {
    $local_file = '';
    list($file_url, $orignal_name) = explode('@@@', $url);
    $local_file = system_retrieve_file($file_url, $destination  . '/' . $orignal_name);
    return $local_file;
  }

}
