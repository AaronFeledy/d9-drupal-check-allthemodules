<?php

namespace Drupal\evergreen;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages evergreen plugins.
 */
class EvergreenManager extends DefaultPluginManager {

  /**
   * Constructs a new EvergreenExpiryProviderManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/evergreen/Evergreen',
      $namespaces,
      $module_handler,
      'Drupal\evergreen\EvergreenInterface',
      'Drupal\evergreen\Annotation\Evergreen'
    );

    $this->alterInfo('evergreen_info');
    $this->setCacheBackend($cache_backend, 'evergreen_plugins');
  }

}
