<?php

namespace Drupal\localgov_elections;

use Drupal\inline_entity_form\Form\EntityInlineForm;

class ElectionsCandidateInlineForm extends EntityInlineForm {

  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);

    $fields['field_party'] = [
      'type' => 'field',
      'label' => $this->t('Party'),
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

}
