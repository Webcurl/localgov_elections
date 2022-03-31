<?php

namespace Drupal\localgov_elections\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\localgov_elections\ElectionsContestInterface;

/**
 * Defines the elections contest entity class.
 *
 * @ContentEntityType(
 *   id = "localgov_elections_contest",
 *   label = @Translation("Elections Contest"),
 *   label_collection = @Translation("Elections Contests"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\localgov_elections\ElectionsContestListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\localgov_elections\ElectionsContestAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\localgov_elections\Form\ElectionsContestForm",
 *       "edit" = "Drupal\localgov_elections\Form\ElectionsContestForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "localgov_elections_contest",
 *   revision_table = "localgov_elections_contest_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer elections contest",
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
 *     "add-form" = "/admin/localgov-elections/contest/add",
 *     "canonical" = "/localgov_elections_contest/{localgov_elections_contest}",
 *     "edit-form" = "/admin/localgov-elections/contest/{localgov_elections_contest}/edit",
 *     "delete-form" = "/admin/localgov-elections/contest/{localgov_elections_contest}/delete",
 *     "collection" = "/admin/localgov-elections/contest"
 *   },
 *   field_ui_base_route = "entity.localgov_elections_contest.settings"
 * )
 */
class ElectionsContest extends RevisionableContentEntityBase implements ElectionsContestInterface {

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
      ->setDescription(t('The time that the elections contest was created.'))
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
      ->setDescription(t('The time that the elections contest was last edited.'));

    return $fields;
  }

  function preSave(EntityStorageInterface $storage) {

    $label = 'Unknown Area';
    if (!$this->get('field_electoral_area')->isEmpty()) {
      $area = $this->get('field_electoral_area')->entity;
      if ($area) {
        $label = $this->get('field_electoral_area')->entity->label();
      }
    }

    $this->set('label', $label);

    parent::preSave($storage);
  }

}
