<?php

namespace Drupal\od_ext_webform\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\webformSubmissionInterface;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "solr_data_form_handler",
 *   label = @Translation("Solr Data Copy Handler"),
 *   category = @Translation("Form Handler"),
 *   description = @Translation("Copies field data from a Solr record to existing matching fields (based on machine name) in the webform"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class SolrDataFormHandler extends WebformHandlerBase {

  public function preSave(WebformSubmissionInterface $webform_submission) {
    $values = $webform_submission->getData();
    $index = \Drupal\search_api\Entity\Index::load($values['solr_core']);
    $query = $index->query();
    $query->addCondition('id', $values['entity_id']);
    $results = $query->execute();
    $row = reset($results->getResultItems());
    $field_names = array_keys($row->getFields());

    foreach($field_names as $field_name) {
      if (isset($values[$field_name])) {
        $webform_submission->setElementData($field_name, implode(", ", $row->getField($field_name)->getValues()));
      }
    }
  }

}
