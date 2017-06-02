<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for webform content.
 *
 * @MigrateSource(
 *   id = "webform_node"
 * )
 */
class WebformNode extends SqlBase {

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
      ->condition('n.type', 'webform');

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
   * Helper function to get the local webform id.
   */
  private function getWebformId($nid) {
    $map = [
      287998 => 'app_ideas',
      52 => 'contact',
      51 => 'suggest_dataset',
      53 => 'submit_app',
      381508 => 'suggest_idea_action_plan',
      390716 => 'ati_records',
      390733 => 'suggest_open_information',
      390748 => 'receive_email',
      556613 => 'submit_event',
      564523 => 'suggest_idea',
    ];
    if (isset($map[$nid])) {
      return $map[$nid];
    }
    else {
      return FALSE;
    }
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
      ->condition('bundle', 'webform')
      ->execute()
      ->fetchCol();

    // Body.
    $body = $this->select('field_data_body', 'db')
      ->fields('db', ['body_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'webform')
      ->execute()
      ->fetchCol();

    // Webform.
    $webform = $this->select('webform', 'db')
      ->fields('db', ['nid'])
      ->condition('nid', $row->getSourceProperty('nid'))
      ->execute()
      ->fetchCol();

    // Webform Status.
    $webform_status = $this->select('webform', 'db')
      ->fields('db', ['status'])
      ->condition('nid', $row->getSourceProperty('nid'))
      ->execute()
      ->fetchCol();

    if (!empty($title[0])) {
      $row->setSourceProperty('title', $title[0]);
    }
    elseif (!empty($row->getSourceProperty('translations'))) {
      return FALSE;
    }
    $row->setSourceProperty('body', $body[0]);
    $row->setSourceProperty('webform', $this->getWebformId($webform[0]));
    if (isset($webform_status[0])) {
      $row->setSourceProperty('webform_status', ($webform_status[0] == 1) ? 'open' : 'closed');
    }

    return parent::prepareRow($row);
  }

}
