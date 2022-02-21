<?php

namespace Drupal\localgov_elections\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the elections candidate entity edit forms.
 */
class ElectionsCandidateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New elections candidate %label has been created.', $message_arguments));
      $this->logger('localgov_elections')->notice('Created new elections candidate %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The elections candidate %label has been updated.', $message_arguments));
      $this->logger('localgov_elections')->notice('Updated new elections candidate %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.localgov_elections_candidate.canonical', ['localgov_elections_candidate' => $entity->id()]);
  }

}
