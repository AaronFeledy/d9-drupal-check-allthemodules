<?php

namespace Drupal\tally\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'default' widget.
 *
 * @FieldWidget(
 *   id = "tally_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "tally_reference"
 *   }
 * )
 */
class TallyDefaultWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    $options = $this->getOptions($items->getEntity());
    $count = $options ? count($options) - 1 : 0;
    $count_max = 15;
    // Override widget state in parent function to set item count.
    if (!static::getWidgetState($parents, $field_name, $form_state)) {
      $field_state = [
        'items_count' => min($count, $count_max),
        'array_parents' => [],
      ];
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }
    $build = parent::form($items, $form, $form_state, $get_delta);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $item = $this->getItemValues($items, $delta);
    $element['count'] = [
      '#type' => 'number',
      '#default_value' => $item->count,
      '#attributes' => [
        'placeholder' => '0'
      ],
    ];

    $element['label'] = [
      '#type' => 'item',
      '#markup' => $item->label,
    ];

    $element['target_id'] = [
      '#type' => 'hidden',
      '#value' => $item->id,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    unset($elements['add_more']);
    // Remove the 'drag-n-drop reordering' element.
    // @see template_preprocess_field_multiple_value_form()
    $elements['#cardinality_multiple'] = FALSE;
    // Remove the little 'Weight for row n' box.
    foreach (Element::children($elements) as $id) {
      $elements[$id]['_weight']['#access'] = FALSE;
    }

    $elements['#type'] = 'table';
    $elements['#header'] = [];
    $elements['#caption'] = $elements['#title'];
    $elements['#theme_wrappers'][] = 'table';
    return $elements;
  }

  /**
   * Helper function to gather data for a form row.
   *
   * @param FieldItemListInterface $items
   * @param $delta
   * @return object
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getItemValues(FieldItemListInterface $items, $delta) {
    $values = $this->getOptions($items->getEntity());
    while ($delta > 0) {
      $delta--;
      next($values);
    }
    $item = $this->getItemCount($items, key($values));
    return (object) [
      'id' => key($values),
      'count' => $item ? $item->getValue()['count'] : NULL,
      'label' => current($values),
    ];
  }

  /**
   * Fetch a count based on the target id, not delta.
   *
   * @param FieldItemListInterface $items
   * @param $target_id
   * @return bool|\Drupal\Core\TypedData\TypedDataInterface|null
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function getItemCount(FieldItemListInterface $items, $target_id) {
    $ids = array_column($items->getValue(), 'target_id');
    if (($id = array_search($target_id, $ids)) === FALSE) {
      return FALSE;
    }
    return $items->get($id);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$row) {
      $row['count'] = $row['count'] === "" ? NULL : $row['count'];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '_none') {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }
    $items = [
      'target_id' => $element['target_id']['#value'],
      'count' => $element['count']['#value'],
    ];
    $form_state->setValueForElement($element, $items);
  }

}
