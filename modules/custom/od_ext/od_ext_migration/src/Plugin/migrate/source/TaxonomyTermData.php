<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for taxonomy content.
 *
 * @MigrateSource(
 *   id = "taxonomy_term_data"
 * )
 */
class TaxonomyTermData extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('taxonomy_term_data', 't')
      ->fields('t',
      [
        'tid',
        'vid',
        'name',
        'description',
        'language',
      ]
    );

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'tid' => $this->t('Term ID'),
      'vid' => $this->t('Vocabulary ID'),
      'name' => $this->t('Name'),
      'description' => $this->t('Description'),
      'language' => $this->t('Language'),
      'parent_id' => $this->t('Parent ID'),
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

    // Translation support.
    if (!empty($row->getSourceProperty('translations'))) {
      $row->setSourceProperty('language', 'fr');
    }

    // Taxonomy vocabularies brought in statically for dependency reasons with
    // field configs yet we still need some internal mappings for subsequent
    // db migration to work based on old vid key.
    $vid = '';
    switch ($row->getSourceProperty('vid')) {
      case 2:
        $vid = 'wxt_categories';
        break;

      case 3:
        $vid = 'departments';
        break;

      case 4:
        $vid = 'device_formats';
        break;

      case 5:
        $vid = 'app_categories';
        break;

      case 7:
        $vid = 'app_freetags';
        $row->setSourceProperty('language', 'en');
        break;

      case 8:
        $vid = 'app_ribbon';
        break;

      case 9:
        $vid = 'site_structure';
        break;

      case 10:
        $vid = 'consultation_status';
        break;

      case 11:
        $vid = 'idea_status';
        break;

      case 13:
        $vid = 'idea_freetags';
        break;

      case 15:
        $vid = 'subscriptions';
        break;

      case 17:
        $vid = 'communities';
        break;

      case 18:
        $vid = 'commitment_freetags';
        $row->setSourceProperty('language', 'en');
        break;

      case 19:
        $vid = 'commitments';
        break;

      default:
        $vid = 'wxt_categories';
    }

    // Parent ID Term.
    $parent_id = $this->select('taxonomy_term_hierarchy', 'th')
      ->fields('th', ['parent'])
      ->condition('tid', $row->getSourceProperty('tid'))
      ->execute()
      ->fetchCol();

    $row->setSourceProperty('vid', $vid);
    $row->setSourceProperty('parent_id', $parent_id[0]);

    return parent::prepareRow($row);
  }

}
