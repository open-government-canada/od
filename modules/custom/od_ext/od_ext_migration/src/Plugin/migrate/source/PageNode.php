<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for page content.
 *
 * @MigrateSource(
 *   id = "page_node"
 * )
 */
class PageNode extends SqlBase {

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
        'comment',
      ])
      ->condition('n.type', 'wetkit_page');

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
      'comment' => $this->t('Comment'),
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
      ->condition('bundle', 'wetkit_page')
      ->execute()
      ->fetchCol();

    // Body.
    $body = $this->select('field_data_body', 'db')
      ->fields('db', ['body_value'])
      ->condition('entity_id', $row->getSourceProperty('nid'))
      ->condition('revision_id', $row->getSourceProperty('vid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'wetkit_page')
      ->execute()
      ->fetchCol();

    // Hack to get around some content mismatch issues.
    if ($row->getSourceProperty('translations')) {
      $body = str_replace('engagement-', 'contenu/engagement-', $body);
    }
    else {
      $body = str_replace('commitment-', 'content/commitment-', $body);
    }

    // URL alias.
    $alias = $this->select('url_alias', 'db')
      ->fields('db', ['alias'])
      ->condition('source', 'node/' . $row->getSourceProperty('nid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->execute()
      ->fetchCol();

    $path = end($alias);
    if (!empty($path)) {
      switch ($path) {
        case 'content/10-purpose':
        case 'content/20-scope-application':
        case 'content/30-guiding-principles-federal-regulatory-policy':
        case 'content/40-regulatory-lifecycle-approach':
        case 'content/50-development-regulations':
        case 'content/60-regulatory-management':
        case 'content/70-review-and-results':
        case 'content/80-supporting-policies':
          $path = str_replace('content/', 'appendices/', $path);
          break;

        case 'content/appendices':
          $path = 'appendices';
          break;
      }
    }

    $path = str_replace('commitment/', 'commitment/mtsar/2016-2018/', $path);
    $path = str_replace('engagements/', 'engagements/mtsar/2016-2018/', $path);

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

    if (!empty($title[0])) {
      $row->setSourceProperty('title', $title[0]);
    }
    elseif (!empty($row->getSourceProperty('translations'))) {
      return FALSE;
    }
    $row->setSourceProperty('body', $body[0]);

    if (!empty($path)) {
      $row->setSourceProperty('alias', '/' . $path);
    }
    $row->setSourceProperty('metatags', serialize($metatags));

    return parent::prepareRow($row);
  }

}
