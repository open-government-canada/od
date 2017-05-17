<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for deliverable content.
 *
 * @MigrateSource(
 *   id = "deliverable_paragraph"
 * )
 */
class DeliverableParagraph extends SqlBase {

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
      ->condition('n.type', 'deliverable');

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
      'next_steps' => $this->t('Next Steps'),
      'progress' => $this->t('Progress'),
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
      ->condition('bundle', 'deliverable')
      ->execute()
      ->fetchCol();

    // Body.
    $body = $this->select('field_data_field_deliverable_body', 'db')
      ->fields('db', ['field_deliverable_body_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'deliverable')
      ->execute()
      ->fetchCol();

    // Next Steps.
    $next_steps = $this->select('field_data_field_deliverable_next_steps', 'db')
      ->fields('db', ['field_deliverable_next_steps_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'deliverable')
      ->execute()
      ->fetchCol();

    // Progress.
    $progress = $this->select('field_data_field_deliverable_progress', 'db')
      ->fields('db', ['field_deliverable_progress_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'deliverable')
      ->execute()
      ->fetchCol();

    // Status.
    $status = $this->select('field_data_field_deliverable_status', 'db')
      ->fields('db', ['field_deliverable_status_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'deliverable')
      ->execute()
      ->fetchCol();

    if (!empty($title[0])) {
      $row->setSourceProperty('title', $title[0]);
    }
    elseif (!empty($row->getSourceProperty('translations'))) {
      return FALSE;
    }
    $row->setSourceProperty('body', $body[0]);
    $row->setSourceProperty('next_steps', $next_steps[0]);
    $row->setSourceProperty('progress', $progress[0]);
    $row->setSourceProperty('status', $status[0]);

    return parent::prepareRow($row);
  }

}
