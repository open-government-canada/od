<?php

/**
 * @file
 * Contains a custom drush command that will sync with ckan.
 */

/**
 * Implements hook_drush_command().
 */
function od_solr_external_drush_command() {
  $items = [];
  $items['sync-with-ckan'] = [
    'description' => 'Drush command to get sync external entities with ckan.',
    'aliases' => ['swc'],
  ];
  return $items;
}

/**
 * Callback function for hook_drush_command().
 */
function drush_od_solr_external_sync_with_ckan() {

  $processed_count = 0;
  $removed_records = 0;

  $query = \Drupal::database()->select('config', 'cf');
  $query->fields('cf', ['data']);
  $query->condition('name', '%external_entities.type.%', 'LIKE');
  $config_data = $query->execute()->fetchCol();

  $endpoints = [];
  foreach ($config_data as $config) {
    $config = unserialize($config);
    if (is_array($config)) {
      switch ($config['type']) {
        case 'ckan':
          $host = explode('package_', $config['endpoint']);
          $config['endpoint'] = $host[0] . 'package_list';
          break;

        default:
          break;
      }
      $endpoints[$config['type']] = $config['endpoint'];
    }
  }

  $url = $endpoints['ckan'];
  $response = \Drupal::httpClient()->get($url, ['headers' => ['Accept' => 'text/plain'], 'timeout' => 600]);
  $ckan_count = 0;
  if ($response->code == 200) {
    $data = (string) $response->getBody();
    $data = json_decode($data, TRUE);
    $ckan_count = count($data['result']);

    drush_print_r('Found ' . $ckan_count . ' ckan records');

    $ckan_record_set = array_flip($data['result']);
    unset($data);
    unset($response);
  }

  $url = $endpoints['solr_inventory'] . '?q=*&version=2.2&wt=json&fl=id&rows=2147483647';
  $response = \Drupal::httpClient()->get($url, ['headers' => ['Accept' => 'text/plain'], 'timeout' => 600]);
  $inventory_count = 0;
  if ($response->code == 200) {
    $data = (string) $response->getBody();
    $data = json_decode($data, TRUE);
    $inventory_count = $data['response']['numFound'];

    drush_print_r('Found ' . $inventory_count . ' inventory records');

    $inventory_record_set = [];
    foreach ($data['response']['docs'] as $doc) {
      $inventory_record_set[$doc['id']] = TRUE;
    }
    unset($data);
    unset($response);
  }

  if ($inventory_count > 0 && $ckan_count > 0) {
    drush_print_r('Running for all types');

    $query = \Drupal::database()->select('external_comment__commented_external_entity', 'db');
    $query->fields('db', ['entity_id', 'commented_external_entity_target_id']);
    $cids = $query->execute()->fetchAllAssoc('entity_id');
    foreach ($cids as $cid => $record) {
      if (preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $record->commented_external_entity_target_id, $remote_id)) {
        if (isset($ckan_record_set[$remote_id[0]]) || isset($inventory_record_set[$remote_id[0]])) {
          // Hey it exists, nothing to do.
        }
        else {
          // Remove the record since it was not found in inventory or ckan.
          // Remove from all external comments tables.
          $query = \Drupal::database()->delete('external_comment');
          $query->condition('cid', $cid, '=');
          $query->execute();

          $query = \Drupal::database()->delete('external_comment__comment_body');
          $query->condition('entity_id', $cid, '=');
          $query->execute();

          $query = \Drupal::database()->delete('external_comment__commented_external_entity');
          $query->condition('entity_id', $cid, '=');
          $query->condition('commented_external_entity_target_id', $record->commented_external_entity_target_id, '=');
          $query->execute();

          $query = \Drupal::database()->delete('external_comment_entity_statistics');
          $query->condition('entity_id', $record->commented_external_entity_target_id, '=');
          $query->condition('cid', $cid, '=');
          $query->execute();

          $query = \Drupal::database()->delete('external_comment_field_data');
          $query->condition('cid', $cid, '=');
          $query->execute();

          \Drupal::logger('od_solr_external')->notice($url . ' was not found.');
          \Drupal::logger('od_solr_external')->notice('cid:' . $cid . '(' . $remote_id[0] . ') comments removed.');
          drush_print_r('cid:' . $cid . '(' . $remote_id[0] . ') comments removed.');
          $removed_records++;
        }

        $processed_count++;
        if ($processed_count % 500 == 0) {
          drush_print_r('Removed ' . $removed_records . ' records');
          drush_print_r('Processed ' . $processed_count . ' records');
        }

      }
    }
    drush_print_r('Removed ' . $removed_records . ' records');
    drush_print_r('Processed ' . $processed_count . ' records');

    $votes_removed = 0;

    // Now remove any votes that are redundant.
    $query = \Drupal::database()->select('votingapi_result', 'db');
    $query->fields('db', ['entity_id']);
    $query->condition('entity_type', 'external_entity', '=');
    $votes = $query->execute()->fetchAllAssoc('entity_id');
    if (is_array($votes) && count($votes) > 0) {
      $votes = array_keys($votes);
      $vote_count = count($votes);
      foreach ($votes as $entity_id) {
        if (preg_match('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $entity_id, $remote_id)) {
          if (isset($ckan_record_set[$remote_id[0]]) || isset($inventory_record_set[$remote_id[0]])) {
            // Hey it exists, nothing to do.
          }
          else {
            // Delete the vote data result and the vote.
            $query = \Drupal::database()->delete('votingapi_result');
            $query->condition('entity_id', $entity_id, '=');
            $query->condition('type', 'external_entity', '=');
            $query->execute();

            $query = \Drupal::database()->delete('votingapi_vote');
            $query->condition('entity_id', $entity_id, '=');
            $query->condition('type', 'external_entity', '=');
            $query->execute();

            $votes_removed++;
          }
        }
        if ($votes_removed % 500 == 0) {
          drush_print_r('Removed ' . $votes_removed . ' records out of ' . $vote_count);
        }
      }
    }

    drush_print_r('Removed ' . $votes_removed . ' records out of ' . $vote_count);
  }
  else {
    drush_print_r('ERROR: Unable to reach ckan or inventory endpoints');
  }
}
