<?php

namespace Drupal\localgov_elections;

use Drupal\inline_entity_form\Form\EntityInlineForm;

class ElectionsCandidateInlineForm extends EntityInlineForm {

  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);

    // Override label field so we can auto-generate before entity saved
    $fields['label']['type'] = 'callback';
    $fields['label']['callback'] = [get_class($this), 'generateLabel'];
    $fields['label']['label'] = $this->t('Name');

    $fields['field_party_name'] = [
      'type' => 'field',
      'label' => $this->t('Description'),
      'weight' => 2,
      'display_options' => [
        'type' => 'entity_reference_label',
        'settings' => ['link' => FALSE],
      ],
    ];

    $fields['field_votes_won'] = [
      'type' => 'field',
      'label' => $this->t('Votes'),
      'weight' => 2,
    ];

    return $fields;
  }

  static function generateLabel($entity, $theme) {
    $entity->updateLabel();
    return ['#markup' => $entity->label()];
  }

}
