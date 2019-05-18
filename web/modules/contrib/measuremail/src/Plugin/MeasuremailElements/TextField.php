<?php

namespace Drupal\measuremail\Plugin\MeasuremailElements;

use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\ConfigurableMeasuremailElementBase;

/**
 * Provides a 'textfield' element.
 *
 * @MeasuremailElements(
 *   id = "textfield",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Textfield.php/class/Textfield",
 *   label = @Translation("Text field"),
 *   description = @Translation("Provides a form element for input of a single-line text."),
 *   category = @Translation("Basic elements"),
 * )
 */
class TextField extends ConfigurableMeasuremailElementBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'id' => '',
      'default_value' => '',
      'required' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->configuration['label'],
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => t('Measuremail field ID'),
      '#description' => t('Same ID as on Measuremail'),
      '#default_value' => $this->configuration['id'],
      '#required' => TRUE,
    ];
    $form['default_value'] = [
      '#type' => 'textfield',
      '#title' => t('Default value'),
      '#default_value' => $this->configuration['default_value'],
    ];
    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => t('Required'),
      '#default_value' => $this->configuration['required'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['label'] = $form_state->getValue('label');
    $this->configuration['id'] = $form_state->getValue('id');
    $this->configuration['default_value'] = $form_state->getValue('default_value');
    $this->configuration['required'] = $form_state->getValue('required');
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $field_configuration = $this->getConfiguration()['data'];
    return [
      '#type' => $this->getPluginId(),
      '#title' => t($field_configuration['label']),
      '#default_value' => t($field_configuration['default_value']),
      '#required' => ($field_configuration['required']) ? TRUE : FALSE,
    ];
  }
}
