<?php

/**
 * @file
 * Contains \Drupal\migrate_field_translated\Plugin\migrate\process\TranslatedProcess.
 */

namespace Drupal\migrate_field_translated\Plugin\migrate\process;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Processes translations.
 *
 * Each translation Row is processed just as the original Row is.
 *
 * @MigrateProcessPlugin(
 *   id = "d7_translated",
 *   handle_multiples = TRUE
 * )
 */
class TranslatedProcess extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we have no translation property, maybe we ARE a translation. That's
    // ok, just ignore it.
    if (empty($value)) {
      return $value;
    }

    foreach ($value as $lang => &$trow) {
      $migrate_executable->processRow($trow);
    }

    // TODO: Allow skipping content.
    return $value;
  }

}
