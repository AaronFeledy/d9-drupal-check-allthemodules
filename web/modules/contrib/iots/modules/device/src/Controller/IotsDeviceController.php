<?php

namespace Drupal\iots_device\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\iots_device\Entity\IotsDeviceInterface;

/**
 * Class IotsDeviceController.
 *
 *  Returns responses for Device routes.
 */
class IotsDeviceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Device  revision.
   *
   * @param int $iots_device_revision
   *   The Device  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($iots_device_revision) {
    $iots_device = $this->entityManager()->getStorage('iots_device')->loadRevision($iots_device_revision);
    $view_builder = $this->entityManager()->getViewBuilder('iots_device');

    return $view_builder->view($iots_device);
  }

  /**
   * Page title callback for a Device  revision.
   *
   * @param int $iots_device_revision
   *   The Device  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($iots_device_revision) {
    $iots_device = $this->entityManager()->getStorage('iots_device')->loadRevision($iots_device_revision);
    return $this->t('Revision of %title from %date', ['%title' => $iots_device->label(), '%date' => format_date($iots_device->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Device .
   *
   * @param \Drupal\iots_device\Entity\IotsDeviceInterface $iots_device
   *   A Device  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(IotsDeviceInterface $iots_device) {
    $account = $this->currentUser();
    $langcode = $iots_device->language()->getId();
    $langname = $iots_device->language()->getName();
    $languages = $iots_device->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $iots_device_storage = $this->entityManager()->getStorage('iots_device');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $iots_device->label()]) : $this->t('Revisions for %title', ['%title' => $iots_device->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all device revisions") || $account->hasPermission('administer device entities')));
    $delete_permission = (($account->hasPermission("delete all device revisions") || $account->hasPermission('administer device entities')));

    $rows = [];

    $vids = $iots_device_storage->revisionIds($iots_device);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\iots_device\IotsDeviceInterface $revision */
      $revision = $iots_device_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $iots_device->getRevisionId()) {
          $link = $this->l($date, new Url('entity.iots_device.revision', ['iots_device' => $iots_device->id(), 'iots_device_revision' => $vid]));
        }
        else {
          $link = $iots_device->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.iots_device.revision_revert', ['iots_device' => $iots_device->id(), 'iots_device_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.iots_device.revision_delete', ['iots_device' => $iots_device->id(), 'iots_device_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['iots_device_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
