<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for Post Processing of Taxonomy.
 *
 * @MigrateSource(
 *   id = "post_process_taxonomy"
 * )
 */
class PostProcessTaxonomy extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('taxonomy_term_field_data', 't')
      ->fields('t',
      [
        'tid',
        'vid',
        'langcode',
        'name',
        'description__value',
        'default_langcode',
      ])
      ->condition('t.vid',
      [
        'communities',
      ], 'IN');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'tid' => $this->t('Taxonomy Term ID'),
      'vid' => $this->t('Revision ID'),
      'langcode' => $this->t('Langcode'),
      'name' => $this->t('Name'),
      'description__value' => $this->t('Description'),
      'default_langcode' => $this->t('Default Langcode'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'tid' => [
        'type' => 'integer',
        'alias' => 't',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    if ($row->getSourceProperty('langcode') == 'fr' &&
        $row->getSourceProperty('translations') != TRUE) {
      return FALSE;
    }

    if ($row->getSourceProperty('langcode') == 'en' &&
        $row->getSourceProperty('translations') == TRUE) {
      return FALSE;
    }

    return parent::prepareRow($row);
  }

}
