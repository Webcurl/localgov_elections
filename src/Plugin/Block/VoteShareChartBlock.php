<?php

namespace Drupal\localgov_elections\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a vote share chart block.
 *
 * @Block(
 *   id = "localgov_elections_vote_share_chart",
 *   admin_label = @Translation("Vote Share Chart"),
 *   category = @Translation("Custom")
 *   context_definitions = {
 *     "contest" = @ContextDefinition("entity:localgov_elections_contest", label = @Translation("Contest"))
 *   }
 * )
 */
class VoteShareChartBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

    /**
   * {@inheritdoc}
   */
  public function build() {

    $contest = $this->entityTypeManager
      ->getStorage('localgov_elections_contest')
      ->load($this->getContextValue('contest'));





    //->getStorage('localgov_elections_candidate')
    //  ->getQuery()->add

    //$this->entityTypeManager
    //  ->getStorage('localgov_elections_candidate')
    //  ->getQuery()->add

    $series = [
      '#type' => 'chart_data',
      '#title' => $this->t('Votes'),
      '#data' => [257, 235, 325, 340],
      '#color' => ['#ccc', '#f00', '#0f0', '#00f'],
    ];

    $xaxis = [
      '#type' => 'chart_xaxis',
      '#title' => $this->t('Party'),
      '#labels' => [$this->t('Socialist Worker'), $this->t('Monster Raving Looney'), $this->t('Pirate'), $this->t('Independent')],
    ];

    $build['chart'] = [
      '#type' => 'chart',
      '#chart_type' => 'pie',
      //'#chart_id' => 'foo',
      //'#id' => Html::getUniqueId('chart_' . 'foo'),
      '#title' => $this->t('Share of the vote'),
      //'#title_position' => 'above',
      //'#tooltips' => $chart_settings['display']['tooltips'],
      '#data_labels' => TRUE,
      '#background' => $chart_settings['display']['background'] ?? 'transparent',
      '#legend' => !empty($chart_settings['display']['legend_position']),
      '#legend_position' => $chart_settings['display']['legend_position'] ?? '',
      //'#width' => $chart_settings['display']['dimensions']['width'],
      //'#height' => $chart_settings['display']['dimensions']['height'],
      //'#width_units' => $chart_settings['display']['dimensions']['width_units'],
      //'#height_units' => $chart_settings['display']['dimensions']['height_units'],
      //'#attributes' => ['data-drupal-selector-chart' => Html::getId($chart_id)],
      // Pass info about the actual view results to allow further processing.
      //'#view' => $this->view,
      'series' => $series,
      'x_axis' => $xaxis,
      //'y_axis' => $yaxis,
      '#raw_options' => [
        'plugins' => ['ChartDataLabels'],
        'options' => [
          'plugins' => [
            // Change options for ALL labels of THIS CHART
            'datalabels' => [
              'color' => '#36A2EB'
            ]
          ]
        ],
      ]
    ];

    return $build;
  }

}
