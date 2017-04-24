<?php

namespace Drupal\od_ext_analytics\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'D3Downloads' block.
 *
 * @Block(
 *  id = "d3_downloads",
 *  admin_label = @Translation("Number of Downloads"),
 * )
 */
class D3Downloads extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['d3_downloads']['#attached']['library'][] = 'od_ext_analytics/d3vis';
    $build['d3_downloads']['#theme'] = 'd3vis_downloads';
    return $build;
  }

}
