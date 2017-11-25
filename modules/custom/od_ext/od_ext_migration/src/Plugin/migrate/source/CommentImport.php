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

    $found = FALSE;
    $entity_type = 'node';
    $comment_type = 'comment';

    // Translation support.
    if (!empty($row->getSourceProperty('translations'))) {
      $row->setSourceProperty('language', 'fr');
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
    $row->setSourceProperty('field_name', 'comment');

    // Lookup the correct entity_id / bundle / comment field for node(s).
    $lookup = [
      'app',
      'blog',
      'commitment',
      'consultation',
      'page',
      'suggested_app',
      'suggested_dataset',
      'app_translation',
      'blog_translation',
      'commitment_translation',
      'consultation_translation',
      'idea',
      'idea_translation',
      'page_translation',
      'suggested_app_translation',
      'suggested_dataset_translation',
    ];
    foreach ($lookup as $bundle) {
      if (\Drupal::database()->schema()->tableExists("migrate_map_od_ext_db_node_$bundle")) {
        $entity_id = (int) \Drupal::database()->query("SELECT destid1 FROM {migrate_map_od_ext_db_node_$bundle} WHERE sourceid1 = :sourceId", [':sourceId' => $row->getSourceProperty('nid')])->fetchField();
        if (!empty($entity_id)) {
          $found = TRUE;
          $row->setSourceProperty('entity_id', $entity_id);
          if ($bundle == 'blog') {
            $row->setSourceProperty('field_name', 'field_blog_comments');
          }
        }
      }
    }

    // Lookup the correct entity_id / type / comment field for paragraph(s).
    $lookup = [
      'deliverable',
      'deliverable_translation',
    ];
    foreach ($lookup as $bundle) {
      if (\Drupal::database()->schema()->tableExists("migrate_map_od_ext_db_paragraph_$bundle")) {
        $entity_id = (int) \Drupal::database()->query("SELECT destid1 FROM {migrate_map_od_ext_db_paragraph_$bundle} WHERE sourceid1 = :sourceId", [':sourceId' => $row->getSourceProperty('nid')])->fetchField();
        if (!empty($entity_id)) {
          $found = TRUE;
          $entity_type = 'paragraph';
          $comment_type = 'paragraphs_comments';
          $row->setSourceProperty('entity_id', $entity_id);
          $row->setSourceProperty('field_name', 'field_comment');
        }
      }
    }

    // Lookup the correct entity_id / type / comment field for community taxonomy.
    $entity_id = $row->getSourceProperty('nid');
    $tid = '';
    switch ($entity_id) {
      case 390755:
        $tid = 1229;
        break;

      case 390769:
        $tid = 1241;
        break;

      case 467947:
        $tid = 1225;
        break;
    }
    if (!empty($tid) && \Drupal::database()->schema()->tableExists("migrate_map_od_ext_db_taxonomy_term")) {
      $tid = (int) \Drupal::database()->query("SELECT destid1 FROM {migrate_map_od_ext_db_taxonomy_term} WHERE sourceid1 = :sourceId", [':sourceId' => $tid])->fetchField();
      if (!empty($tid)) {
        $found = TRUE;
        $entity_type = 'taxonomy_term';
        $comment_type = 'taxonomy_comment';
        $row->setSourceProperty('entity_id', $tid);
        $row->setSourceProperty('field_name', 'field_taxonomy_comments');
      }
    }

    // Should only be caught for opendata_package.
    if (!$found) {
      return FALSE;
    }

    // Comment properties.
    $uid = (int) \Drupal::database()->query("SELECT destid1 FROM {migrate_map_od_ext_db_user} WHERE sourceid1 = :sourceId", [':sourceId' => $row->getSourceProperty('uid')])->fetchField();
    if (!empty($uid)) {
      $row->setSourceProperty('uid', $uid);
    }
    $row->setSourceProperty('comment_body', $body['comment_body_value']);
    $row->setSourceProperty('entity_type', $entity_type);
    $row->setSourceProperty('comment_type', $comment_type);

    return parent::prepareRow($row);
  }

}
