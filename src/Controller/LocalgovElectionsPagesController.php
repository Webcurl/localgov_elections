<?php

namespace Drupal\localgov_elections\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Views;

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
    $undeclared_contest_seat_ids = [];
    $undeclared_contest_seat_vids = [];

    foreach ($contests as $contest) {

      $contest_state = $contest->get('moderation_state')->getString();

      $contest_seat_ids = array_column($contest->field_previous_seat_makeup->getValue(), 'target_id');
      $contest_seat_vids = array_column($contest->field_previous_seat_makeup->getValue(), 'target_revision_id');

      // Always process unchanged seats.
      $seat_ids = array_merge($seat_ids, $contest_seat_ids);
      $seat_vids = array_merge($seat_vids, $contest_seat_vids);

      if ($contest_state == 'declared') {
        $candidate_ids = array_merge($candidate_ids, array_column($contest->field_candidates->getValue(), 'target_id'));
        $candidate_vids = array_merge($candidate_vids, array_column($contest->field_candidates->getValue(), 'target_revision_id'));
      }
      else {
        $undeclared_contest_seat_ids = array_merge($undeclared_contest_seat_ids, $contest_seat_ids);
        $undeclared_contest_seat_vids = array_merge($undeclared_contest_seat_vids, $contest_seat_vids);
      }
    }

    $elected_candidates = [];
    if (!empty($candidate_ids)) {
      $elected_candidates = \Drupal::entityQueryAggregate('localgov_elections_candidate')
        ->condition('field_elected', TRUE)
        ->condition('id', $candidate_ids, 'IN')
        ->condition('id', $candidate_vids, 'IN')
        ->groupBy('field_party')
        ->aggregate('id', 'COUNT')
        ->execute();
    }

    $declared_existing_seats = [];
    if (!empty($seat_ids)) {
      $declared_existing_seats = \Drupal::entityQueryAggregate('paragraph')
        ->condition('field_contested', FALSE)
        ->condition('id', $seat_ids, 'IN')
        ->condition('revision_id', $seat_vids, 'IN')
        ->groupBy('field_party')
        ->aggregate('id', 'COUNT')
        ->execute();
    }

    $contested_seats_count = 0;
    if (!empty($undeclared_contest_seat_ids)) {
      $contested_seats_result = \Drupal::entityQueryAggregate('paragraph')
        ->condition('field_contested', TRUE)
        ->condition('id', $undeclared_contest_seat_ids, 'IN')
        ->condition('revision_id', $undeclared_contest_seat_vids, 'IN')
        ->aggregate('id', 'COUNT')
        ->execute();

      $contested_seats_count = $contested_seats_result[0]['id_count'];
    }


    // Join together uncontested seats and newly elected candidates
    $council_makeup = [];

    foreach ([$elected_candidates, $declared_existing_seats] as $group) {
      foreach ($group as $seat) {
        $party = $seat['field_party_target_id'];
        $council_makeup[$party] = isset($council_makeup[$party]) ? $council_makeup[$party] += $seat['id_count'] : $seat['id_count'];
      }
    }

    $party_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadMultiple(array_keys($council_makeup));

    $table_rows = array_map(function ($party_tid, $seats) use ($party_terms) {

      return array(
        'color' => isset($party_terms[$party_tid]) ? ['data' => $party_terms[$party_tid]->get('field_color')->view(['type' => 'color_field_formatter_swatch', 'label' => 'hidden'])] : '',
        'party' => isset($party_terms[$party_tid]) ? $party_terms[$party_tid]->label() : "Others",
        'seats' => $seats,
      );
    }, array_keys($council_makeup), $council_makeup);

    if ($contested_seats_count > 0) {
      $table_rows[] = [
        'color' => '',
        'party' => $this->t('Undeclared'),
        'seats' => $contested_seats_count,
      ];
    }

    $build['content'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Party'), 'colspan' => 2],
        'Seats'
      ],
      '#rows' => $table_rows,
    ];

    $chart_data = array_map(function ($party_tid, $seats) use ($party_terms) {
      return array(
        'color' => isset($party_terms[$party_tid]) ? $party_terms[$party_tid]->get('field_color')->color : '#ccc',
        'party' => isset($party_terms[$party_tid]) ? $party_terms[$party_tid]->label() : "Others",
        'seats' => $seats,
      );
    }, array_keys($council_makeup), $council_makeup);

    if ($contested_seats_count > 0) {
      $chart_data[] = [
        'color' => '#ccc',
        'party' => $this->t('Undeclared')->render(),
        'seats' => $contested_seats_count,
      ];
    }

    $series = [
      '#type' => 'chart_data',
      '#title' => $this->t('Seats'),
      '#data' => array_column($chart_data, 'seats'),
      '#color' => array_column($chart_data, 'color'), // This gets overwritten by global colors in Charts 5.x-dev :-/
    ];

    $xaxis = [
      '#type' => 'chart_xaxis',
      '#title' => $this->t('Party'),
      '#labels' => array_column($chart_data, 'party'),
    ];

    $build['makeup_chart'] = [
      '#type' => 'chart',
      '#chart_type' => 'donut',
      '#title' => $this->t('Council Make-up'),
      '#data_labels' => TRUE,
      //'#colors' => array_column($council_makeup, 'color'),
      '#legend' => TRUE,
      '#legend_position' => 'top',
      'series' => $series,
      'x_axis' => $xaxis,
      '#raw_options' => [
        'options' => [
          'rotation' => -90,
          'circumference' => 180,
        ]
      ]
    ];

    return $build;
  }


  /**
   * Page callback to display list of candidates for a given party.
   * @param NodeInterface $node
   * @param TermInterface $party
   * @return array
   */
  public function embedCandidatesByParty(NodeInterface $node, TermInterface $party) {

    $build['title'] = [
      '#prefix' => '<h2>',
      '#markup' =>$this->t('@party candidates', ['@party' => $party->label()]),
      '#suffix' => '</h2>',
    ];

    $view = Views::getView('localgov_elections_candidates');
    $build['candidate_list'] = $view->buildRenderable('by_party', [$node->id(), $party->id()]);

    return $build;

  }

  /**
   * Title callback.
   * @param NodeInterface $node
   * @param TermInterface $party
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   */
  public function embedCandidatesByPartyTitle(NodeInterface $node, TermInterface $party) {
    return $node->label() . ' - ' . $party->label();
  }

}
