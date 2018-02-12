<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\search_api\Entity\Index;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Source plugin for comment content.
 *
 * @MigrateSource(
 *   id = "vote_import"
 * )
 */
class VoteImport extends SqlBase implements ContainerFactoryPluginInterface, DependentPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->fields('n',
      [
        'nid',
        'type',
        'title',
        'language',
      ])
      ->condition('type', ['opendata_package'], 'IN')
      ->orderBy('nid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'type' => $this->t('Type'),
      'title' => $this->t('Title'),
      'language' => $this->t('Language'),
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
    $entity_type = $row->getSourceProperty('type');
    $entity_id = $row->getSourceProperty('nid');

    $votes = $this->select('votingapi_vote', 'vv')
      ->fields('vv', ['value'])
      ->condition('entity_id', $entity_id)
      ->condition('entity_type', 'node')
      ->execute()
      ->fetchCol();
    if (count($votes) == 0) {
      return FALSE;
    }

    $new_entity_id = NULL;
    if ($entity_type == 'opendata_package') {
      $new_entity_type = 'external_entity';
      $alias = $this->select('url_alias', 'db')
        ->fields('db', ['alias'])
        ->condition('source', 'node/' . $row->getSourceProperty('nid'))
        ->condition('language', $row->getSourceProperty('language'))
        ->condition('alias', 'dataset/%', 'LIKE')
        ->execute()
        ->fetchCol();
      if (!empty($alias)) {
        $row->setSourceProperty('alias', '/' . end($alias));
        $ckan_uuid = explode('/', $row->getSourceProperty('alias'));
        $new_entity_id = 'ckan-' . end($ckan_uuid);
      }
    }
    elseif ($entity_type == 'inventory_solr') {
      // Lookup the ID.
      $query = Index::load('inventory')->query();
      $query->addCondition('uuid', $row->getSourceProperty('title'));
      $query->range(0, 1);
      $data = $query->execute();
      $eid = '';
      foreach ($data as $result) {
        $eid = $result->getField('id')->getValues();
      }
      $new_entity_type = 'external_entity';
      if (!empty($eid[0])) {
        $new_entity_id = 'solr_inventory-' . $eid[0];
      }
      unset($query);
      unset($data);
    }
    else {
      // Lookup the correct entity_id / bundle / comment field for node(s).
      $lookup = [
        'app',
        'blog',
        'commitment',
        'consultation',
        'page',
        'suggested_app',
        'suggested_dataset',
        'app_translation',
        'blog_translation',
        'commitment_translation',
        'consultation_translation',
        'idea',
        'idea_translation',
        'page_translation',
        'suggested_app_translation',
        'suggested_dataset_translation',
      ];
      foreach ($lookup as $bundle) {
        if (\Drupal::database()->schema()->tableExists("migrate_map_od_ext_db_node_$bundle")) {
          $new_entity_id = (int) \Drupal::database()->query("SELECT destid1 FROM {migrate_map_od_ext_db_node_$bundle} WHERE sourceid1 = :sourceId", [':sourceId' => $row->getSourceProperty('nid')])->fetchField();
        }
      }
      $new_entity_type = 'node';
    }

    if (!empty($new_entity_id)) {
      foreach ($votes as $vote) {
        $storage = $this->entityManager->getStorage('vote');
        $data = $vote;
        if (($entity_type == 'app') || ($entity_type == 'opendata_package')) {
          $data = $vote * 0.05;
        }

        $field_name = 'field_vud';
        if ($entity_type == 'app') {
          $field_name = 'field_vote';
        }
        $voteData = [
          'entity_type' => $new_entity_type,
          'entity_id'   => $new_entity_id,
          'type'      => 'vote',
          'field_name'  => $field_name,
          'user_id' => 0,
        ];

        echo $entity_type . "," . $entity_id . "," . $new_entity_type . "," . $new_entity_id . "\n";

        $vote = $storage->create($voteData);
        $vote->setValue($data);
        $vote->save();

      }
      unset($votes);
      unset($row);
    }

    $manager = \Drupal::service('plugin.manager.votingapi.resultfunction');
    $manager->recalculateResults($new_entity_type, $new_entity_id, 'vote');

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // Generic handling for Drupal source plugin constants.
    if (isset($this->configuration['constants']['entity_type'])) {
      $this->addDependency('module', $this->entityManager->getDefinition($this->configuration['constants']['entity_type'])->getProvider());
    }
    if (isset($this->configuration['constants']['module'])) {
      $this->addDependency('module', $this->configuration['constants']['module']);
    }
    return $this->dependencies;
  }

}
