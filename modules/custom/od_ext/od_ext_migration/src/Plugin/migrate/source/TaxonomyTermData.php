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

    $name_field = 'name_field';

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
        $name_field = 'title_field';

        // Icon.
        $icon = $this->select('field_data_field_taxonomy_icon', 'db')
          ->fields('db', ['field_taxonomy_icon_icon'])
          ->condition('entity_id', $row->getSourceProperty('tid'))
          ->condition('revision_id', $row->getSourceProperty('tid'))
          ->condition('language', 'und')
          ->condition('bundle', 'communities')
          ->execute()
          ->fetchCol();
        $row->setSourceProperty('icon', 'opengov:opengov-' . $icon[0]);

        // CKAN Solr.
        $solr = $this->select('field_data_field_taxonomy_solr_all', 'db')
          ->fields('db', ['field_taxonomy_solr_all_value'])
          ->condition('entity_id', $row->getSourceProperty('tid'))
          ->condition('revision_id', $row->getSourceProperty('tid'))
          ->condition('language', 'und')
          ->condition('bundle', 'communities')
          ->execute()
          ->fetchCol();
        $row->setSourceProperty('solr', $solr[0]);

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

    // Name Field.
    $name = $this->select('field_data_' . $name_field, 'db')
      ->fields('db', [$name_field . '_value'])
      ->condition('entity_id', $row->getSourceProperty('tid'))
      ->condition('revision_id', $row->getSourceProperty('tid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('entity_type', 'taxonomy_term')
      ->execute()
      ->fetchCol();

    if (!empty($name)) {
      $row->setSourceProperty('name', $name[0]);
    }

    $translated_name = $this->select('field_data_' . $name_field, 'db')
      ->fields('db', [$name_field . '_value'])
      ->condition('entity_id', $row->getSourceProperty('tid'))
      ->condition('revision_id', $row->getSourceProperty('tid'))
      ->condition('language', 'fr')
      ->condition('entity_type', 'taxonomy_term')
      ->execute()
      ->fetchCol();

    if (!empty($translated_name)) {
      $row->setSourceProperty('translated_name', $translated_name[0]);
    }

    // Description Field.
    $description = $this->select('field_data_description_field', 'db')
      ->fields('db', ['description_field_value'])
      ->condition('entity_id', $row->getSourceProperty('tid'))
      ->condition('revision_id', $row->getSourceProperty('tid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('entity_type', 'taxonomy_term')
      ->execute()
      ->fetchCol();

    if (!empty($description)) {
      $row->setSourceProperty('description', $description[0]);
    }

    $translated_description = $this->select('field_data_description_field', 'db')
      ->fields('db', ['description_field_value'])
      ->condition('entity_id', $row->getSourceProperty('tid'))
      ->condition('revision_id', $row->getSourceProperty('tid'))
      ->condition('language', 'fr')
      ->condition('entity_type', 'taxonomy_term')
      ->execute()
      ->fetchCol();

    if (!empty($translated_description)) {
      $row->setSourceProperty('translated_description', $translated_description[0]);
    }

    // Parent ID Term.
    $parent_id = $this->select('taxonomy_term_hierarchy', 'th')
      ->fields('th', ['parent'])
      ->condition('tid', $row->getSourceProperty('tid'))
      ->execute()
      ->fetchCol();

    // Links.
    $links = $this->select('field_data_field_taxonomy_links', 'db')
      ->fields('db', [
        'field_taxonomy_links_url',
        'field_taxonomy_links_title',
      ])
      ->condition('entity_id', $row->getSourceProperty('tid'))
      ->condition('revision_id', $row->getSourceProperty('tid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', $vid)
      ->execute()
      ->fetchAll();

    $row->setSourceProperty('vid', $vid);
    $row->setSourceProperty('parent_id', $parent_id[0]);

    if (count($links) > 0) {
      foreach ($links as $key => &$link) {
        $link['title'] = $link['field_taxonomy_links_title'];
        $link['url'] = $link['field_taxonomy_links_url'];
        // Fix www links.
        if (strpos($link['url'], 'www') == 0 && (!strpos($link['url'], 'http://wwww'))) {
          $link['url'] = 'http://' . $link['url'];
        }
        unset($link['field_taxonomy_links_url']);
        unset($link['field_taxonomy_links_title']);
        $links[$key] = $link;
      }

      $row->setSourceProperty('links', $links);
    }

    return parent::prepareRow($row);
  }

}
