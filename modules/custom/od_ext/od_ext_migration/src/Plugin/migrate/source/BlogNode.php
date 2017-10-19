<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for blog content.
 *
 * @MigrateSource(
 *   id = "blog_node"
 * )
 */
class BlogNode extends SqlBase {

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
      ->condition('n.type', 'blog_post');

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
      'file_fid' => $this->t('File fid'),
      'file_alt' => $this->t('File alt'),
      'file_title' => $this->t('File title'),
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
      ->condition('bundle', 'blog_post')
      ->execute()
      ->fetchCol();

    // Body.
    $body = $this->select('field_data_body', 'db')
      ->fields('db', ['body_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'blog_post')
      ->execute()
      ->fetchCol();

    // Blog Image.
    $file = $this->select('field_data_field_featured_image', 'df')
      ->fields('df', [
        'field_featured_image_fid',
        'field_featured_image_alt',
        'field_featured_image_title',
      ])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'blog_post')
      ->execute()
      ->fetchAssoc();

    // Metatags.
    $metatags = $this->select('metatag', 'df')
      ->fields('df', [
        'data',
      ])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('entity_type', 'node')
      ->execute()
      ->fetchAssoc();
    $tmp = unserialize($metatags['data']);
    $metatags = [
      'title' => isset($tmp['title']['value']) ? $tmp['title']['value'] : '[current-page:title] | [site:name]',
      'description' => isset($tmp['description']['value']) ? $tmp['description']['value'] : '[node:summary]',
      'keywords' => isset($tmp['keywords']['value']) ? $tmp['keywords']['value'] : '',
    ];

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
    $row->setSourceProperty('file_fid', $file['field_featured_image_fid']);
    $row->setSourceProperty('file_alt', $file['field_featured_image_alt']);
    $row->setSourceProperty('file_title', $file['field_featured_image_title']);
    $row->setSourceProperty('metatags', serialize($metatags));

    return parent::prepareRow($row);
  }

}
