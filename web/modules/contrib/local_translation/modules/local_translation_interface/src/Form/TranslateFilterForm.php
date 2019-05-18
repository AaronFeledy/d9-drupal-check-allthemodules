<?php

namespace Drupal\local_translation_interface\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\local_translation_interface\LocalTranslationInterfaceLanguagesTrait;
use Drupal\locale\Form\TranslateFilterForm as TranslateFilterFormOrigin;

/**
 * Class TranslateFilterForm.
 *
 * @package Drupal\local_translation_interface\Form
 */
class TranslateFilterForm extends TranslateFilterFormOrigin {
  use LocalTranslationInterfaceLanguagesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'local_translation_interface_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Fix filters ordering using #weight property.
    $form['filters']['status']['langcode_from']['#weight'] = 0;
    $form['filters']['status']['langcode']['#weight']      = 1;
    $form['filters']['status']['translation']['#weight']   = 2;
    // Attach an additional library for styling fixes.
    $form['#attached']['library'][] = 'local_translation_interface/style';
    if (!empty($this->userRegisteredSkills)) {
      $form['#attached']['drupalSettings']['userRegisteredLanguages'] = $this->userRegisteredSkills;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $from = $form_state->getValue('langcode_from');
    $to   = $form_state->getValue('langcode');
    if ($from === $to && $from !== 'en') {
      $message = $this->t("The 'from' and 'to' language fields can't have the same value.");
      $form_state->setErrorByName('langcode_from', $message);
      $form_state->setErrorByName('langcode', $message);
    }
  }

}
