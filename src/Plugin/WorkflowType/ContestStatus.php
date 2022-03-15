<?php

namespace Drupal\localgov_elections\Plugin\WorkflowType;

use Drupal\workflows\Plugin\WorkflowTypeBase;

/**
 * Test workflow type.
 *
 * @WorkflowType(
 *   id = "localgov_elections_contest_status",
 *   label = @Translation("Contest Status"),
 *   required_states = {
 *     "draft",
 *     "published",
 *     "counting",
 *     "declared",
 *   }
 * )
 */
class ContestStatus extends WorkflowTypeBase {


}
