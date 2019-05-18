<?php

namespace Drupal\content_entity_builder\Plugin\BaseFieldConfig;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * ListStringItemBaseFieldConfig.
 *
 * @BaseFieldConfig(
 *   id = "list_string_base_field_config",
 *   label = @Translation("List (text)"),
 *   description = @Translation("This field stores text values from a list of allowed 'value => label' pairs, i.e. 'US States': IL => Illinois, IA => Iowa, IN => Indiana."),
 *   field_type = "list_string",
 *   category = @Translation("Text")
 * )
 */
class ListStringItemBaseFieldConfig extends ListItemBaseFieldConfigBase {

  /**
   * {@inheritdoc}
   */
  public function buildBaseFieldDefinition() {
    $field_type = $this->getFieldType();
    $label = $this->getLabel();
    $weight = $this->getWeight();
    $default_value = $this->getDefaultValue();
    $allowed_values = $this->configuration['allowed_values'];
    $required = $this->isRequired();
    $description = $this->getDescription();

    $base_field_definition = BaseFieldDefinition::create($field_type)
      ->setLabel($label)
      ->setDescription($description)
      ->setRequired($required)
      ->setDefaultValue($default_value)
      ->setSetting('allowed_values', static::extractAllowedValues($allowed_values))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => $weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $base_field_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $allowed_values = $form_state->getValue('allowed_values');
    $invalid = FALSE;
    $list = explode("\n", $allowed_values);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        if (empty($key) || empty($value)) {
          $invalid = TRUE;
          break;
        }
      }
      else {
        $invalid = TRUE;
        break;
      }
    }

    if (!empty($invalid)) {
      $form_state->setErrorByName("allowed_values", t('Invalid allowed values.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exportCode() {
  $template = <<<Eof

    \$fields['@field_name'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('@label'))
      ->setDescription(t('@description'))
      ->setRequired(@required)
      ->setDefaultValue('@default_value')
      ->setSetting('allowed_values', [
@allowed_values
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => @weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => @weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);  

Eof;

    $allowed_values = "";
    $list = explode("\n", $this->configuration['allowed_values']);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        $allowed_values .= "        '$key' => '$value',
";
      }
    }
	
    $ret = strtr($template, array(
      "@field_name" => $this->getFieldName(),
      "@label" => $this->getLabel(),
      "@description" => $this->getDescription(),
	  "@default_value" => $this->getDefaultValue(),
      "@required" => !empty($this->isRequired()) ? "TRUE" : "FALSE",
      "@weight" => $this->getWeight(),
      "@allowed_values" => $allowed_values,	  
    ));
	
    return $ret;
  }

}
