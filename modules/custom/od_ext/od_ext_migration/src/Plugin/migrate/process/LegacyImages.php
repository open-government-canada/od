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

    $value = ' ' . $value . ' ';
    $value = preg_replace_callback(
      "/src *= *[\"']?(\/sites\/default\/files\/[^\"']*)/s",
      function ($match) use ($migrate_executable, $row, $destination_property) {
        return $this->replaceToken($match, $migrate_executable, $row, $destination_property);
      },
      $value
    );

    return $value;
  }

  /**
   * Replace callback to convert a media file tag into HTML markup.
   *
   * Partially copied from 7.x media module media.filter.inc (media_filter).
   *
   * @param string $match
   *   Takes a match of tag code
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migrate executable helper class.
   * @param \Drupal\migrate\Row $row
   *   The current row after processing.
   * @param string $destination_property
   *   The destination propery.
   */
  private function replaceToken($match, $migrate_executable, $row, $destination_property) {
    $imgSrc = $match[1];

    try {
      if (!is_string($imgSrc)) {
        throw new MigrateException('Unable to find matching tag');
      }

      $file = substr(strrchr($imgSrc, "/"), 1);
      $dir = str_replace($file, '', $imgSrc);
      if (strpos($dir, '/sites/default/files/legacy/') !== FALSE) {
        return $match[0];
      }
      else {
        return 'src="' . str_replace('/sites/default/files/', '/sites/default/files/legacy/', $match[1]);
      }
    }
    catch (Exception $e) {
      $msg = t('Unable to render img from %tag. Error: %error', ['%tag' => $imgSrc, '%error' => $e->getMessage()]);
      \Drupal::logger('Migration')->error($msg);
      return '';
    }

    return $match[0];
  }

}
