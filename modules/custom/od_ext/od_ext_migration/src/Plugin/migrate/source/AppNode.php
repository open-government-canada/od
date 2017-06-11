<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for app content.
 *
 * @MigrateSource(
 *   id = "app_node"
 * )
 */
class AppNode extends SqlBase {

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
      ->condition('n.type', 'apps');

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
      'name' => $this->t('Name'),
      'url' => $this->t('URL'),
      'dev' => $this->t('Developer Name'),
      'submitter' => $this->t('Submitter'),
      'date_pub' => $this->t('Date Published'),
      'file_fid' => $this->t('File fid'),
      'file_alt' => $this->t('File alt'),
      'file_title' => $this->t('File title'),
      'app_categories' => $this->t('Categories'),
      'app_freetags' => $this->t('Tags'),
      'community' => $this->t('Community'),
      'departments' => $this->t('Departments'),
      'device_formats' => $this->t('Device Formats'),
      'ribbon' => $this->t('Ribbon'),
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
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchCol();

    // Body.
    $body = $this->select('field_data_body', 'db')
      ->fields('db', ['body_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchCol();

    // Datasets Used.
    $datasets = $this->select('field_data_field_datasets_used_in_app', 'db')
      ->fields('db', ['field_datasets_used_in_app_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchCol();

    // Application Name.
    $name = $this->select('field_data_field_application_name', 'df')
      ->fields('df', ['field_application_name_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchCol();

    // Application URL.
    $url = $this->select('field_data_field_application_url', 'df')
      ->fields('df', ['field_application_url_url'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchCol();

    // Application Developer.
    $dev = $this->select('field_data_field_application_developer', 'df')
      ->fields('df', ['field_application_developer_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchCol();

    // App Submitter Name.
    $submitter = $this->select('field_data_field_application_submitter_name', 'df')
      ->fields('df', ['field_application_submitter_name_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchCol();

    // Date Published.
    $date_pub = $this->select('field_data_field_date_published', 'df')
      ->fields('df', ['field_date_published_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', 'und')
      ->execute()
      ->fetchCol();

    // Screenshots.
    // TODO: switch to fetchAllAssoc + remap in YML.
    $file = $this->select('field_data_field_application_screenshots', 'df')
      ->fields('df', [
        'field_application_screenshots_fid',
        'field_application_screenshots_alt',
        'field_application_screenshots_title',
        'field_application_screenshots_width',
        'field_application_screenshots_height',
      ])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchAllAssoc('field_application_screenshots_fid');

    // App Categories (Subject).
    $app_categories = $this->select('field_data_field_application_categories', 'df')
      ->fields('df', ['field_application_categories_target_id'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', 'und')
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchAllAssoc('field_application_categories_target_id');

    // App Tags (Keywords).
    $app_tags = $this->select('field_data_field_app_tags', 'df')
      ->fields('df', ['field_app_tags_tid'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchAllAssoc('field_app_tags_tid');

    // Community.
    $community = $this->select('field_data_field_community', 'df')
      ->fields('df', ['field_community_tid'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', 'und')
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchAllAssoc('field_community_tid');

    // Departments.
    $departments = $this->select('field_data_field_departments', 'df')
      ->fields('df', ['field_departments_target_id'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchAllAssoc('field_departments_target_id');

    // Device Formats.
    $device_formats = $this->select('field_data_field_device_formats', 'df')
      ->fields('df', ['field_device_formats_target_id'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', 'und')
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchAllAssoc('field_device_formats_target_id');

    // Ribbon.
    $ribbon = $this->select('field_data_field_ribbon', 'df')
      ->fields('df', ['field_ribbon_tid'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'apps')
      ->execute()
      ->fetchAssoc();

    if (!empty($title[0])) {
      $row->setSourceProperty('title', $title[0]);
    }
    elseif (!empty($row->getSourceProperty('translations'))) {
      return FALSE;
    }
    $row->setSourceProperty('body', $body[0]);
    $row->setSourceProperty('datasets', $datasets[0]);
    $row->setSourceProperty('name', $name[0]);
    $row->setSourceProperty('url', $url[0]);
    $row->setSourceProperty('dev', $dev[0]);
    $row->setSourceProperty('submitter', $submitter[0]);
    $row->setSourceProperty('date_pub', $date_pub[0]);
    $row->setSourceProperty('app_categories', $app_categories);
    $row->setSourceProperty('app_freetags', $app_tags);
    $row->setSourceProperty('community', $community);
    $row->setSourceProperty('departments', $departments);
    $row->setSourceProperty('device_formats', $device_formats);
    $row->setSourceProperty('ribbon', $ribbon['field_ribbon_tid']);
    $row->setSourceProperty('file', $file);

    return parent::prepareRow($row);
  }

}
