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

        // grgca-ldrosc groupings.
        case 'content/consultation-guidelines-reporting-grants-and-contributions-awards':
        case 'content/appendices-guidelines-reporting-grants-and-contributions-awards':
        case 'content/appendix-b-fields-and-field-descriptions':
        case 'content/appendix-b-field-population-guidance':
        case 'content/guidelines-reporting-grants-and-contributions-awards':
          $path = str_replace('content/', 'grgca-ldrosc/', $path);
          break;

        case 'contenu/consultation-lignes-directrices-rapports-visant-loctroi-subventions-contributions':
        case 'contenu/annexes-lignes-directrices-rapports-visant-loctroi-subventions-contributions':
        case 'contenu/annexe-b-champs-descriptions-champs':
        case 'contenu/annexe-b-guide-linscription-champs':
        case 'contenu/lignes-directrices-rapports-visant-loctroi-subventions-contributions':
          $path = str_replace('contenu/', 'grgca-ldrosc/', $path);
          break;

        // cabinet-directive groupings.
        case 'content/public-consultation-draft-cabinet-directive-regulation':
        case 'content/10-purpose':
        case 'content/20-scope-application':
        case 'content/30-guiding-principles-federal-regulatory-policy':
        case 'content/40-regulatory-lifecycle-approach':
        case 'content/50-development-regulations':
        case 'content/60-regulatory-management':
        case 'content/70-review-and-results':
        case 'content/80-supporting-policies':
        case 'content/appendices':
          $path = str_replace('content/', 'consult/draft/cabinet-directive/', $path);
          break;

        case 'content/consultation-publique-lebauche-directive-cabinet-reglementation':
        case 'contenu/10-but':
        case 'contenu/20-champ-dapplication':
        case 'contenu/30-principes-directeurs-politique-federale-matiere-reglementation':
        case 'contenu/40-demarche-relative-au-cycle-vie-dun-reglement':
        case 'contenu/50-elaboration-reglements':
        case 'contenu/60-gestion-reglementation':
        case 'contenu/70-examen-resultats':
        case 'contenu/80-politiques-jacentes':
        case 'contenu/annexes':
          $path = str_replace('content/', 'consult/draft/cabinet-directive/', $path);
          $path = str_replace('contenu/', 'consult/draft/cabinet-directive/', $path);
          break;

        // DIY toolkit groupings.
        case 'content/1-diy-open-data-toolkit-story':
        case 'content/2-say-hello-open':
        case 'content/3-getting-started':
        case 'content/4-lets-make-plan':
        case 'content/5-putting-pilot-project-plan-action':
        case 'content/6-ongoing-community-engagement':
        case 'content/7-road-ahead':
          $path = str_replace('content/', 'toolkit/diy/', $path);
          break;

        case 'contenu/1-lexperience-trousse-doutils-maison-donnees-ouvertes':
        case 'contenu/2-dites-bonjour-a-communaute-donnees-ouvertes':
        case 'contenu/3-commencer':
        case 'contenu/4-elaborons-plan':
        case 'contenu/5-mettre-oeuvre-projet-pilote':
        case 'contenu/6-maintenir-mobilisation-communaute':
        case 'contenu/7-chemin-a-parcourir':
          $path = str_replace('contenu/', 'toolkit/diy/', $path);
          break;

        // Misc groupings.
        case 'content/engagement-schedule-canadas-4th-plan-open-government':
        case 'content/creating-canadas-4th-plan-open-government-2018-20':
          $path = str_replace('content/', '4plan/', $path);
          break;

        case 'contenu/horaire-lactivite-4e-plan-du-canada-gouvernement-ouvert':
        case 'contenu/elaborer-quatrieme-plan-du-canada-gouvernement-ouvert-2018-2020':
          $path = str_replace('contenu/', '4plan/', $path);
          break;

        case 'content/about-open-government-consultations':
        case 'content/open-government-partnership':
        case 'content/multi-stakeholder-forum-open-government':
          $path = str_replace('content/', '', $path);
          break;

        case 'contenu/au-sujet-consultations-gouvernement-ouvert':
        case 'contenu/partenariat-gouvernement-ouvert':
        case 'contenu/forum-multi-intervenants-gouvernement-ouvert':
          $path = str_replace('contenu/', '', $path);
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
