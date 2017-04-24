<?php

namespace Drupal\od_ext_analytics\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'D3Country' block.
 *
 * @Block(
 *  id = "d3_country",
 *  admin_label = @Translation("Percentages of Site Visits by Country"),
 * )
 */
class D3Country extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['d3_country']['#attached']['library'][] = 'od_ext_analytics/d3vis';
    $build['d3_country']['#theme'] = 'd3vis_country';
    return $build;
  }

}
