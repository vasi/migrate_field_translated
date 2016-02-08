<?php

/**
 * @file
 * Contains \Drupal\migrate_field_translated\Plugin\migrate\destination\TranslatedEntity.
 */

namespace Drupal\migrate_field_translated\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;

/**
 * Destination for translated entities.
 *
 * TODO: Allow other entity types.
 *
 * @MigrateDestination(
 *   id = "d7_translated_entity:node"
 * )
 */
class TranslatedEntity extends EntityContentBase {
  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($plugin_id) {
    // Remove "d7_translated_entity:".
    return substr($plugin_id, 21);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    // TODO: Support overwrite_properties.

    // Remove the translations for now, so the parent function can work.
    $translated_rows = $row->getDestinationProperty('translations');
    $row->removeDestinationProperty('translations');

    $entity = parent::getEntity($row, $old_destination_id_values);

    // Now set the translated values.
    foreach ($translated_rows as $lang => $trow) {
      $dest = $trow->getDestination();

      // Check if we need a translation for this language.
      $translated_fields = [];
      foreach ($dest as $field_name => $values) {
        $definition = $entity->getFieldDefinition($field_name);
        if ($definition->isTranslatable()) {
          $translated_fields[$field_name] = $values;
        }
      }
      if (empty($translated_fields)) {
        continue;
      }

      // Create a translation if it doesn't exist.
      if (!$entity->hasTranslation($lang)) {
        $entity->addTranslation($lang);
      }
      $translation = $entity->getTranslation($lang);

      // Add translated values.
      foreach ($translated_fields as $field_name => $values) {
        $translation->$field_name = $values;
      }
    }

    return $entity;
  }

}
