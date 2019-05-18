<?php

namespace Drupal\plus\Http;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Site\Settings;
use GuzzleHttp\HandlerStack;

/**
 * Helper class to construct a HTTP client with Drupal specific config.
 */
class ClientFactory {

  /**
   * The handler stack.
   *
   * @var \GuzzleHttp\HandlerStack
   */
  protected $stack;

  /**
   * Constructs a new ClientFactory instance.
   *
   * @param \GuzzleHttp\HandlerStack $stack
   *   The handler stack.
   */
  public function __construct(HandlerStack $stack) {
    $this->stack = $stack;
  }

  /**
   * Constructs a new client object from some configuration.
   *
   * @param array $config
   *   The config for the client.
   *
   * @return \Drupal\plus\Http\Client
   *   The HTTP client.
   */
  public function fromOptions(array $config = []) {
    static $default_config;
    if ($default_config === NULL) {
      $default_config = [
        // Security consideration: we must not use the certificate authority
        // file shipped with Guzzle because it can easily get outdated if a
        // certificate authority is hacked. Instead, we rely on the certificate
        // authority file provided by the operating system which is more likely
        // going to be updated in a timely fashion. This overrides the default
        // path to the pem file bundled with Guzzle.
        'verify' => TRUE,
        'timeout' => 30,
        'handler' => $this->stack,
        // Security consideration: prevent Guzzle from using environment variables
        // to configure the outbound proxy.
        'proxy' => [
          'http' => NULL,
          'https' => NULL,
          'no' => [],
        ]
      ];
    }

    $config = NestedArray::mergeDeep($default_config, Settings::get('http_client_config', []), $config);

    return new Client($config);
  }

}
