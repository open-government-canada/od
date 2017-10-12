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
 * Source plugin for inventory content.
 *
 * @MigrateSource(
 *   id = "inventory_node"
 * )
 */
class InventoryNode extends SqlBase implements ContainerFactoryPluginInterface, DependentPluginInterface {

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
        'vid',
        'language',
        'title',
        'uid',
        'created',
        'changed',
        'status',
      ])
      ->condition('n.type', 'inventory_solr');

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

    // Lookup the ID.
    $query = Index::load('inventory')->query();
    $query->addCondition('uuid', $row->getSourceProperty('title'));
    $query->range(0, 1);
    $data = $query->execute();
    $id = '';

    foreach ($data as $result) {
      $id = $result->getField('id')->getValues();
    }

    if (!empty($id[0])) {
      // Gather votes.
      $votes = $this->select('votingapi_vote', 'vv')
        ->fields('vv', ['value'])
        ->condition('entity_id', $row->getSourceProperty('nid'))
        ->condition('entity_type', 'node')
        ->execute()
        ->fetchCol();

      // Bulk of migration logic.
      foreach ($votes as $vote) {
        $storage = $this->entityManager->getStorage('vote');
        $data = $vote;
        $voteData = [
          'entity_type' => 'external_entity',
          'entity_id'   => 'solr_inventory-' . $id[0],
          'type'      => 'vote',
          'field_name'  => 'field_vud',
          'user_id' => 0,
        ];
        $vote = $storage->create($voteData);
        $vote->setValue($data);
        $vote->save();
      }
    }
    else {
      return FALSE;
    }

    return parent::prepareRow($row);
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
