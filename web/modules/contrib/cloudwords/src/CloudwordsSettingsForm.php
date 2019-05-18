<?php

namespace Drupal\cloudwords;

use Drupal\Core\Url;

class CloudwordsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cloudwords.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cloudwords.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $form['cloudwords_client_mode'] = [
      '#type' => 'radios',
      '#title' => t('Environment'),
      '#description' => t('Configure whether to use the staging or production environment.'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('cloudwords.settings')->get('cloudwords_client_mode'),
      '#options' => [
        'production' => t('Production'),
        'stage' => t('Stage'),
      ],
    ];

    $description = t('The API authorization token generated by Cloudwords.');
    $description .= ' ' . t('Follow the instructions to <a target="_blank" href="https://app.cloudwords.com/cust.htm#settings/myaccount/api">generate an API key</a> for your user.');

    $form['stage_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Authorization URLs'),
    ];

    $form['stage_fieldset']['cloudwords_app_url'] = [
      '#type' => 'textfield',
      '#title' => t('Cloudwords Application URL'),
      '#required' => FALSE,
      '#size' => 100,
      '#default_value' => \Drupal::config('cloudwords.settings')->get('cloudwords_app_url'),
    ];

    $form['stage_fieldset']['cloudwords_api_url'] = [
      '#type' => 'textfield',
      '#title' => t('Cloudwords API URL'),
      '#required' => FALSE,
      '#size' => 100,
      '#default_value' => \Drupal::config('cloudwords.settings')->get('cloudwords_api_url'),
    ];

    $form['cloudwords_auth_token'] = [
      '#type' => 'textfield',
      '#title' => t('API Authorization Token'),
      '#description' => $description,
      '#required' => TRUE,
      '#size' => 70,
      '#default_value' => \Drupal::config('cloudwords.settings')->get('cloudwords_auth_token'),
    ];

    $form['cloudwords_temp_directory'] = [
      '#type' => 'textfield',
      '#title' => t('Temp directory'),
      '#description' => t('Set the temporary directory used by cloudwords. Defaults to the Drupal temporary directory.'),
      '#required' => FALSE,
      '#size' => 70,
      '#default_value' => cloudwords_temp_directory(),
    ];

    $form['cloudwords_preview_bundle_enabled'] = [
      '#type' => 'select',
      '#title' => t('Preview Bundle Feature'),
      '#options' => [
        TRUE => t('Enabled'),
        FALSE => t('Disabled'),
      ],
      '#description' => t('Enabling the In-Context Review feature allows static translation and source pages to be delivered to Cloudwords for In-Context Review.'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('cloudwords.settings')->get('cloudwords_preview_bundle_enabled'),
    ];

    $form['automatic_import_translation_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Automatically Import Translations'),
      '#description' => t('Automatically import translations delivered to the Cloudwords.'),
    ];

    $form['automatic_import_translation_fieldset']['cloudwords_auto_import_translation_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => \Drupal::config('cloudwords.settings')->get('cloudwords_auto_import_translation_enabled'),
    ];

    $form['automatic_import_translation_fieldset']['cloudwords_auto_import_translation_frequency'] = [
      '#type' => 'textfield',
      '#title' => t('Frequency (in minutes)'),
      '#description' => t('Set the frequency (in minutes) in which to automatically check and import translated content.  This runs on your Drupal system cron and is dependent on your !settings_cron.  Setting this value to something more frequent than your cron configuration will just trigger it to run on your next Drupal system cron.  Accepted values are whole integer number such as "60" for 1 hour or "120 for 2 hours."', [
        '!settings_cron' => Url::fromRoute(t('cron configuration'), \Drupal\Core\Url::fromRoute('system.cron_settings'))
        ]),
      '#default_value' => \Drupal::config('cloudwords.settings')->get('cloudwords_auto_import_translation_frequency'),
    ];

    $form['automatic_import_translation_fieldset']['cloudwords_auto_import_translation_max_process_items'] = [
      '#type' => 'textfield',
      '#title' => t('Max number of translations to import at a time.'),
      '#description' => t('Set the maximum number of translations to import on a single cron run.'),
      '#default_value' => \Drupal::config('cloudwords.settings')->get('cloudwords_auto_import_translation_max_process_items'),
    ];


    $form['auto_project_creation_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Automatically Create Projects'),
      '#description' => t('Automatic project creation for out of date translations handled by the Cloudwords module.'),
    ];

    $form['auto_project_creation_fieldset']['cloudwords_auto_project_creation_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => \Drupal::config('cloudwords.settings')->get('cloudwords_auto_project_creation_enabled'),
    ];
    /*
  $form['auto_project_creation_fieldset']['cloudwords_auto_project_creation_frequency'] = array(
    '#type' => 'radios',
    '#title' => t('Frequency'),
    '#description' => t('Set the frequency in which this module checks for out of date translations and auto creates projects.  This runs on Cron.'),
    '#default_value' => variable_get('cloudwords_auto_project_creation_frequency', 'weekly'),
    '#options' => array('daily' => t('Daily'), 'weekly' => t('Weekly')),
  );

  $form['auto_project_creation_fieldset']['cloudwords_auto_project_creation_grouping'] = array(
    '#type' => 'radios',
    '#title' => t('Grouping'),
    '#description' => t('Default is by department'),
    '#required' => TRUE,
    '#default_value' => variable_get('cloudwords_auto_project_creation_grouping', 'department'),
    '#options' => array('department' => t('Department'), 'Previous Project' => t('Previous Project'), 'language' => t('Language'),),
  );
*/
    $form = parent::buildForm($form, $form_state);

    $form['actions']['refresh'] = [
      '#type' => 'submit',
      '#value' => t('Refresh translatables'),
      '#submit' => [
        'cloudwords_refresh_translatables'
        ],
    ];

    $form['#attached']['js'][] = drupal_get_path('module', 'cloudwords') . '/cloudwords-form.js';

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    if ($form_state->getValue(['cloudwords_client_mode']) == 'stage') {
      if ($form_state->getValue(['cloudwords_app_url']) || $form_state->getValue(['cloudwords_api_url'])) {

        if ($form_state->getValue(['cloudwords_app_url']) && $form_state->getValue(['cloudwords_app_url'])) {
          $url_app_preffix = parse_url($form_state->getValue(['cloudwords_app_url']));
          $url_api_preffix = parse_url($form_state->getValue(['cloudwords_api_url']));

          if (valid_url($form_state->getValue(['cloudwords_app_url']), $absolute = TRUE) === FALSE || $url_app_preffix['scheme'] != 'https') {
            $form_state->setErrorByName('cloudwords_app_url', t('Application URL is not a valid one or is not starting with https'));
          }
          if (valid_url($form_state->getValue(['cloudwords_api_url']), $absolute = TRUE) === FALSE || $url_api_preffix['scheme'] != 'https') {
            $form_state->setErrorByName('cloudwords_api_url', t('API URL is not a valid one or is not starting with https'));
          }
        }
      }
      else {
        $form_state->setErrorByName('cloudwords_api_url', t('Please, provide a valid API URL'));
        $form_state->setErrorByName('cloudwords_app_url', t('Please, provide a valid Application URL'));
      }
    }

    if (!is_numeric($form_state->getValue(['cloudwords_auto_import_translation_frequency']))) {
      $form_state->setErrorByName('cloudwords_auto_import_translation_frequency', t('Must be a positive number value.  This runs on cron.'));
    }

  }

}
