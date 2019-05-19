<?php

namespace Drupal\sample_data;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager service for data generator plugins.
 */
class DataGeneratorPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/SampleData';

    $plugin_interface = 'Drupal\sample_data\SampleDataGeneratorInterface';

    $plugin_definition_annotation_name = 'Drupal\sample_data\Annotation\SampleDataGenerator';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('sample_data_generator_info');

    $this->setCacheBackend($cache_backend, 'sample_data_generator_info');
  }

  /**
   * Load plugin definitions supporting a data type.
   *
   * @param string $data_type
   *   The supported data type being searched for.
   *
   * @return mixed[]
   *   An associative array of plugin definitions keyed by the plugin ID. Only
   *   plugins supporting the requested data type are returned, and an empty
   *   array is returned if no matching plugin definitions were found.
   */
  public function getDefinitionsByDataType($data_type) {
    $all_plugins = $this->getDefinitions();

    $matches = [];
    if ($all_plugins) {
      foreach ($all_plugins as $id => $definition) {
        if (isset($definition->data_type) && $definition->data_type == $data_type) {
          $matches[$id] = $definition;
        }
      }
    }

    return $matches;
  }

}
