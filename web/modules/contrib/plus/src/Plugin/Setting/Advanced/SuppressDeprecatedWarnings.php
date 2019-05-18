<?php
/**
 * @file
 * Contains \Drupal\plus\Plugin\Setting\Advanced\SuppressDeprecatedWarnings.
 */

namespace Drupal\plus\Plugin\Setting\Advanced;

use Drupal\plus\Annotation\PlusSetting;
use Drupal\plus\Plugin\Setting\SettingBase;
use Drupal\plus\Utility\Element;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "suppress_deprecated_warnings" theme setting.
 *
 * @ingroup plugins_setting
 *
 * @ThemeSetting(
 *   id = "suppress_deprecated_warnings",
 *   type = "checkbox",
 *   weight = -2,
 *   title = @Translation("Suppress deprecated warnings"),
 *   defaultValue = 0,
 *   description = @Translation("Enable this setting if you wish to suppress deprecated warning messages. <strong class='error text-error'>WARNING: Suppressing these messages does not &quote;fix&quote; the problem and you will inevitably encounter issues when they are removed in future updates. Only use this setting in extreme and necessary circumstances.</strong>"),
 *   groups = {
 *     "advanced" = @Translation("Advanced"),
 *   },
 * )
 */
class SuppressDeprecatedWarnings extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function alterFormElement(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    $setting = $this->buildElement($form, $form_state);
    $setting->setProperty('states', [
      'visible' => [
        ':input[name="include_deprecated"]' => ['checked' => TRUE],
      ],
    ]);
  }

}
