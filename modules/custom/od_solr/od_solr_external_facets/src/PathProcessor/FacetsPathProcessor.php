<?php

namespace Drupal\od_solr_external_facets\PathProcessor;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FacetsPathProcessor.
 */
class FacetsPathProcessor implements OutboundPathProcessorInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a PathProcessorAlias object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {

    $cores = [
      'pd_core_ati' => '/search/ati',
      'pd_core_contracts' => '/search/contracts',
      'pd_core_contractsa' => '/search/contractsa',
      'pd_core_grants' => '/search/grants',
      'pd_core_hospitalitya' => '/search/hospitalitya',
      'pd_core_hospitalityq' => '/search/hospitalityq',
      'pd_core_reclassification' => '/search/reclassification',
      'pd_core_travela' => '/search/travela',
      'pd_core_travelq' => '/search/travelq',
      'pd_core_wrongdoing' => '/search/wrongdoing',
      'solr_inventory' => '/search/inventory',
    ];

    $currentLang = $this->languageManager->getCurrentLanguage()->getId();
    $langcode = isset($options['language']) ? $options['language']->getId() : NULL;
    if (!empty($options['query']) && $langcode != $currentLang) {
      if (in_array($path, $cores)) {
        unset($options['query']['f']);
      }
    }

    return $path;
  }

}
