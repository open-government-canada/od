<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for comment dataset content.
 *
 * @MigrateSource(
 *   id = "comment_dataset_import"
 * )
 */
class CommentDatasetImport extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('comment', 'c')->fields('c');
    $query->innerJoin('node', 'n', 'c.nid = n.nid');
    $query->addField('n', 'type', 'node_type');
    $query->orderBy('c.created');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'cid' => $this->t('Comment ID'),
      'pid' => $this->t('Parent ID'),
      'nid' => $this->t('Node ID'),
      'uid' => $this->t('User ID'),
      'subject' => $this->t('Subject'),
      'hostname' => $this->t('Hostname'),
      'created' => $this->t('Created'),
      'changed' => $this->t('Changed'),
      'status' => $this->t('Status'),
      'thread' => $this->t('Thread'),
      'name' => $this->t('Name'),
      'mail' => $this->t('Mail'),
      'homepage' => $this->t('Homepage'),
      'language' => $this->t('Language'),
      'comment_body' => $this->t('Comment Body'),
      'field_name' => $this->t('Field Name'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'cid' => [
        'type' => 'integer',
        'alias' => 'c',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    $entity_type = 'external_entity';
    $comment_type = 'external_comment';

    // Translation support.
    if (!empty($row->getSourceProperty('translations'))) {
      $row->setSourceProperty('language', 'fr');
    }

    // Node Type.
    $node = $this->select('node', 'db')
      ->fields('db',
      [
        'type',
      ])
      ->condition('nid', $row->getSourceProperty('nid'))
      ->condition('type', 'opendata_package')
      ->execute()
      ->fetchAssoc();

    if ($node['type'] != 'opendata_package') {
      return FALSE;
    }

    // Body.
    $body = $this->select('field_data_comment_body', 'db')
      ->fields('db',
      [
        'comment_body_value',
      ])
      ->condition('entity_id', $row->getSourceProperty('cid'))
      ->condition('revision_id', $row->getSourceProperty('cid'))
      ->condition('language', 'und')
      ->execute()
      ->fetchAssoc();

    // Default comment field.
    $row->setSourceProperty('field_name', 'field_external_comment');

    // URL alias.
    $alias = $this->select('url_alias', 'db')
      ->fields('db', ['alias'])
      ->condition('source', 'node/' . $row->getSourceProperty('nid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('alias', 'dataset/%', 'LIKE')
      ->execute()
      ->fetchCol();

    if (!empty($alias)) {
      $row->setSourceProperty('alias', '/' . end($alias));
      $ckan_uuid = explode('/', $row->getSourceProperty('alias'));
      if (!empty($ckan_uuid)) {
        // Verify if exists in CKAN.
        $uri = 'http://open.canada.ca/data/api/3/action/package_show?id=' . end($ckan_uuid);
        try {
          $response = \Drupal::httpClient()->get($uri,
          [
            'http_errors' => FALSE,
            'headers' => ['Accept' => 'text/plain'],
            'timeout' => 600,
          ]);
          if ($response->getStatusCode() != 200) {
            return FALSE;
          }
        }
        catch (RequestException $e) {
          return FALSE;
        }

        $ckan_uuid = 'ckan-' . end($ckan_uuid);
        $row->setSourceProperty('ckan_uuid', $ckan_uuid);
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }

    // Comment properties.
    $row->setSourceProperty('comment_body', $body['comment_body_value']);
    $row->setSourceProperty('entity_type', $entity_type);
    $row->setSourceProperty('external_comment_type', $comment_type);

    return parent::prepareRow($row);
  }

}
