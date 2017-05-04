<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for comment content.
 *
 * @MigrateSource(
 *   id = "comment_import"
 * )
 */
class CommentImport extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('comment', 'c')
      ->fields('c',
      [
        'cid',
        'pid',
        'nid',
        'uid',
        'subject',
        'hostname',
        'created',
        'changed',
        'status',
        'thread',
        'name',
        'mail',
        'homepage',
        'language',
        'uuid',
      ]
    );

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

    // Address.
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

    // Default comment field attached to nodes.
    $row->setSourceProperty('field_name', 'comment');

    // Lookup the correct nid / bundle / comment field.
    $lookup = [
      'app',
      'blog',
      'commitment',
      'community',
      'consultation',
      'deliverable',
      'idea',
      'page',
      'suggested_app',
      'suggested_dataset',
    ];
    $found = FALSE;
    foreach ($lookup as $bundle) {
      $nid = (int) \Drupal::database()->query("SELECT destid1 FROM {migrate_map_od_ext_db_node_$bundle} WHERE sourceid1 = :sourceId", [':sourceId' => $row->getSourceProperty('nid')])->fetchField();
      if (!empty($nid)) {
        $found = TRUE;
        $row->setSourceProperty('nid', $nid);
        if ($bundle == 'blog') {
          $row->setSourceProperty('field_name', 'field_blog_comments');
        }
        if ($bundle == 'idea') {
          $row->setSourceProperty('field_name', 'field_comment');
        }
      }
    }

    // Should only be caught for opendata_package.
    if (!$found) {
      return FALSE;
    }

    // Comment Body.
    $row->setSourceProperty('comment_body', $body['comment_body_value']);

    return parent::prepareRow($row);
  }

}
