<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for commitment content.
 *
 * @MigrateSource(
 *   id = "commitment_node"
 * )
 */
class CommitmentNode extends SqlBase {

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
      ->condition('n.type', 'commitment');

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
      'department' => $this->t('Department'),
      'relevance' => $this->t('Relevance'),
      'deliverable' => $this->t('Deliverable'),
      'end_date' => $this->t('End Date'),
      'tags' => $this->t('Tags'),
      'pillars' => $this->t('Pillars'),
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
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchCol();

    // Body.
    $body = $this->select('field_data_body', 'db')
      ->fields('db', ['body_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchCol();

    // Ambition.
    $ambition = $this->select('field_data_field_commitment_ambition', 'df')
      ->fields('df', ['field_commitment_ambition_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchCol();

    // Department.
    // TODO: switch to fetchAllAssoc + remap in YML.
    $department = $this->select('field_data_field_department', 'df')
      ->fields('df', ['field_department_tid'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchAllAssoc('field_department_tid');

    // Relevance.
    $relevance = $this->select('field_data_field_commitment_relevance', 'df')
      ->fields('df', ['field_commitment_relevance_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchCol();

    // Deliverable.
    // TODO: switch to fetchAllAssoc + remap in YML.
    $deliverable = $this->select('field_data_field_deliverable', 'df')
      ->fields('df', ['field_deliverable_target_id'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', 'und')
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchAllAssoc('field_deliverable_target_id');

    // End Date.
    $end_date = $this->select('field_data_field_commitment_end_date_txt', 'df')
      ->fields('df', ['field_commitment_end_date_txt_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchCol();

    // Tags.
    $tags = $this->select('field_data_field_commitment_tags', 'df')
      ->fields('df', ['field_commitment_tags_tid'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchAllAssoc('field_commitment_tags_tid');

    // Pillars.
    $pillars = $this->select('field_data_field_pillars', 'df')
      ->fields('df', ['field_pillars_tid'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchAssoc();

    // Status.
    $status = $this->select('field_data_field_commitment_status', 'df')
      ->fields('df', ['field_commitment_status_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchCol();

    // Paragraph Deliverable.
    $paragraph_deliverable = $this->select('field_data_field_deliverable', 'df')
      ->fields('df', ['field_deliverable_target_id'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', 'und')
      ->condition('bundle', 'commitment')
      ->execute()
      ->fetchAllAssoc('field_deliverable_target_id');

    if (!empty($title[0])) {
      $row->setSourceProperty('title', $title[0]);
    }
    elseif (!empty($row->getSourceProperty('translations'))) {
      return FALSE;
    }
    $row->setSourceProperty('body', $body[0]);
    $row->setSourceProperty('department', $department);
    $row->setSourceProperty('ambition', $ambition[0]);
    $row->setSourceProperty('relevance', $relevance[0]);
    $row->setSourceProperty('deliverable', $deliverable);
    $row->setSourceProperty('end_date', $end_date[0]);
    $row->setSourceProperty('tags', $tags);
    $row->setSourceProperty('pillars', $pillars['field_pillars_tid']);
    $row->setSourceProperty('status', $status[0]);
    $row->setSourceProperty('paragraph_deliverable', $paragraph_deliverable);

    return parent::prepareRow($row);
  }

}
