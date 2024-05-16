<?php

namespace Drupal\localgov_elections;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\views\Views;

class ElectionsExtraFieldDisplay {

  use StringTranslationTrait;

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @see localgov_elections_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {

    $fields = [];

    $fields['node']['localgov_election']['display']['localgov_election_results'] = [
      'label' => $this->t('Election Results Summary'),
      'description' => $this->t("Summary of seats contested at this election"),
      'weight' => -20,
      'visible' => TRUE,
    ];


    $fields['localgov_elections_contest']['localgov_elections_contest']['display']['vote_share_chart'] = [
      'label' => $this->t('Vote share chart'),
      'description' => $this->t("Share of teh vote chart"),
      'weight' => -20,
      'visible' => TRUE,
    ];
    return $fields;
  }

  public function nodeView(array &$build, NodeInterface $node, EntityViewDisplayInterface $display, $view_mode) {
    if ($display->getComponent('localgov_election_results')) {
      $build['localgov_election_results'] = $this->getViewEmbed($node, 'embed_1');
    }
  }

  public function contestView(array &$build, ElectionsContestInterface $contest, EntityViewDisplayInterface $display, $view_mode)
  {

    if ($display->getComponent('vote_share_chart')) {

      // Hide vote share before election has begun.
      $contest_state = $contest->get('moderation_state')->getString();

      if ($contest_state != 'declared') {
        return;
      }

      $candidate_ids = [];

      foreach ($contest->get('field_candidates') as $candidate_item) {
        $candidate_ids[] = $candidate_item->target_id;
        $candidate_vids[] = $candidate_item->target_revision_id;
      }

      $query = \Drupal::entityQueryAggregate('localgov_elections_candidate')
        ->accessCheck(TRUE)
        ->condition('id', $candidate_ids, 'IN')
        ->condition('revision_id', $candidate_vids, 'IN');

      $results = $query->groupBy('field_party')
        ->aggregate('field_votes_won', 'SUM')
        ->execute();

      $party_terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadMultiple(array_column($results, 'field_party_target_id'));

      $series = [
        '#type' => 'chart_data',
        '#title' => $this->t('Votes'),
        '#data' => [],
        //'#color' => [], // This currently gets overwritten by global colors in Charts 5.x :-/
      ];

      $xaxis = [
        '#type' => 'chart_xaxis',
        '#title' => $this->t('Party'),
        '#labels' => [],
      ];

      $colors = [];
      foreach ($results as $result) {
        $party_id = $result['field_party_target_id'];
        $series['#data'][] = (int) $result['field_votes_won_sum'];
        $series['#color'][] = !empty($party_terms[$party_id]) ? $party_terms[$party_id]->get('field_color')->color : '#cccccc';
        $xaxis['#labels'][] = !empty($party_terms[$party_id]) ? $party_terms[$party_id]->label() : 'Independent';
      }

      $build['vote_share_chart'] = [
        '#type' => 'chart',
        '#attached' => [
          'library' => ['localgov_elections/chartjs_plugin_datalabels']
        ],
        '#chart_type' => 'pie',
        '#title' => $this->t('Share of the vote'),
        '#data_labels' => TRUE,
        '#colors' => $colors,
        '#legend' => TRUE,
        '#legend_position' => 'top',
        'series' => $series,
        'x_axis' => $xaxis,
        '#raw_options' => [
          'options' => [
            'rotation' => 0,
            'circumference' => 360,
            'plugins' => [
              // Change options for ALL labels of THIS CHART
//              'datalabels' => [
//                'formatter' => 'Drupal.localgov_elections.percentageLabelFormatter', // Can't do this here, need to pass actual JS function
//                'color' => '#ff0000'
//             ]
            ]
          ],
        ]
      ];
    }
  }

  protected function getViewEmbed(NodeInterface $node, string $display_id) {
    $view = Views::getView('localgov_elections_candidates');
    if (!$view || !$view->access($display_id)) {
      return;
    }
    return [
      '#type' => 'view',
      '#name' => 'localgov_elections_candidates',
      '#display_id' => $display_id,
      '#arguments' => [$node->id()]
    ];
  }

}
