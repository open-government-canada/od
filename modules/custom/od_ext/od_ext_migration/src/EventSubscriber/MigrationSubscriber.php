<?php

namespace Drupal\od_ext_migration\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * WxT mode subscriber for controller requests.
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MigrationSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory;
  }

  /**
   * Code to run after a migration has been imported.
   */
  public function onMigrationPostImport(MigrateImportEvent $event) {
    if ($event->getMigration()->id() == 'od_ext_node_landing_page') {
      // Set front page to panelized "homepage".
      $this->config->getEditable('system.site')
        ->set('page.front', '/homepage')
        ->save(TRUE);
    }
  }

  /**
   * Code to run after a migration row has been saved.
   */
  public function onMigrationPostRowSave(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->id() == 'od_ext_db_node_blog') {
      $entities = $this->entityTypeManager->getStorage('group')->loadByProperties(['field_shortcode' => 'tbs-sct']);
      $entity = reset($entities);
      $destinationIds = $event->getDestinationIdValues();
      $node = $this->entityTypeManager->getStorage('node')->load($destinationIds[0]);
      $entity->addContent($node, 'group_node:blog_post');
    }
    if ($event->getMigration()->id() == 'od_ext_db_user') {
      $entities = $this->entityTypeManager->getStorage('group')->loadByProperties(['field_shortcode' => 'tbs-sct']);
      $entity = reset($entities);
      $destinationIds = $event->getDestinationIdValues();
      $user = $this->entityTypeManager->getStorage('user')->load($destinationIds[0]);

      $group_roles[4] = 'department-tbs_editor';
      $group_roles[5] = 'department-web_content_manager';
      $group_roles[9] = 'department-content_reviewer';

      $row = $event->getRow();
      $options_list = [];
      if (count($row->getSourceProperty('user_roles')) > 0) {
        $source_roles = array_keys($row->getSourceProperty('user_roles'));
        foreach ($source_roles as $key) {
          if (isset($group_roles[$key])) {
            $options[] = $group_roles[$key];
          }
        }
      }
      $entity->removeMember($user);
      if (count($options) > 0) {
        $options_list['group_roles'] = $options;
        $entity->addMember($user, $options_list);
      }
    }
    if ($event->getMigration()->id() == 'od_ext_db_node_consultation') {
      $entities = $this->entityTypeManager->getStorage('group')->loadByProperties(['field_shortcode' => 'tbs-sct']);
      $entity = reset($entities);
      $destinationIds = $event->getDestinationIdValues();
      $node = $this->entityTypeManager->getStorage('node')->load($destinationIds[0]);
      $entity->addContent($node, 'group_node:consultation');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_ROW_SAVE] = 'onMigrationPostRowSave';
    $events[MigrateEvents::POST_IMPORT] = 'onMigrationPostImport';
    return $events;
  }

}
