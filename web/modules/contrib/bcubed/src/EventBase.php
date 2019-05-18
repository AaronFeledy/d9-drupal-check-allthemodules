<?php

namespace Drupal\bcubed;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Provides a base class for Bcubed Event plugins.
 *
 * @see \Drupal\bcubed\Annotation\Event
 * @see \Drupal\bcubed\EventManager
 * @see \Drupal\bcubed\EventInterface
 * @see plugin_api
 */
abstract class EventBase extends PluginBase implements EventInterface {

  /**
   * The name of the provider that owns this event.
   *
   * @var string
   */
  public $provider;

  /**
   * Settings of this instance.
   *
   * @var array
   */
  public $settings = [];

  /**
   * Generated strings of this plugin.
   *
   * @var array
   */
  protected $generatedStrings = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->provider = $this->pluginDefinition['provider'];

    if (empty($configuration['settings'])) {
      $default = $this->defaultConfiguration();
      $configuration['settings'] = $default['settings'];
      $configuration['provider'] = $default['provider'];
    }

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    if (isset($configuration['generated_strings'])) {
      $this->generatedStrings = (array) $configuration['generated_strings'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['provider'],
      'settings' => $this->settings,
      'generated_strings' => $this->generatedStrings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'provider' => $this->pluginDefinition['provider'],
      'settings' => $this->pluginDefinition['settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function bcubedPluginDependencies() {
    return $this->pluginDefinition['bcubed_dependencies'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return NULL;
  }

  /**
   * Gets a generated string.
   */
  protected function getString($key) {
    return isset($this->generatedStrings[$key]) ? $this->generatedStrings[$key] : NULL;
  }

  /**
   * Returns all generated strings.
   */
  public function getStrings() {
    return $this->generatedStrings;
  }

}
