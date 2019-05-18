<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/26/16
 * Time: 6:59 AM
 */

namespace Drupal\forena;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class FrxDriverPluginManager extends DefaultPluginManager {
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {

    $subdir = 'FrxPlugin/Driver';

    // The name of the interface that plugins should adhere to.
    $plugin_interface = 'Drupal\forena\FrxPlugin\Driver\DriverInterface';

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = 'Drupal\forena\Annotation\FrxDriver';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    // This allows the plugin definitions to be altered by an alter hook. The
    // parameter defines the name of the hook, thus: hook_sandwich_info_alter().
    // In this example, we implement this hook to change the plugin definitions:
    // see plugin_type_example_sandwich_info_alter().
    $this->alterInfo('frx_driver_info');

    // This sets the caching method for our plugin definitions.  Plugin
    // definitions are cached using the provided cache backend.
    $this->setCacheBackend($cache_backend, 'frx_driver_info');
  }
}