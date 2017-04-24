<?php

namespace Drupal\od_ext_analytics\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'D3CountryForeign' block.
 *
 * @Block(
 *  id = "d3_country_foreign",
 *  admin_label = @Translation("Percentages of Site Visits by Foreign Country"),
 * )
 */
class D3CountryForeign extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['d3_country_foreign']['#attached']['library'][] = 'od_ext_analytics/d3vis';
    $build['d3_country_foreign']['#theme'] = 'd3vis_country_foreign';
    return $build;
  }

}
