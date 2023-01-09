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

    $renderer = \Drupal::service('renderer');

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

    $build['council_control'] = [
      '#type' => 'text',
      '#markup' => "<div class='council-control-label'>" . $node->field_council_control->value . "</div"
    ];

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
      '#chart_type' => 'pie',
      '#title' => $this->t('Council Make-up'),
      '#data_labels' => TRUE,
      //'#colors' => array_column($council_makeup, 'color'),
      '#legend' => TRUE,
      '#legend_position' => 'top',
      'series' => $series,
      'x_axis' => $xaxis,
      '#raw_options' => [
        'options' => [
          'rotation' => 0,
          'circumference' => 360,
        ]
      ]
    ];

    // Add contest cache tags.
    foreach ($contests as $contest) {
      $renderer->addCacheableDependency($build, $contest);
    }

    // Add contest cache tags.
    foreach ($party_terms as $party) {
      $renderer->addCacheableDependency($build, $party);
    }

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


  public function voteShare(NodeInterface $node)
  {
    $renderer = \Drupal::service('renderer');

    $contest_ids = \Drupal::entityQuery('localgov_elections_contest')
      ->condition('field_election', $node->id())
      ->execute();

    $contests = \Drupal::entityTypeManager()
      ->getStorage('localgov_elections_contest')
      ->loadMultiple($contest_ids);


    $total_turnout = 0;
    $contests_declared = 0;
    $candidate_ids = [];
    $candidate_vids = [];

    foreach ($contests as $contest) {

      $contest_state = $contest->get('moderation_state')->getString();

      if ($contest_state == 'declared') {
        $candidate_ids = array_merge($candidate_ids, array_column($contest->field_candidates->getValue(), 'target_id'));
        $candidate_vids = array_merge($candidate_vids, array_column($contest->field_candidates->getValue(), 'target_revision_id'));
        $contests_declared++;
        $total_turnout += $contest->get('field_turnout')->value;
      }
    }

    $table_rows = [];
    $party_votes_set = [];
    if (!empty($candidate_ids)) {
      $party_votes_set = \Drupal::entityQueryAggregate('localgov_elections_candidate')
        ->condition('id', $candidate_ids, 'IN')
        ->condition('id', $candidate_vids, 'IN')
        ->groupBy('field_party')
        ->aggregate('field_votes_won', 'SUM')
        ->execute();

      $party_terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadMultiple(array_filter(array_column($party_votes_set, 'field_party_target_id')));

      // append percentage
      foreach  ($party_votes_set as $party) {

        $party_tid = $party['field_party_target_id'];
        $party_term = $party_terms[$party_tid] ?? NULL;
          $table_rows[] = [
            'color' => $party_term ? ['data' => $party_term->get('field_color')->view(['type' => 'color_field_formatter_swatch', 'label' => 'hidden'])] : '',
            'party' => $party_term ? $party_term->label() : 'Other',
            'votes' => number_format($party['field_votes_won_sum']),
            'percent' => number_format(($party['field_votes_won_sum'] / $total_turnout) * 100, 2),
          ];
      }
    }

    $build['status'] = [
      '#markup' => "<p>After " . $contests_declared . " of " . count($contests) . " wards declared.</p>",
    ];

    $build['content'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Party'), 'colspan' => 2],
        'Votes Won',
        '% Share'
      ],
      '#rows' => $table_rows,
    ];

    // Add contest cache tags.
    foreach ($contests as $contest) {
      $renderer->addCacheableDependency($build, $contest);
    }

    // Add contest cache tags.
    foreach ($party_terms as $party) {
      $renderer->addCacheableDependency($build, $party);
    }

    // Generate chart.

    $chart_data = array_map(function ($party_votes) use ($party_terms, $total_turnout) {
      $party_tid = $party_votes['field_party_target_id'];
      $party_term = $party_terms[$party_tid] ?? NULL;
      return array(
        'color' => $party_term ? $party_term->get('field_color')->color : '#ccc',
        'party' => $party_term ? $party_term->label() : "Others",
        'votes' => $party_votes['field_votes_won_sum'],
        'percent' => number_format(($party_votes['field_votes_won_sum'] / $total_turnout) * 100, 2),
      );
    }, $party_votes_set);

    //if ($contested_seats_count > 0) {
    //  $chart_data[] = [
    //    'color' => '#ccc',
    //    'party' => $this->t('Undeclared')->render(),
    //    'seats' => $contested_seats_count,
    //  ];
    //}

    $series = [
      '#type' => 'chart_data',
      '#title' => $this->t('Votes'),
      // Charts module currently expects data to be in $data[1] for pie/donut on chartsjs
      '#data' => array_map(fn($value) => ['stupid', $value],  array_column($chart_data, 'votes')),
      '#color' => array_column($chart_data, 'color'), // This gets overwritten by global colors in Charts 5.x-dev :-/
    ];

    $xaxis = [
      '#type' => 'chart_xaxis',
      '#title' => $this->t('Party'),
      '#labels' => array_column($chart_data, 'party'),
    ];

    $build['vote_share_chart'] = [
      '#type' => 'chart',
      '#chart_type' => 'pie',
      '#title' => $this->t('Share of the vote'),
      '#data_labels' => TRUE,
    //  '#colors' => array_column($chart_data, 'color'),
      '#legend' => TRUE,
      '#legend_position' => 'top',
      'series' => $series,
      'x_axis' => $xaxis,

      '#raw_options' => [
       'options' => [
         'rotation' => 0,
         'circumference' => 360,
       ]
      ]
    ];


    return $build;
  }

}
