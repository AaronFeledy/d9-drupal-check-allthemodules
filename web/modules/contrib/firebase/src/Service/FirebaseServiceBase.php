<?php

namespace Drupal\firebase\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\firebase\FirebaseServiceInterface;
use GuzzleHttp\ClientInterface;

/**
 * Provides a base class for service, working with FCM.
 */
class FirebaseServiceBase implements FirebaseServiceInterface {
  use DependencySerializationTrait {
    __wakeup as wakeup;
    __sleep as sleep;
  }

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Firebase API Key.
   *
   * @var string
   */
  protected $key;

  /**
   * Firebase service endpoint.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * Request body.
   *
   * @var array
   */
  protected $body;

  /**
   * Constructs a FirebaseServiceBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \GuzzleHttp\ClientInterface $client
   *   An HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   The logger channel.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $client, LoggerChannelInterface $loggerChannel) {
    $this->configFactory = $configFactory;
    $this->client = $client;
    $this->logger = $loggerChannel;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'Content-Type' => 'application/json',
      'Authorization' => 'key=' . $this->key,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    // Build the header of our request to Firebase.
    // The header is composed by Content-Type and Authorization.
    // The Authorization is our server key, which needs to be provided
    // by the admin interface.
    // @see \Drupal\firebase\Form\ConfigurationForm.
    $headers = $this->buildHeader();

    // The body is composed by an array of data.
    if (!$this->body) {
      throw new \InvalidArgumentException('The body of request shouldn\'t be blank.');
    }

    if (!$response = $this->client->post($this->endpoint, [
      'headers' => $headers,
      'body' => Json::encode($this->body),
    ])) {
      // Error connecting to Firebase API. For instance, timeout.
      return FALSE;
    }

    $responseBody = Json::decode($response->getBody());
    if ($response->getStatusCode() === 200 && (!isset($responseBody['failure']) || $responseBody['failure'] == 0)) {
      return $responseBody;
    }

    // Something went wrong. We didn't sent the push notification.
    // Common errors:
    // - Authentication Error
    //   The Server Key is invalid.
    // - Invalid Registration Token
    //   The token (generated by app) is not recognized by Firebase.
    // @see https://firebase.google.com/docs/cloud-messaging/http-server-ref#error-codes
    $errorMessage = reset($responseBody['results'])['error'];
    $this->logger->error('Failure message: @error',
      [
        '@error' => $errorMessage,
      ]);

    return FALSE;
  }

  /**
   * Reset body of service.
   */
  public function resetService() {
    unset($this->body);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $vars = $this->sleep();
    // Do not serialize static cache.
    unset($vars['key'], $vars['endpoint'], $vars['body']);
    return $vars;
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    $this->wakeup();
    // Initialize static cache.
    $this->key = '';
    $this->endpoint = '';
    $this->body = [];
  }

}
