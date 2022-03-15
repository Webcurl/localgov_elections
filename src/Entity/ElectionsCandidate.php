<?php

namespace Drupal\localgov_elections\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\localgov_elections\ElectionsCandidateInterface;

/**
 * Defines the elections candidate entity class.
 *
 * @ContentEntityType(
 *   id = "localgov_elections_candidate",
 *   label = @Translation("Elections Candidate"),
 *   label_collection = @Translation("Elections Candidates"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\localgov_elections\ElectionsCandidateListBuilder",
 *     "inline_form" = "Drupal\localgov_elections\ElectionsCandidateInlineForm",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\localgov_elections\ElectionsCandidateAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\localgov_elections\Form\ElectionsCandidateForm",
 *       "edit" = "Drupal\localgov_elections\Form\ElectionsCandidateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "localgov_elections_candidate",
 *   revision_table = "localgov_elections_candidate_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer elections candidate",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *     "revision_user" = "revision_user"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/localgov-elections-candidate/add",
 *     "canonical" = "/localgov_elections_candidate/{localgov_elections_candidate}",
 *     "edit-form" = "/admin/content/localgov-elections-candidate/{localgov_elections_candidate}/edit",
 *     "delete-form" = "/admin/content/localgov-elections-candidate/{localgov_elections_candidate}/delete",
 *     "collection" = "/admin/structure/localgov-elections-candidate/list"
 *   },
 *   field_ui_base_route = "entity.localgov_elections_candidate.settings"
 * )
 */
class ElectionsCandidate extends RevisionableContentEntityBase implements ElectionsCandidateInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('The label of the candidate.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the elections candidate was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the elections candidate was last edited.'));

    return $fields;
  }


  function preSave(EntityStorageInterface $storage) {

    $label = implode(", ", [
      $this->get('field_surname')->getString(),
      $this->get('field_forenames')->getString()
    ]);

    $this->set('label', $label);

    parent::preSave($storage);
  }


}
