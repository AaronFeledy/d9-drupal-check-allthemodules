<?php
namespace Drupal\pagedesigner_audio\Plugin\pagedesigner\Handler;

use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\pagedesigner\Handler\Standard;
use Drupal\ui_patterns\Definition\PatternDefinitionField;

/**
 * @PagedesignerHandler(
 *   id = "audio",
 *   name = @Translation("Audio handler"),
 *   types = {
 *      "audio"
 *   },
 * )
 */
class Audio extends Standard
{
    public function collectAttachments(&$attachments)
    {
        $attachments['library'][] = 'pagedesigner_audio/pagedesigner';
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(Element $entity)
    {
        $data = [
            'src' => '',
            'id' => $entity->field_media->target_id,
        ];
        if ($entity->field_media->entity != null) {
            $data['src'] = $entity->field_media->entity->field_media_file->entity->url();
        }
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function get(Element $entity)
    {
        if ($entity->field_media->entity != null) {
            return $entity->field_media->entity->field_media_file->entity->url();
        }
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function patch(Element $entity, $data)
    {
        if (!empty($data['id'])) {
            $entity->field_media->target_id = $data['id'];
            $entity->saveEdit();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function generate($definition, $data)
    {
        return parent::generate(['type' => 'audio', 'name' => 'audio'], $data);
    }
}
