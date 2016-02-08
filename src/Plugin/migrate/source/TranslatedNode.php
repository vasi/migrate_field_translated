<?php

/**
 * @file
 * Contains \Drupal\migrate_field_translated\Plugin\migrate\source\TranslatedNode.
 */

namespace Drupal\migrate_field_translated\Plugin\migrate\source;

use Drupal\Core\Language\LanguageInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 node source from database.
 *
 * Yields normal D7 node content, but with an extra 'translations' property.
 * The 'translations' property holds an array of language code => Row,
 * with each Row holding the fields for that language.
 *
 * TODO: Handle non-node entities.
 *
 * @MigrateSource(
 *   id = "d7_translated_node",
 *   source_provider = "node"
 * )
 */
class TranslatedNode extends \Drupal\node\Plugin\migrate\source\d7\Node {
  /**
   * Add fields to a row in the given language.
   *
   * @param \Drupal\migrate\Row $row
   *   The row to operate on.
   * @param $language
   *   The language for which to add fields.
   */
  protected function addFields(Row $row, $language) {
    // Get Field API field values.
    foreach (array_keys($this->getFields('node', $row->getSourceProperty('type'))) as $field) {
      $nid = $row->getSourceProperty('nid');
      $vid = $row->getSourceProperty('vid');
      $row->setSourceProperty($field, $this->getFieldValues('node', $field, $nid, $vid, $language));
    }
  }

  /**
   * Get the languages into which an entity is translated.
   *
   * @param string $entity_type
   *   The type of entity.
   * @param int $entity_id
   *   The entity ID.
   * @param string $base_language
   *   The original language of the entity.
   *
   * @return array a list of language codes
   */
  protected function translationLanguages($entity_type, $entity_id, $base_language) {
    $query = $this->select('entity_translation', 'et')
      ->fields('et', ['language'])
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('language', $base_language, '<>');
    return $query->execute()->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Save the non-field properties, we want them in each translation.
    $base = clone $row;

    // Add the fields in the base language.
    $base_language = $row->getSourceProperty('language');
    $this->addFields($row, $base_language);

    // Add the translations.
    $translations = [];
    $other_languages = $this->translationLanguages('node',
      $row->getSourceProperty('nid'), $base_language);
    foreach ($other_languages as $language) {
      $translations[$language] = clone $base;
      $this->addFields($translations[$language], $language);
      $translations[$language]->setSourceProperty('language', $language);
    }
    $row->setSourceProperty('translations', $translations);

    // Don't use the parent implementation, it doesn't respect translations.
    return FieldableEntity::prepareRow($row);
  }

  /**
   * Retrieves field values for a single field of a single entity.
   *
   * This is translation-aware, we don't add fields in the wrong languages.
   *
   * @see \Drupal\node\Plugin\migrate\source\d7\Node::getFieldValues
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field
   *   The field name.
   * @param int $entity_id
   *   The entity ID.
   * @param int|null $revision_id
   *   (optional) The entity revision ID.
   * @param string $language
   *   (optional) The entity language
   *
   * @return array
   *   The raw field values, keyed by delta.
   */
  protected function getFieldValues($entity_type, $field, $entity_id, $revision_id = NULL, $language = NULL) {
    $table = (isset($revision_id) ? 'field_revision_' : 'field_data_') . $field;
    $query = $this->select($table, 't')
      ->fields('t')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('deleted', 0);
    if (isset($revision_id)) {
      $query->condition('revision_id', $revision_id);
    }
    if (isset($language)) {
      // We want a field if it's in our language, or not language-specific.
      $query->condition('language',
        [$language, LanguageInterface::LANGCODE_NOT_SPECIFIED], 'IN');
    }
    $values = [];
    foreach ($query->execute() as $row) {
      foreach ($row as $key => $value) {
        $delta = $row['delta'];
        if (strpos($key, $field) === 0) {
          $column = substr($key, strlen($field) + 1);
          $values[$delta][$column] = $value;
        }
      }
    }
    return $values;
  }

}
