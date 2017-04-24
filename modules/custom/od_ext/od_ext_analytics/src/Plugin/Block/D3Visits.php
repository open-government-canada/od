<?php

namespace Drupal\od_ext_analytics\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'D3Visits' block.
 *
 * @Block(
 *  id = "d3_visits",
 *  admin_label = @Translation("Number of Visits"),
 * )
 */
class D3Visits extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['d3_visits']['#attached']['library'][] = 'od_ext_analytics/d3vis';
    $build['d3_visits']['#theme'] = 'd3vis_visits';
    return $build;
  }

}
