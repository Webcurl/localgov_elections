<?php

namespace Drupal\localgov_elections_import\EventSubscriber;

use Drupal\localgov_elections\Entity\ElectionsCandidate;
use Drupal\localgov_elections\Entity\ElectionsContest;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Plugin\migrate\process\Concat;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * mkc migration event subscriber.
 */
class CandidateImportMigrationSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_ROW_SAVE][] = ['onPostRowSave'];
    $events[MigratePlusEvents::PREPARE_ROW][] = ['onPrepareRow'];
    return $events;
  }

  public function onPrepareRow(MigratePrepareRowEvent $event){
    // set a new source property, reference that source 

    // migration object
    $migration = $event->getMigration();

    // make sure it's elections candidate
    if ($migration->id() != 'elections_candidate_xpress'){
      return;
    }

    // Get row / candidate
    $row = $event->getRow();
    $election_id = $migration->getSourceConfiguration()['election_node'];

    // create source property to tell the importer where to add/insert the row (make it relative to election id?)
    $row->setSourceProperty("Row no.", (int)($election_id . $row->getSourceProperty("Row no.")));
  }

  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    // Migration object being imported.
    $migration = $event->getMigration();

    if ($migration->id() == 'elections_candidate_xpress') {

      // Add candidate to list of candidates in contest.
      $row = $event->getRow();
      $area_name = $row->getSourceProperty('Election Area');

      $election_id = $migration->getSourceConfiguration()['election_node'];

      // Find existing contest, create if necessary.
      $contests = \Drupal::entityQuery('localgov_elections_contest')
        ->condition('field_electoral_area.entity:taxonomy_term.name', $area_name)
        ->condition('field_election', $election_id)
        ->accessCheck(true)
        ->execute();

      if (!$contests) {
        // Create new contest
        $area_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
          ->loadByProperties(['name' => $area_name, 'vid' => 'localgov_elections_area']);

        // Make sure if the ward name contains "Ward" to remove it so it can actually find the ward.
        if (str_contains($area_name, "Ward")){
          $area_terms = array_merge($area_terms,
            \Drupal::entityTypeManager()->getStorage('taxonomy_term')
            ->loadByProperties(['name' => trim(str_replace("Ward","",$area_name)), 'vid' => 'localgov_elections_area'])
          );
        }

        $area_term = reset($area_terms);

        $contest_params = [
          'field_election' => $election_id,
          'field_electoral_area' => $area_term->id(),
        ];
        $contest = \Drupal::entityTypeManager()
          ->getStorage('localgov_elections_contest')
          ->create($contest_params);
      }
      else {
        // Use existing contest
        $contest_id = reset($contests);
        $contest = \Drupal::entityTypeManager()->getStorage('localgov_elections_contest')
          ->load($contest_id);
      }

      $candidate = ElectionsCandidate::load($event->getDestinationIdValues()[0]);
      $contest->get('field_candidates')->appendItem([
        'target_id' => $candidate->id(),
        'target_revision_id' => $candidate->getRevisionId(),
      ]);
      $contest->save();
    }


  }

}
