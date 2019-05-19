<?php

namespace Drupal\uc_stock\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure stock settings for this site.
 */
class StockSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_stock_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_stock.settings',
      'uc_stock.mail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uc_stock.settings');
    $mail = $this->config('uc_stock.mail');

    $form['uc_stock_threshold_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send email notification when stock level reaches its threshold value'),
      '#default_value' => $config->get('notify'),
    ];

    $form['uc_stock_threshold_notification_recipients'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification recipients'),
      '#default_value' => $config->get('recipients'),
      '#description' => $this->t('The list of comma-separated email addresses that will receive the notification.'),
    ];

    $form['uc_stock_threshold_notification_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message subject'),
      '#default_value' => $mail->get('threshold_notification.subject'),
    ];

    $form['uc_stock_threshold_notification_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message text'),
      '#default_value' => $mail->get('threshold_notification.body'),
      '#description' => $this->t('The message the user receives when the stock level reaches its threshold value.'),
      '#rows' => 10,
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['uc_order', 'uc_stock', 'node', 'site', 'store'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('uc_stock.settings')
      ->set('notify', $form_state->getValue('uc_stock_threshold_notification'))
      ->set('recipients', $form_state->getValue('uc_stock_threshold_notification_recipients'))
      ->save();

    $this->config('uc_stock.mail')
      ->set('threshold_notification.subject', $form_state->getValue('uc_stock_threshold_notification_subject'))
      ->set('threshold_notification.body', $form_state->getValue('uc_stock_threshold_notification_message'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
