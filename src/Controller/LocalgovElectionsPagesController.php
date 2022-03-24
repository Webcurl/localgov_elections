<?php

namespace Drupal\localgov_elections\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Returns responses for Localgov Elections routes.
 */
class LocalgovElectionsPagesController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build(NodeInterface $node) {

    $contest_ids = \Drupal::entityQuery('localgov_elections_contest')
      ->condition('field_election', $node->id())
      ->execute();

    $contests = \Drupal::entityTypeManager()
      ->getStorage('localgov_elections_contest')
      ->loadMultiple($contest_ids);


    $candidate_ids = [];
    $candidate_vids = [];
    $seat_ids = [];
    $seat_vids = [];
    foreach ($contests as $contest) {
      $candidate_ids = array_merge($candidate_ids, array_column($contest->field_candidates->getValue(), 'target_id'));
      $candidate_vids = array_merge($candidate_vids, array_column($contest->field_candidates->getValue(), 'target_revision_id'));
      $seat_ids = array_merge($seat_ids, array_column($contest->field_previous_seat_makeup->getValue(), 'target_id'));
      $seat_vids = array_merge($seat_vids, array_column($contest->field_previous_seat_makeup->getValue(), 'target_revision_id'));
    }

    $elected_candidates = \Drupal::entityQueryAggregate('localgov_elections_candidate')
      ->condition('field_elected', TRUE)
      ->condition('id', $candidate_ids, 'IN')
      ->condition('id', $candidate_vids, 'IN')
      ->groupBy('field_party')
      ->aggregate('id', 'COUNT')
      ->execute();

    $existing_seats = [];
    if (!empty($seat_ids)) {
      $existing_seats = \Drupal::entityQueryAggregate('paragraph')
        ->condition('field_contested', FALSE)
        ->condition('id', $seat_ids, 'IN')
        ->condition('revision_id', $seat_vids, 'IN')
        ->groupBy('field_party')
        ->aggregate('id', 'COUNT')
        ->execute();
    }


    // Join together uncontested seats and newly elected candidates
    $council_makeup = [];

    foreach ([$elected_candidates, $existing_seats] as $group) {
      foreach ($group as $seat) {
        $party = $seat['field_party_target_id'];
        $council_makeup[$party] = isset($council_makeup[$party]) ? $council_makeup[$party] += $seat['id_count'] : $seat['id_count'];
      }
    }

    $party_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadMultiple(array_keys($council_makeup));

    $council_makeup = array_map(function ($party_tid, $seats) use ($party_terms) {
      $party = isset($party_terms[$party_tid]) ? $party_terms[$party_tid]->label() : "Others";
      return array(
        'party' => $party,
        'seats' => $seats
      );
    }, array_keys($council_makeup), $council_makeup);


    $build['content'] = [
      '#type' => 'table',
      '#header' => ['Party', 'Seats'],
      '#rows' => $council_makeup,
    ];

    return $build;
  }

}
