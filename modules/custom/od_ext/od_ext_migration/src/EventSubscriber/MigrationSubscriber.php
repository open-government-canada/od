<?php

namespace Drupal\od_ext_migration\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
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
   * Constructs a new MigrationSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
  }

  /**
   * Code to run after a migration has been imported.
   */
  public function onMigrationPostImport(MigrateImportEvent $event) {
    if ($event->getMigration()->id() == 'od_ext_node_landing_page') {
      // Set front page to panelized "homepage".
      $this->config->get('system.site')
        ->set('page.front', '/homepage')
        ->save(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT] = 'onMigrationPostImport';
    return $events;
  }

}
