<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for idea content.
 *
 * @MigrateSource(
 *   id = "idea_node"
 * )
 */
class IdeaNode extends SqlBase {

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
      ])
      ->condition('n.type', 'idea');

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
      'freetags' => $this->t('Tags'),
      'status' => $this->t('Status'),
      'submission_name' => $this->t('Submission Name'),
      'idea_permalink' => $this->t('Idea Permalink'),
      'tags' => $this->t('Tags'),
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
      ->condition('bundle', 'idea')
      ->execute()
      ->fetchCol();

    // Body.
    $body = $this->select('field_data_field_idea', 'db')
      ->fields('db', ['field_idea_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'idea')
      ->execute()
      ->fetchCol();

    // Tags (Keywords).
    // TODO: switch to fetchAllAssoc + remap in YML.
    $freetags = $this->select('field_data_field_idea_tags', 'df')
      ->fields('df', ['field_idea_tags_tid'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'idea')
      ->execute()
      ->fetchAllAssoc('field_idea_tags_tid');

    // Status.
    $status = $this->select('field_data_sc_consultation_field_status', 'df')
      ->fields('df', ['sc_consultation_field_status_tid'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'idea')
      ->execute()
      ->fetchAssoc();

    // Submission Name.
    $submission_name = $this->select('field_data_field_submitted_by', 'db')
      ->fields('db', ['field_submitted_by_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', 'und')
      ->condition('bundle', 'idea')
      ->execute()
      ->fetchCol();

    $idea_permalink = $this->select('url_alias', 'db')
      ->fields('db', ['alias'])
      ->condition('source', 'node/' . $row->getSourceProperty('nid'))
      ->execute()
      ->fetchCol();

    if (!empty($title[0])) {
      $row->setSourceProperty('title', $title[0]);
    }
    elseif (!empty($row->getSourceProperty('translations'))) {
      return FALSE;
    }
    $row->setSourceProperty('body', $body[0]);
    $row->setSourceProperty('freetags', $freetags);
    $row->setSourceProperty('status', $status['sc_consultation_field_status_tid']);
    $row->setSourceProperty('submission_name', $submission_name[0]);
    $row->setSourceProperty('idea_permalink', $idea_permalink[0]);

    return parent::prepareRow($row);
  }

}
