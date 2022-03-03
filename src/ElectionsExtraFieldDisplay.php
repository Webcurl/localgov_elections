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
   * @see localgov_news_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {

    $fields = [];

    $fields['node']['localgov_election']['display']['localgov_election_results'] = [
      'label' => $this->t('Election Results Summary'),
      'description' => $this->t("Summary of seats contested at this election"),
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

  protected function getViewEmbed(NodeInterface $node, string $display_id) {
    $view = Views::getView('election_winners');
    if (!$view || !$view->access($display_id)) {
      return;
    }
    return [
      '#type' => 'view',
      '#name' => 'election_winners',
      '#display_id' => $display_id,
      '#arguments' => [$node->id()]
    ];
  }

}
