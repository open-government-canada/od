<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for suggested_dataset content.
 *
 * @MigrateSource(
 *   id = "suggested_dataset_node"
 * )
 */
class SuggestedDatasetNode extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n',
      [
        'nid',
        'vid',
        'language',
        'title',
        'uid',
        'created',
        'changed',
        'status',
      ])
      ->condition('n.type', 'suggested_datasets');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'vid' => $this->t('Revision ID'),
      'language' => $this->t('Language'),
      'title' => $this->t('Title'),
      'uid' => $this->t('User ID'),
      'body' => $this->t('Body'),
      'created' => $this->t('Created'),
      'changed' => $this->t('Changed'),
      'status' => $this->t('Status'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Translation support.
    if (!empty($row->getSourceProperty('translations'))) {
      $row->setSourceProperty('language', 'fr');
    }

    // Title Field.
    $title = $this->select('field_data_title_field', 'db')
      ->fields('db', ['title_field_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'suggested_datasets')
      ->execute()
      ->fetchCol();

    // Body.
    $body = $this->select('field_data_body', 'db')
      ->fields('db', ['body_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'suggested_datasets')
      ->execute()
      ->fetchCol();

    // Departments.
    $department = $this->select('field_data_field_ddepartment', 'df')
      ->fields('df', ['field_ddepartment_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'suggested_datasets')
      ->execute()
      ->fetchCol();

    // Status.
    $status = $this->select('field_data_field_status', 'df')
      ->fields('df', ['field_status_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'suggested_datasets')
      ->execute()
      ->fetchCol();

    // Status Link.
    $url = $this->select('field_data_field_status_link_location', 'df')
      ->fields('df', ['field_status_link_location_url'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'suggested_datasets')
      ->execute()
      ->fetchCol();

    // Likes.
    $likes = $this->select('votingapi_vote', 'vv')
      ->fields('vv', ['value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('entity_type', 'node')
      ->condition('tag', 'thumbs_rate')
      ->execute()
      ->fetchCol();

    // URL alias.
    $alias = $this->select('url_alias', 'db')
      ->fields('db', ['alias'])
      ->condition('source', 'node/' . $row->getSourceProperty('nid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->execute()
      ->fetchCol();

    if (!empty($title[0])) {
      $row->setSourceProperty('title', $title[0]);
    }
    elseif (!empty($row->getSourceProperty('translations'))) {
      return FALSE;
    }

    if (!empty($alias)) {
      $row->setSourceProperty('alias', '/' . end($alias));
    }
    $row->setSourceProperty('body', $body[0]);
    $row->setSourceProperty('department', $department[0]);
    $row->setSourceProperty('dataset_status', $status[0]);
    $row->setSourceProperty('url', $url[0]);
    $row->setSourceProperty('likes', $likes);

    return parent::prepareRow($row);
  }

}
