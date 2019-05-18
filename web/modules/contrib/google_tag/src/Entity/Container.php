<?php

namespace Drupal\google_tag\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the container configuration entity.
 *
 * @ConfigEntityType(
 *   id = "google_tag_container",
 *   label = @Translation("Container configuration"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\google_tag\ContainerListBuilder",
 *     "form" = {
 *       "default" = "Drupal\google_tag\Form\ContainerForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "access" = "Drupal\google_tag\ContainerAccessControlHandler"
 *   },
 *   config_prefix = "container",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "container_id",
 *     "path_toggle",
 *     "path_list",
 *     "role_toggle",
 *     "role_list",
 *     "status_toggle",
 *     "status_list",
 *     "data_layer",
 *     "include_classes",
 *     "whitelist_classes",
 *     "blacklist_classes",
 *     "include_environment",
 *     "environment_id",
 *     "environment_token",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/google_tag/add",
 *     "edit-form" = "/admin/structure/google_tag/manage/{google_tag_container}",
 *     "delete-form" = "/admin/structure/google_tag/manage/{google_tag_container}/delete",
 *     "enable" = "/admin/structure/google_tag/manage/{google_tag_container}/enable",
 *     "disable" = "/admin/structure/google_tag/manage/{google_tag_container}/disable",
 *     "collection" = "/admin/structure/google_tag",
 *   }
 * )
 *
 * @todo Add a clone operation.
 * this may not be an option in above annotation
 *     "clone-form" = "/admin/structure/google_tag/manage/{google_tag_container}/clone",
 */
