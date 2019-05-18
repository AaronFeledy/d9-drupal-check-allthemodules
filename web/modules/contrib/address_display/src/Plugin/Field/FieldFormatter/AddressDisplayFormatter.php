<?php

namespace Drupal\address_display\Plugin\Field\FieldFormatter;

use Drupal\address\Plugin\Field\FieldFormatter\AddressPlainFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Address Display' formatter.
 *
 * @FieldFormatter(
 *   id = "address_display_formatter",
 *   label = @Translation("Address Display"),
 *   field_types = {
 *     "address"
 *   }
 * )
 */
class AddressDisplayFormatter extends AddressPlainFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'address_display' => [
        'organization' => [
          'display' => TRUE,
          'glue' => '',
          'weight' => -1,
        ],
        'address_line1' => [
          'display' => TRUE,
          'glue' => '',
          'weight' => 0,
        ],
        'address_line2' => [
          'display' => TRUE,
          'glue' => ',',
          'weight' => 1,
        ],
        'locality' => [
          'display' => TRUE,
          'glue' => ',',
          'weight' => 2,
        ],
        'postal_code' => [
          'display' => TRUE,
          'glue' => '',
          'weight' => 3,
        ],
        'country_code' => [
          'display' => TRUE,
          'glue' => '',
          'weight' => 4,
        ],
        'langcode' => [
          'display' => FALSE,
          'glue' => ',',
          'weight' => 100,
        ],
        'administrative_area' => [
          'display' => FALSE,
          'glue' => ',',
          'weight' => 100,
        ],
        'dependent_locality' => [
          'display' => FALSE,
          'glue' => ',',
          'weight' => 100,
        ],
        'sorting_code' => [
          'display' => FALSE,
          'glue' => ',',
          'weight' => 100,
        ],
        'given_name' => [
          'display' => TRUE,
          'glue' => '',
          'weight' => 100,
        ],
        'family_name' => [
          'display' => TRUE,
          'glue' => ',',
          'weight' => 100,
        ],
      ],
    ];

    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form = parent::settingsForm($form, $form_state);

    $group_class = 'group-order-weight';
    $items = $this->getSetting('address_display');

    // Build table.
    $form['address_display'] = [
      '#type' => 'table',
      '#caption' => $this->t('Address display'),
      '#header' => [
        $this->t('Label'),
        $this->t('Display'),
        $this->t('Glue'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No items.'),
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $group_class,
        ],
      ],
    ];

    // Build rows.
    foreach ($items as $key => $value) {
      $form['address_display'][$key]['#attributes']['class'][] = 'draggable';
      $form['address_display'][$key]['#weight'] = $value['weight'];

      // Label col.
      $form['address_display'][$key]['label'] = [
        '#plain_text' => $key,
      ];

      // ID col.
      $form['address_display'][$key]['display'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display'),
        '#default_value' => $value['display'],
      ];

      // Glue col.
      $form['address_display'][$key]['glue'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Glue'),
        '#default_value' => $value['glue'],
      ];

      // Weight col.
      $form['address_display'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $key]),
        '#title_display' => 'invisible',
        '#default_value' => $value['weight'],
        '#attributes' => ['class' => [$group_class]],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $config = $this->getSetting('address_display');
    $summary = [];
    $display = [];
    foreach ($config as $key => $config_item) {
      if ($config_item['display']) {
        $display[] = $key;
      }
    }

    if (!empty($display)) {
      $summary[] = $this->t('Display: @elements', ['@elements' => implode(', ', $display)]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'container',
        '#children' => $this->prepareAddressDisplay($item->toArray()),
      ];
    }

    return $elements;
  }

  /**
   * Prepare render array with address components.
   *
   * @param array $item
   *   Address values.
   *
   * @return array
   *   Render array.
   */
  private function prepareAddressDisplay(array $item) {
    $config = $this->getSetting('address_display');
    $countries = $this->countryRepository->getList();

    $elements = [];

    foreach ($config as $key => $config_item) {
      if ($config_item['display'] && isset($item[$key])) {
        if ($key == 'country_code') {
          $item[$key] = $countries[$item[$key]];
        }

        $elements[$key] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => [
              'address-display-element',
              str_replace('_', '-', $key) . '-element',
            ],
          ],
          '#value' => $item[$key] . $config_item['glue'],
        ];
      }
    }
    return $elements;
  }

}
