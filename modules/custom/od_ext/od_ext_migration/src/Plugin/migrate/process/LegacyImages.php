<?php

namespace Drupal\od_ext_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Convert a Drupal image link to a image link pointing to the legacy directory.
 *
 * @MigrateProcessPlugin(
 *   id = "od_legacy_images",
 * )
 *
 * @link https://blog.kalamuna.com/news/converting-drupal-7-media-tags-during-a-drupal-8-migration
 */
class LegacyImages extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration process plugin, configured for lookups in od_ext_db_file.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrateProcessInterface $migration_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPlugin = $migration_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    // Default required migration configuration.
    $migration_configuration = [
      'migration' => [
        'od_ext_db_file',
      ],
    ];

    // Handle any custom migrations leveraging this plugin.
    $migration_dependencies = $migration->getMigrationDependencies();
    if (isset($migration_dependencies['required'])) {
      foreach ($migration_dependencies['required'] as $dependency) {
        if (strpos($dependency, 'file') !== FALSE) {
          $migration_configuration['migration'][] = $dependency;
        }
      }
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migrate.process')->createInstance('migration', $migration_configuration, $migration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$value) {
      throw new MigrateSkipProcessException();
    }

    $value = str_replace('/sites/default/files/', '/sites/default/files/legacy', $value);

    return $value;
  }

}
