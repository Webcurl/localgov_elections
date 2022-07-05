<?php

namespace Drupal\localgov_elections\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Plugin implementation of the 'Seats Summary' formatter.
 *
 * @FieldFormatter(
 *   id = "localgov_elections_seats_summary",
 *   label = @Translation("Seats Summary"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class SeatsSummaryFormatter extends FormatterBase {

  /**
   * @var EntityReferenceFieldItemListInterface items
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $total_seats = count($items);

    $contested_seats = 0;
    foreach ($items->referencedEntities() as $seat) {
        if ($seat->field_contested->value) {
          $contested_seats++;
        }
    }

    $element[0]['total_seats']['#markup'] = $this->formatPlural($total_seats, 'This ward has 1 seat. ', 'This ward has @count seats. ');
    $element[0]['contested_seats']['#markup'] = $this->formatPlural($contested_seats, '1 seat was up for election.', '@count seats were up for election.');

    return $element;
  }

  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_type = $field_definition->getSetting('target_type');
    $paragraph_type = \Drupal::entityTypeManager()->getDefinition($target_type);
    if ($paragraph_type) {
      return $paragraph_type->entityClassImplements(ParagraphInterface::class);
    }

    return FALSE;
  }


}
