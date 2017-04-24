<?php

namespace Drupal\od_ext_analytics\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'D3Provinces' block.
 *
 * @Block(
 *  id = "d3_provinces",
 *  admin_label = @Translation("Percentages of Site Visits by Province and Territory"),
 * )
 */
class D3Provinces extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['d3_provinces']['#attached']['library'][] = 'od_ext_analytics/d3vis';
    $build['d3_provinces']['#theme'] = 'd3vis_provinces';
    return $build;
  }

}
