<?php

namespace Drupal\ji_commerce_taxes\Plugin\Commerce\TaxType;

use Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeBase;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\ji_quickbooks\JIQuickBooksSupport;

/**
 * Provides the QuickBooks tax type.
 *
 * @CommerceTaxType(
 *   id = "quickbookstax",
 *   label = "QuickBooks Tax",
 * )
 */
class QuickBooksTax extends LocalTaxTypeBase {

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * Constructs a new Custom object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\commerce_tax\Resolver\ChainTaxRateResolverInterface $chain_rate_resolver
   *   The chain tax rate resolver.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, RounderInterface $rounder, ChainTaxRateResolverInterface $chain_rate_resolver, UuidInterface $uuid_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $event_dispatcher, $rounder, $chain_rate_resolver);

    $this->uuidGenerator = $uuid_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'), $container->get('event_dispatcher'), $container->get('commerce_price.rounder'), $container->get('ji_commerce_taxes.chain_tax_rate_resolver'), $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_label' => 'tax',
      'tax_type' => 'single',
      'round' => FALSE,
      'display_inclusive' => FALSE,
      'rate' => [],
      'rates' => [],
      'territories' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);

    foreach ($this->configuration['rate'] as &$rate) {
      if (isset($rate['amount'])) {
        // The 'amount' key was renamed to 'percentage' in 2.0-rc2.
        $rate['percentage'] = $rate['amount'];
        unset($rate['amount']);
      }
    }

    foreach ($this->configuration['rates'] as &$rate) {
      if (isset($rate['amount'])) {
        // The 'amount' key was renamed to 'percentage' in 2.0-rc2.
        $rate['percentage'] = $rate['amount'];
        unset($rate['amount']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Clear the cache on each page load.
    // Save \Drupal::cache()->delete(JIQuickBooksSupport::$ji_quickbooks_tax_agencies);.
    $error = JIQuickBooksSupport::taxAgenciesCache();
    if ($error === TRUE) {
      \Drupal::messenger()->addError($this->t('Failed trying to add Tax Agencies to the cache.'), FALSE);
      return NULL;
    }

    $wrapper_id = Html::getUniqueId('tax-type-ajax-wrapper');
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['display_label'] = [
      '#type' => 'select',
      '#title' => t('Display label'),
      '#description' => t('Used to identify the applied tax in order summaries.'),
      '#options' => $this->getDisplayLabels(),
      '#default_value' => $this->configuration['display_label'],
    ];

    // Returns value from Ajax.
    $tax_type_value = NULL;
    if (!isset($tax_type_value)) {
      $tax_type_value = $form_state->get('tax_type');
      if (!isset($tax_type_value)) {
        // Loads what's in configuration as a default.
        $tax_type_value = $this->configuration['tax_type'];
      }
    }

    $form['tax_type'] = [
      '#type' => 'radios',
      '#title' => t('New sales tax rate'),
      '#options' => [
        'single' => 'Single tax rate',
        'combined' => 'Combined tax rate',
      ],
      '#default_value' => $tax_type_value,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxCallback'],
        'wrapper' => $wrapper_id,
      ],
      '#disabled' => TRUE,
    ];

    switch ($tax_type_value) {
      case 'single':
        // Ajax callbacks need rates and territories to be in form state.
        if (!$form_state->get('ji_tax_form_single')) {
          // Initialize empty rows in case there's no data yet.
          $rate = $this->configuration['rate'] ?: [NULL];
          $territories = $this->configuration['territories'] ?: [NULL];

          $form_state->set('rate', $rate);
          $form_state->set('territories', $territories);
          $form_state->set('ji_tax_form_single', TRUE);
        }

        $this->loadSingleTax($form, $form_state);
        break;

      case 'combined':
        // Ajax callbacks need rates and territories to be in form state.
        if (!$form_state->get('ji_tax_form_combined')) {
          // Initialize empty rows in case there's no data yet.
          $rates = $this->configuration['rates'] ?: [NULL];
          $territories = $this->configuration['territories'] ?: [NULL];

          $form_state->set('rates', $rates);
          $form_state->set('territories', $territories);
          $form_state->set('ji_tax_form_combined', TRUE);
        }

        $this->loadCombinedTax($form, $form_state, $wrapper_id);

        $form['rates'][] = [
          'add_rate' => [
            '#type' => 'submit',
            '#value' => $this->t('Add rate'),
            '#submit' => [[get_class($this), 'addRateSubmit']],
            '#limit_validation_errors' => [],
            '#ajax' => [
              'callback' => [get_class($this), 'ajaxCallback'],
              'wrapper' => $wrapper_id,
            ],
          ],
          '#disabled' => TRUE,
        ];
        break;
    }

    $form['territories'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Territory'),
        $this->t('Operations'),
      ],
      '#input' => FALSE,
      '#prefix' => '<p>' . $this->t('The tax type will be used if both the customer and the store belong to one of the territories.') . '</p>',
    ];
    foreach ($form_state->get('territories') as $index => $territory) {
      $territory_form = &$form['territories'][$index];
      $territory_form['territory'] = [
        '#type' => 'address_zone_territory',
        '#default_value' => $territory,
        '#required' => TRUE,
      ];
      $territory_form['remove'] = [
        '#type' => 'submit',
        '#name' => 'remove_territory' . $index,
        '#value' => $this->t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => [[get_class($this), 'removeTerritorySubmit']],
        '#territory_index' => $index,
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxCallback'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }
    $form['territories'][] = [
      'add_territory' => [
        '#type' => 'submit',
        '#value' => $this->t('Add territory'),
        '#submit' => [[get_class($this), 'addTerritorySubmit']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxCallback'],
          'wrapper' => $wrapper_id,
        ],
      ],
    ];

    return $form;
  }

  /**
   * Loads a single QuickBooks tax rate.
   */
  public function loadSingleTax(array &$form, FormStateInterface &$form_state) {
    $form['rate'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Tax name'),
        $this->t('Agency name'),
        $this->t('Percentage'),
      ],
      '#input' => FALSE,
    ];

    foreach ($form_state->get('rate') as $index => $rate) {
      $rate_form = &$form['rate'][$index];
      $rate_form['rate']['id'] = [
        '#type' => 'value',
        '#value' => $rate ? $rate['id'] : $this->uuidGenerator->generate(),
      ];
      $rate_form['rate']['tax_name'] = [
        '#type' => 'textfield',
        '#attributes' => [
          'placeholder' => 'Tax name',
          'autofocus' => TRUE,
        ],
        '#default_value' => $rate ? $rate['tax_name'] : '',
        '#maxlength' => 255,
        '#required' => TRUE,
        '#disabled' => TRUE,
      ];
      $rate_form['agency_name'] = [
        '#type' => 'textfield',
        '#attributes' => [
          'placeholder' => 'Agency name',
        ],
        '#default_value' => $rate ? $rate['agency_name'] : '',
        '#maxlength' => 255,
        '#required' => TRUE,
        '#disabled' => TRUE,
      ];
      $rate_form['percentage'] = [
        '#type' => 'commerce_number',
        '#default_value' => $rate ? $rate['percentage'] * 100 : 0,
        '#field_suffix' => $this->t('%'),
        '#min' => 0,
        '#max' => 100,
        '#disabled' => TRUE,
      ];
    }
  }

  /**
   * Loads combined QuickBooks tax types.
   */
  public function loadCombinedTax(array &$form, FormStateInterface &$form_state, $wrapper_id) {
    $form['tax_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tax name'),
      '#attributes' => [
        'placeholder' => 'Tax name',
        'autofocus' => TRUE,
      ],
      '#default_value' => (isset($form_state->get('rates')[0]['tax_name'])) ? $form_state->get('rates')[0]['tax_name'] : '',
      '#maxlength' => 255,
      '#required' => TRUE,
      '#disabled' => TRUE,
    ];

    $form['rates'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Component name'),
        $this->t('Agency name'),
        $this->t('Percentage'),
        $this->t('Operations'),
      ],
      '#input' => FALSE,
    ];

    foreach ($form_state->get('rates') as $index => $rate) {
      $rate_form = &$form['rates'][$index];
      $rate_form['rate']['id'] = [
        '#type' => 'value',
        '#value' => $rate ? $rate['id'] : $this->uuidGenerator->generate(),
      ];
      $rate_form['rate']['component_name'] = [
        '#type' => 'textfield',
        '#attributes' => [
          'placeholder' => 'Component name',
          'autofocus' => TRUE,
        ],
        '#default_value' => $rate ? $rate['component_name'] : '',
        '#maxlength' => 255,
        '#required' => TRUE,
        '#disabled' => TRUE,
      ];
      $rate_form['agency_name'] = [
        '#type' => 'textfield',
        '#attributes' => [
          'placeholder' => 'Agency name',
        ],
        '#default_value' => $rate ? $rate['agency_name'] : '',
        '#maxlength' => 255,
        '#required' => TRUE,
        '#autocomplete_route_name' => 'ji_commerce_taxes.agency_name.autocomplete',
        '#disabled' => TRUE,
      ];
      $rate_form['percentage'] = [
        '#type' => 'commerce_number',
        '#default_value' => $rate ? $rate['percentage'] * 100 : 0,
        '#field_suffix' => $this->t('%'),
        '#min' => 0,
        '#max' => 100,
        '#disabled' => TRUE,
      ];
      $rate_form['remove'] = [
        '#type' => 'submit',
        '#name' => 'remove_rate' . $index,
        '#value' => $this->t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => [[get_class($this), 'removeRateSubmit']],
        '#rate_index' => $index,
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxCallback'],
          'wrapper' => $wrapper_id,
        ],
        '#disabled' => TRUE,
      ];
    }
  }

  /**
   * Ajax callback for tax rate and zone territory operations.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form['configuration'];
  }

  /**
   * Submit callback for adding a new rate.
   */
  public static function addRateSubmit(array $form, FormStateInterface $form_state) {
    $rates = $form_state->get('rates');
    $rates[] = [];
    $form_state->set('rates', $rates);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for removing a rate.
   */
  public static function removeRateSubmit(array $form, FormStateInterface $form_state) {
    $rates = $form_state->get('rates');
    $index = $form_state->getTriggeringElement()['#rate_index'];
    if (count($rates[$index])) {
      $form_state->set('tax_type', $rates[$index]['tax_type']);
    }
    unset($rates[$index]);
    $form_state->set('rates', $rates);

    $form_state->setRebuild();
  }

  /**
   * Submit callback for adding a new territory.
   */
  public static function addTerritorySubmit(array $form, FormStateInterface $form_state) {
    $territories = $form_state->get('territories');
    $territories[] = [];
    $form_state->set('territories', $territories);

    $rates = $form_state->get('rates');
    if ($rates) {
      $form_state->set('tax_type', $rates[0]['tax_type']);
    }

    $form_state->setRebuild();
  }

  /**
   * Submit callback for removing a territory.
   */
  public static function removeTerritorySubmit(array $form, FormStateInterface $form_state) {
    $territories = $form_state->get('territories');
    $index = $form_state->getTriggeringElement()['#territory_index'];
    unset($territories[$index]);
    $form_state->set('territories', $territories);

    $rates = $form_state->get('rates');
    if ($rates) {
      $form_state->set('tax_type', $rates[0]['tax_type']);
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    if (isset($values['rates'])) {
      // Filter out the button rows.
      $values['rates'] = array_filter($values['rates'], function ($rate) {
        return !empty($rate) && !isset($rate['add_rate']);
      });

      if (empty($values['rates'])) {
        $form_state->setError($form['rates'], $this->t('Please add at least one rate.'));
      }
    }

    if (isset($values['rate'])) {
      // Filter out the button rows.
      $values['rate'] = array_filter($values['rate'], function ($rate) {
        return !empty($rate) && !isset($rate['add_rate']);
      });

      if (empty($values['rate'])) {
        $form_state->setError($form['rate'], $this->t('Please add at least one rate.'));
      }
    }

    $values['territories'] = array_filter($values['territories'], function ($territory) {
      return !empty($territory) && !isset($territory['add_territory']);
    });

    $form_state->setValue($form['#parents'], $values);

    if (empty($values['territories'])) {
      $form_state->setError($form['territories'], $this->t('Please add at least one territory.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['display_label'] = $values['display_label'];
      $this->configuration['tax_type'] = $values['tax_type'];

      // Sooner or later we'll build it so new QBO rates can be added
      // from the interface.
      $stop = TRUE;
      if ($stop) {
        $this->configuration['rate'] = [];
        if (isset($values['rate'])) {
          foreach (array_filter($values['rate']) as $rate) {
            $this->configuration['rate'][] = [
              'id' => $rate['rate']['id'],
              'tax_name' => $rate['rate']['tax_name'],
              'agency_name' => $rate['agency_name'],
              'percentage' => (string) ($rate['percentage'] / 100),
            ];
          }
        }

        $this->configuration['rates'] = [];
        if (isset($values['rates'])) {
          $filtered_array = array_filter($values['rates']);
          foreach ($filtered_array as $rate) {
            $this->configuration['rates'][] = [
              'id' => $rate['rate']['id'],
              'tax_type' => $values['tax_type'],
              'tax_name' => $values['tax_name'],
              'component_name' => $rate['rate']['component_name'],
              'agency_name' => $rate['agency_name'],
              'percentage' => (string) ($rate['percentage'] / 100),
            ];
          }
        }
      }

      $this->configuration['territories'] = [];
      foreach (array_filter($values['territories']) as $territory) {
        $this->configuration['territories'][] = $territory['territory'];
      }
    }
  }

  /**
   * Gets the available display labels.
   *
   * @return array
   *   The display labels, keyed by machine name.
   */
  protected function getDisplayLabels() {
    return [
      'tax' => $this->t('Tax'),
      'vat' => $this->t('VAT'),
      // Australia, New Zealand, Singapore, Hong Kong, India, Malaysia.
      'gst' => $this->t('GST'),
      // Japan.
      'consumption_tax' => $this->t('Consumption tax'),
    ];
  }

  /**
   * Gets the configured display label.
   *
   * @return string
   *   The configured display label.
   */
  protected function getDisplayLabel() {
    $display_labels = $this->getDisplayLabels();
    $display_label_id = $this->configuration['display_label'];
    if (isset($display_labels[$display_label_id])) {
      $display_label = $display_labels[$display_label_id];
    }
    else {
      $display_label = reset($display_labels);
    }
    return $display_label;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRound() {
    return $this->configuration['round'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildZones() {
    $configuration = $this->getConfiguration();
    if ($configuration['tax_type'] === 'single') {
      $rates = $this->configuration['rate'];
    }
    else {
      $rates = $this->configuration['rates'];
    }

    $percent = 0;
    // The plugin doesn't support defining multiple percentages with own
    // start/end dates for UX reasons, so a start date is invented here.
    foreach ($rates as &$rate) {
      $rate['percentages'][] = [
        'number' => $rate['percentage'],
        'start_date' => '2000-01-01',
      ];
      unset($rate['percentage']);

      // We changed the schema and now TaxRate.php wants 'label'
      // back. Here ya go...
      $rate['label'] = $rate['tax_name'];

      // Add each percentage or row together.
      $percent += $rate['percentages'][0]['number'];
    }

    // The first defined rate is assumed to be the default.
    $rates[0]['default'] = TRUE;
    $rates[0]['percentages'][0]['number'] = (string) $percent;

    $zones = [];
    $zones['default'] = new TaxZone([
      'id' => 'default',
      'label' => 'Default',
      'display_label' => $this->getDisplayLabel(),
      'territories' => $this->configuration['territories'],
      'rates' => $rates,
    ]);

    return $zones;
  }

}