class Container extends ConfigEntityBase implements ConfigEntityInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  public $label;

  /**
   * The weight of the configuration entity.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The Google Tag Manager container id.
   *
   * @var string
   */
  public $container_id;

  /**
   * Whether to include or exclude the listed paths.
   *
   * @var string
   */
  public $path_toggle;

  /**
   * The listed paths.
   *
   * @var string
   */
  public $path_list;

  /**
   * Whether to include or exclude the listed roles.
   *
   * @var string
   */
  public $role_toggle;

  /**
   * The listed roles.
   *
   * @var array
   */
  public $role_list;

  /**
   * Whether to include or exclude the listed statuses.
   *
   * @var string
   */
  public $status_toggle;

  /**
   * The listed statuses.
   *
   * @var string
   */
  public $status_list;

  /**
   * The name of the data layer.
   *
   * @var string
   */
  public $data_layer;

  /**
   * Whether to add the listed classes to the data layer.
   *
   * @var bool
   */
  public $include_classes;

  /**
   * The white-listed classes.
   *
   * @var string
   */
  public $whitelist_classes;

  /**
   * The black-listed classes.
   *
   * @var string
   */
  public $blacklist_classes;

  /**
   * Whether to include the environment items in the applicable snippets.
   *
   * @var bool
   */
  public $include_environment;

  /**
   * The environment ID.
   *
   * @var string
   */
  public $environment_id;

  /**
   * The environment token.
   *
   * @var string
   */
  public $environment_token;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $values = array_diff_key($values, array_flip(['uuid', 'langcode']));
    if (empty($values)) {
      // Initialize entity properties from default container settings.
      $config = \Drupal::config('google_tag.settings');
      foreach ($config->get('_default_container') as $key => $value) {
        $this->$key = $value;
      }
    }
  }

  /**
   * Returns array of JavaScript snippets.
   *
   * @return array
   *   Associative array of snippets keyed by type: script, noscript and
   *   data_layer.
   */
  public function snippets() {
    $snippets = [
      'script' => $this->scriptSnippet(),
      'noscript' => $this->noscriptSnippet(),
      'data_layer' => $this->dataLayerSnippet(),
    ];
    // Allow other modules to alter the snippets.
    \Drupal::moduleHandler()->alter('google_tag_snippets', $snippets);
    return $snippets;
  }

  /**
   * Returns JavaScript script snippet.
   *
   * @return array
   *   The script snippet.
   */
  protected function scriptSnippet() {
    // Gather data.
    $compact = \Drupal::config('google_tag.settings')->get('compact_snippet');
    $container_id = $this->variableClean('container_id');
    $data_layer = $this->variableClean('data_layer');
    $query = $this->environmentQuery();

    // Build script snippet.
    $script = <<<EOS
(function(w,d,s,l,i){

  w[l]=w[l]||[];
  w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
  var f=d.getElementsByTagName(s)[0];
  var j=d.createElement(s);
  var dl=l!='dataLayer'?'&l='+l:'';
  j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl+'$query';
  j.async=true;
  f.parentNode.insertBefore(j,f);

})(window,document,'script','$data_layer','$container_id');
EOS;
    if ($compact) {
      $script = str_replace(["\n", '  '], '', $script);
    }
/*
    $script = <<<EOS
<!-- Google Tag Manager -->
$script
<!-- End Google Tag Manager -->
EOS;
*/
    return $script;
  }

  /**
   * Returns JavaScript noscript snippet.
   *
   * @return array
   *   The noscript snippet.
   */
  protected function noscriptSnippet() {
    // Gather data.
    $compact = \Drupal::config('google_tag.settings')->get('compact_snippet');
    $container_id = $this->variableClean('container_id');
    $query = $this->environmentQuery();

    // Build noscript snippet.
    $noscript = <<<EOS
<noscript aria-hidden="true"><iframe src="https://www.googletagmanager.com/ns.html?id=$container_id$query"
 height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
EOS;
    if ($compact) {
      $noscript = str_replace("\n", '', $noscript);
    }
/*
    $noscript = <<<EOS
<!-- Google Tag Manager -->
$noscript
<!-- End Google Tag Manager -->
EOS;
*/
    return $noscript;
  }

  /**
   * Returns JavaScript data layer snippet or adds items to data layer.
   *
   * @return string|null
   *   The data layer snippet or NULL.
   */
  protected function dataLayerSnippet() {
    // Gather data.
    $data_layer = $this->variableClean('data_layer');
    $whitelist = $this->get('whitelist_classes');
    $blacklist = $this->get('blacklist_classes');

    $classes = [];
    $names = ['whitelist', 'blacklist'];
    foreach ($names as $name) {
      if (empty($$name)) {
        continue;
      }
      // @see https://www.drupal.org/files/issues/add_options_to-2851405-7.patch
      // this suggests to flip order of previous two statements; yet if user
      // enters a new line in textarea, then this change does not eliminate the
      // empty script item. Need to trim "\n" from ends of string.
      $$name = explode("\n", $$name);
      $classes["gtm.$name"] = $$name;
    }

    if ($classes) {
      // Build data layer snippet.
      $script = "var $data_layer = [" . json_encode($classes) . '];';
      return $script;
    }
  }

  /**
   * Returns a query string with the environment parameters.
   *
   * @return string
   *   The query string.
   */
  public function environmentQuery() {
    if (!$this->get('include_environment')) {
      return '';
    }

    // Gather data.
    $environment_id = $this->variableClean('environment_id');
    $environment_token = $this->variableClean('environment_token');

    // Build query string.
    return "&gtm_auth=$environment_token&gtm_preview=$environment_id&gtm_cookies_win=x";
  }

  /**
   * Returns a cleansed variable.
   *
   * @param string $variable
   *   The variable name.
   *
   * @return string
   *   The cleansed variable.
   */
  public function variableClean($variable) {
    return trim(json_encode($this->get($variable)), '"');
  }

  /**
   * Determines whether to insert the snippet on the response.
   *
   * @return bool
   *   TRUE if the conditions are met; FALSE otherwise.
   */
  public function insertSnippet() {
    static $satisfied;

    if (!isset($satisfied)) {
      $debug = \Drupal::config('google_tag.settings')->get('debug_output');
      $id = $this->get('container_id');

      if (empty($id)) {
        // No container ID.
        return FALSE;
      }

      $satisfied = TRUE;
      if (!$this->statusCheck() || !$this->pathCheck() || !$this->roleCheck()) {
        // Omit snippet if any condition is not met.
        $satisfied = FALSE;
      }

      // Allow other modules to alter the insertion criteria.
      \Drupal::moduleHandler()->alter('google_tag_insert', $satisfied);
      $debug ? drupal_set_message(t('after alter @satisfied', ['@satisfied' => $satisfied])) : '';
    }
    return $satisfied;
  }

  /**
   * Determines whether to insert the snippet based on status code settings.
   *
   * @return bool
   *   TRUE if the status conditions are met; FALSE otherwise.
   */
  protected function statusCheck() {
    static $satisfied;

    if (!isset($satisfied)) {
      $debug = \Drupal::config('google_tag.settings')->get('debug_output');
      $toggle = $this->get('status_toggle');
      $statuses = $this->get('status_list');

      if (empty($statuses)) {
        $satisfied = ($toggle == GOOGLE_TAG_EXCLUDE_LISTED);
      }
      else {
        // Get the HTTP response status.
        $request = \Drupal::request();
        $status = '200';
        if ($exception = $request->attributes->get('exception')) {
          $status = $exception->getStatusCode();
        }
        $satisfied = strpos($statuses, (string) $status) !== FALSE;
        $satisfied = ($toggle == GOOGLE_TAG_EXCLUDE_LISTED) ? !$satisfied : $satisfied;
      }
      $debug ? drupal_set_message(t('google_tag')) : '';
      $debug ? drupal_set_message(t('status check @satisfied', ['@satisfied' => $satisfied])) : '';
    }
    return $satisfied;
  }

  /**
   * Determines whether to insert the snippet based on the path settings.
   *
   * @return bool
   *   TRUE if the path conditions are met; FALSE otherwise.
   */
  protected function pathCheck() {
    static $satisfied;

    if (!isset($satisfied)) {
      $debug = \Drupal::config('google_tag.settings')->get('debug_output');
      $toggle = $this->get('path_toggle');
      $paths = Unicode::strtolower($this->get('path_list'));

      if (empty($paths)) {
        $satisfied = ($toggle == GOOGLE_TAG_EXCLUDE_LISTED);
      }
      else {
        $request = \Drupal::request();
        $current_path = \Drupal::service('path.current');
        $alias_manager = \Drupal::service('path.alias_manager');
        $path_matcher = \Drupal::service('path.matcher');
        // @todo Are not some paths case sensitive???
        // Compare the lowercase path alias (if any) and internal path.
        $path = $current_path->getPath($request);
        $path_alias = Unicode::strtolower($alias_manager->getAliasByPath($path));
        $satisfied = $path_matcher->matchPath($path_alias, $paths) || (($path != $path_alias) && $path_matcher->matchPath($path, $paths));
        $satisfied = ($toggle == GOOGLE_TAG_EXCLUDE_LISTED) ? !$satisfied : $satisfied;
      }
      $debug ? drupal_set_message(t('path check @satisfied', ['@satisfied' => $satisfied])) : '';
    }
    return $satisfied;
  }

  /**
   * Determines whether to insert the snippet based on the user role settings.
   *
   * @return bool
   *   TRUE if the role conditions are met; FALSE otherwise.
   */
  protected function roleCheck() {
    static $satisfied;

    if (!isset($satisfied)) {
      $debug = \Drupal::config('google_tag.settings')->get('debug_output');
      $toggle = $this->get('role_toggle');
      $roles = array_filter($this->get('role_list'));

      if (empty($roles)) {
        $satisfied = ($toggle == GOOGLE_TAG_EXCLUDE_LISTED);
      }
      else {
        $satisfied = FALSE;
        // Check user roles against listed roles.
        $satisfied = (bool) array_intersect($roles, \Drupal::currentUser()->getRoles());
        $satisfied = ($toggle == GOOGLE_TAG_EXCLUDE_LISTED) ? !$satisfied : $satisfied;
      }
      $debug ? drupal_set_message(t('role check @satisfied', ['@satisfied' => $satisfied])) : '';
    }
    return $satisfied;
  }

  /**
   * Returns the snippet directory path.
   *
   * @return string
   *   The snippet directory path.
   */
  public function snippetDirectory() {
    return \Drupal::config('google_tag.settings')->get('uri') . "/{$this->id()}";
  }

  /**
   * Returns the snippet URI for a snippet type.
   *
   * @param string $type
   *   The snippet type.
   *
   * @return string
   *   The snippet URI.
   */
  public function snippetURI($type) {
    return $this->snippetDirectory() . "/google_tag.$type.js";
  }

  /**
   * Returns tag array for the snippet type.
   *
   * @param string $type
   *   The snippet type.
   * @param int $weight
   *   The weight of the item.
   *
   * @return array
   *   The tag array.
   */
  public function fileTag($type, $weight) {
    $uri = $this->snippetURI($type);
    $url = file_url_transform_relative(file_create_url($uri));
    $query_string = \Drupal::state()->get('system.css_js_query_string') ?: '0';
    $attachment = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => ['src' => $url . '?' . $query_string, 'defer' => 'true'],
        '#weight' => $weight,
      ],
      "google_tag_{$type}_tag__{$this->id()}",
    ];
    return $attachment;
  }

  /**
   * Returns tag array for the snippet type.
   *
   * @param string $type
   *   The snippet type.
   * @param int $weight
   *   The weight of the item.
   *
   * @return array
   *   The tag array.
   */
  public function inlineTag($type, $weight) {
    $uri = $this->snippetURI($type);
    $url = \Drupal::service('file_system')->realpath($uri);
    $contents = @file_get_contents($url);
    $attachment = $contents ? [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => $contents,
        '#weight' => $weight,
      ],
      "google_tag_{$type}_tag__{$this->id()}",
    ] : [];
    return $attachment;
  }

  /**
   * Returns tag array for the snippet type.
   *
   * @param string $type
   *   (optional) The snippet type.
   * @param int $weight
   *   (optional) The weight of the item.
   *
   * @return array
   *   The tag array.
   */
  public function noscriptTag($type = 'noscript', $weight = -10) {
    // Note: depending on the theme, this may not place the snippet immediately
    // after the body tag but should be close and it can be altered.

    // @see https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!theme.api.php/group/theme_render/8.2.x
    // The markup is passed through \Drupal\Component\Utility\Xss::filterAdmin()
    // which strips known vectors while allowing a permissive list of HTML tags
    // that are not XSS vectors. (e.g., <script> and <style> are not allowed.)
    // As markup, core removes the 'style' attribute from the noscript snippet.
    // With the inline template type, core does not alter the noscript snippet.

    $uri = $this->snippetURI($type);
    $url = \Drupal::service('file_system')->realpath($uri);
    $contents = @file_get_contents($url);
    $attachment = $contents ? [
      "google_tag_{$type}_tag__{$this->id()}" => [
        '#type' => 'inline_template',
        '#template' => $contents,
        '#weight' => $weight,
      ],
    ] : [];
    return $attachment;
  }

}
