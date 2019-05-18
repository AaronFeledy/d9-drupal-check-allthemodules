<?php

namespace Drupal\facets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Facets Processor annotation.
 *
 * @see \Drupal\facets\Processor\ProcessorPluginManager
 * @see plugin_api
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FacetsProcessor extends Plugin {

  /**
   * The processor plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the processor plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The processor description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Class used to retrieve derivative definitions of the url processor.
   *
   * @var string
   */
  public $derivative = '';

  /**
   * The stages this processor will run in, along with their default weights.
   *
   * This is represented as an associative array, mapping one or more of the
   * stage identifiers to the default weight for that stage. For the available
   * stages, see
   * \Drupal\facets\Processor\ProcessorPluginManager::getProcessingStages().
   *
   * @var int[]
   */
  public $stages;

  /**
   * Whether or not this processor is default enabled for new facets.
   *
   * @var bool
   */
  // @codingStandardsIgnoreStart
  public $default_enabled;
  // @codingStandardsIgnoreEnd

}
