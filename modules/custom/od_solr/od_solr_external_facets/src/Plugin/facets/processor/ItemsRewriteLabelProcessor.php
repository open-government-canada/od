<?php

namespace Drupal\od_solr_external_facets\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor that rewrites label values.
 *
 * @FacetsProcessor(
 *   id = "items_rewrite_label",
 *   label = @Translation("Items Rewrite Labels"),
 *   description = @Translation("Rewrite label(s) according to GoC specification"),
 *   stages = {
 *     "build" = 40
 *   }
 * )
 */
class ItemsRewriteLabelProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as $result) {
      switch ($result->getDisplayValue()) {
        case 'GC':
          $result->setDisplayValue($this->t('Government of Canada'));
          break;

        case 'PUBLIC':
          $result->setDisplayValue($this->t('User Contributed'));
          break;
      }
    }
    return $results;
  }

}
