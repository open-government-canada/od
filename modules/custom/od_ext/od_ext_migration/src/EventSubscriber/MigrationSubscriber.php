<?php

namespace Drupal\od_ext_migration\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\flag\FlagServiceInterface;
use Drupal\media_entity\Entity\Media;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\panelizer\PanelizerInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * WxT mode subscriber for controller requests.
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * The database object.
   *
   * @var object
   */
  protected $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The session manager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $invalidator;

  /**
   * The Panelizer service.
   *
   * @var \Drupal\panelizer\PanelizerInterface
   */
  protected $panelizer;

  /**
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * The path alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * Constructs a new MigrationSubscriber.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager service.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The session.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   UUID service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator
   *   The cache tag invalidator.
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   The tempstore factory.
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   The path alias storage.
   */
  public function __construct(Connection $database,
                              EntityManagerInterface $entity_manager,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory,
                              SessionManagerInterface $session_manager,
                              Session $session,
                              AccountInterface $current_user,
                              FlagServiceInterface $flag_service,
                              UuidInterface $uuid_service,
                              CacheTagsInvalidatorInterface $invalidator,
                              PanelizerInterface $panelizer,
                              SharedTempStoreFactory $tempstore,
                              AliasStorageInterface $alias_storage) {
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory;
    $this->sessionManager = $session_manager;
    $this->session = $session;
    $this->currentUser = $current_user;
    $this->flagService = $flag_service;
    $this->uuidService = $uuid_service;
    $this->invalidator = $invalidator;
    $this->panelizer = $panelizer;
    $this->tempstore = $tempstore;
    $this->aliasStorage = $alias_storage;
  }

  /**
   * Code to run after a migration has been imported.
   */
  public function onMigrationPostImport(MigrateImportEvent $event) {

    // Block logic for panelizer assignment.
    if ($event->getMigration()->id() == 'od_ext_block_basic') {
      $content_types = [
        'suggested_dataset' => [
          'region' => 'top_left',
          'weight' => 0,
        ],
      ];
      foreach ($content_types as $type => $value) {
        $uuid = $this->uuidService;
        $uuid = $uuid->generate();
        $displays = $this->panelizer->getDefaultPanelsDisplays('node', $type, 'default');
        $display = $displays['default'];
        $display->addBlock([
          'id' => 'system_menu_block:account',
          'label' => 'User account menu',
          'provider' => 'system',
          'label_display' => '0',
          'level' => 1,
          'depth' => 0,
          'region' => 'top_left',
          'weight' => 0,
          'uuid' => $uuid,
          'context_mapping' => [],
        ]);
        $this->panelizer->setDefaultPanelsDisplay('default', 'node', $type, 'default', $display);
        $this->panelizer->setDisplayStaticContexts('default', 'node', $type, 'default', []);
        $this->invalidator->invalidateTags(["panelizer_default:node:{$type}:default:default"]);
        $this->tempstore->get('panelizer.wizard')->delete("node__{$type}__default__default");
        $this->tempstore->get('panels_ipe')->delete("node__{$type}__default__default");
      }
    }

    // Commitment logic for menu creation.
    if ($event->getMigration()->id() == 'od_ext_node_commitment' ||
        $event->getMigration()->id() == 'od_ext_node_commitment_translation') {

      $table = ($event->getMigration()->id() == 'od_ext_node_commitment_translation') ? 'migrate_map_od_ext_db_node_commitment_translation' : 'migrate_map_od_ext_db_node_commitment';
      $translations = ($event->getMigration()->id() == 'od_ext_node_commitment_translation') ? TRUE : FALSE;
      $results = $this->database->select($table, 'et')
        ->fields('et')
        ->execute()
        ->fetchAllAssoc('destid1');

      $titleEn = "Mid-Term Self-Assessment Report on Canada's Action Plan on Open Government 2014-16";
      $titleFr = "Rapport d’auto-évaluation à mi-parcours du Plan d’action pour un gouvernement ouvert 2014-2016";
      foreach ($results as $result) {
        $links = $this->entityTypeManager->getStorage('menu_link_content')
          ->loadByProperties(['title' => (!empty($translations)) ? $titleFr : $titleEn]);
        if ($link = end($links)) {
          $links = $this->entityTypeManager->getStorage('menu_link_content')
            ->loadByProperties(['parent' => $link->getPluginId()]);
          $count = count($links);
          $node = $this->entityTypeManager->getStorage('node')
            ->load($result->destid1);
          $translation = $node->getTranslation((!empty($translations)) ? 'fr' : 'en');
          $menu_link_content = $this->entityManager->getStorage('menu_link_content')->create([
            'title' => $translation->getTitle(),
            'link' => ['uri' => 'internal:/node/' . $result->destid1],
            'menu_name' => (!empty($translations)) ? 'main_fr' : 'main',
            'langcode' => (!empty($translations)) ? 'fr' : 'en',
            'parent' => $link->getPluginId(),
            'weight' => $count,
          ]);
          $menu_link_content->save();
          $this->database->update('menu_link_content_data')
            ->fields(['link__uri' => 'entity:node/' . $result->destid1])
            ->condition('id', $menu_link_content->id())
            ->execute();
        }
      }
    }

    // Landing Page logic.
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
  public function onMigrationPreRowSave(MigratePreRowSaveEvent $event) {

    // Webform Submissions logic to convert tid.
    if ($event->getMigration()->id() == 'od_ext_db_webform_submissions') {
      $data = $event->getRow()->getSourceProperty('webform_data');
      $id = $event->getRow()->getSourceProperty('webform_id');

      if ($id == 'receive_email' && !empty($data['sectors'])) {
        $sectors = explode('_', $data['sectors']);
        if (!empty($sectors[1])) {
          $results = $this->database->select('migrate_map_od_ext_db_taxonomy_term', 'et')
            ->fields('et')
            ->condition('sourceid1', $sectors[1])
            ->execute()
            ->fetchAssoc('destid1');

          $data['sectors'] = $results['destid1'];
          $event->getRow()->setDestinationProperty('data', $data);
        }
      }

      if ($id == 'submit_app' && !empty($data['language'])) {
        $languages = $data['language'];
        if (!empty($languages)) {
          foreach ($languages as $key => $language) {
            $data['language'][$key] = ucwords($language);
          }
          $event->getRow()->setDestinationProperty('data', $data);
        }
      }

      if ($id == 'ati_records' && !empty($data['consent'])) {
        $consent = $data['consent'];
        if (!empty($consent)) {
          foreach ($consent as $key => $value) {
            $data['consent'] = ucwords($value);
          }
          $event->getRow()->setDestinationProperty('data', $data);
        }
      }

      if ($id == 'ati_records') {
        $data['address_fieldset']['address'];
        if (!empty($data['street_address'])) {
          $data['address_fieldset']['address'] .= $data['street_address'];
          unset($data['street_address']);
        }
        if (!empty($data['street_name'])) {
          $data['address_fieldset']['address'] .= ' ' . $data['street_name'];
          unset($data['street_name']);
        }
        if (!empty($data['apartment_suite_unit_number'])) {
          $data['address_fieldset']['address'] .= ' ' . $data['apartment_suite_unit_number'];
          unset($data['apartment_suite_unit_number']);
        }

        $data['address_fieldset']['address_2'];
        if (!empty($data['post_office_box'])) {
          $data['address_fieldset']['address_2'] .= $data['post_office_box'];
          unset($data['post_office_box']);
        }
        if (!empty($data['other_region'])) {
          $data['address_fieldset']['address_2'] .= ' ' . $data['other_region'];
          unset($data['other_region']);
        }

        $data['address_fieldset']['city'];
        if (!empty($data['city'])) {
          $data['address_fieldset']['city'] .= $data['city'];
          unset($data['city']);
        }

        $data['address_fieldset']['country'];
        if (!empty($data['country'])) {
          $data['address_fieldset']['country'] .= $data['country'];
          unset($data['country']);
        }

        $data['address_fieldset']['postal_code'];
        if (!empty($data['postal_code'])) {
          $data['address_fieldset']['postal_code'] .= $data['postal_code'];
          unset($data['postal_code']);
        }

        $data['address_fieldset']['state_province'];
        if (!empty($data['state_province'])) {
          $data['address_fieldset']['state_province'] .= $data['state_province'];
          unset($data['state_province']);
        }

        $event->getRow()->setDestinationProperty('data', $data);
      }

    }

  }

  /**
   * Code to run after a migration row has been saved.
   */
  public function onMigrationPostRowSave(MigratePostRowSaveEvent $event) {

    // Application logic to gather votes.
    if ($event->getMigration()->id() == 'od_ext_db_node_app') {
      $votes = $event->getRow()->getSourceProperty('votes');
      $destinationIds = $event->getDestinationIdValues();
      foreach ($votes as $vote) {
        $storage = $this->entityManager->getStorage('vote');
        $data = $vote * 0.05;
        $voteData = [
          'entity_type' => 'node',
          'entity_id'   => $destinationIds[0],
          'type'      => 'vote',
          'field_name'  => 'field_vote',
          'user_id' => 0,
        ];
        $vote = $storage->create($voteData);
        $vote->setValue($data);
        $vote->save();
      }
    }

    // Blog logic for group assignment.
    if ($event->getMigration()->id() == 'od_ext_db_node_blog') {
      $entities = $this->entityTypeManager->getStorage('group')->loadByProperties(['field_shortcode' => 'tbs-sct']);
      $entity = reset($entities);
      $destinationIds = $event->getDestinationIdValues();
      $node = $this->entityTypeManager->getStorage('node')->load($destinationIds[0]);
      $entity->addContent($node, 'group_node:blog_post');
    }

    // User logic for group assignment.
    if ($event->getMigration()->id() == 'od_ext_db_user') {
      $entities = $this->entityTypeManager->getStorage('group')->loadByProperties(['field_shortcode' => 'tbs-sct']);
      $entity = reset($entities);
      $destinationIds = $event->getDestinationIdValues();
      $user = $this->entityTypeManager->getStorage('user')->load($destinationIds[0]);
      $group_roles['administrator'] = 'department-tbs_editor';
      $group_roles['creator'] = 'department-web_content_manager';
      $group_roles['reviewer'] = 'department-content_reviewer';
      $options_list = [];
      if (count($event->getRow()->getSourceProperty('user_roles')) > 0) {
        $source_roles = array_values($event->getRow()->getSourceProperty('user_roles'));
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

    // Consultation logic for group assignment.
    if ($event->getMigration()->id() == 'od_ext_db_node_consultation') {
      $entities = $this->entityTypeManager->getStorage('group')->loadByProperties(['field_shortcode' => 'tbs-sct']);
      $entity = reset($entities);
      $destinationIds = $event->getDestinationIdValues();
      $node = $this->entityTypeManager->getStorage('node')->load($destinationIds[0]);
      $entity->addContent($node, 'group_node:consultation');
    }

    // Media logic for image link localization.
    if ($event->getMigration()->id() == 'od_ext_db_media_image') {
      $sourceMid = $event->getRow()->getSourceProperty('fid');
      $destMid = $event->getDestinationIdValues();
      $uri = '';
      $title = '';

      if (!empty($sourceMid)) {
        $media_image = Media::load($destMid[0]);

        switch ($sourceMid) {
          case 1558:
            $uri = 'http://open.canada.ca/en/blog/open-data-across-canada-snapshot';
            $title = 'Blog: Open Data Across Canada – A Snapshot';
            $uriFr = 'http://ouvert.canada.ca/fr/blogue/apercu-donnees-ouvertes-au-canada';
            $titleFr = 'Blogue : Données ouvertes au Canada : Un aperçu';
            break;

          case 1631:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/a2dd0554-03f8-4edc-a3b3-67b47c5c9d6d,9554ed18-6ab2-477f-9545-da091eba762f,636b9550-3700-4e66-8259-5cfc8159a784,40fbe40c-01cd-49d3-8add-0d20ed64c90d,490db619-ab58-4a2a-a245-2376ce1840de,57e7bc4c-680b-4640-9fa1-ded7ce186fab,e08eec16-7c7a-4253-9bee-ea640d400a54,e313db89-4219-4a5e-a543-772a86068710,82053097-6b4f-44d9-a750-549df44b36b4,35c51098-5cd8-44b0-b1cd-c8ce2f5dae89';
            $title = 'Energy infrastructure map';
            $uriFr = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/a2dd0554-03f8-4edc-a3b3-67b47c5c9d6d,9554ed18-6ab2-477f-9545-da091eba762f,636b9550-3700-4e66-8259-5cfc8159a784,40fbe40c-01cd-49d3-8add-0d20ed64c90d,490db619-ab58-4a2a-a245-2376ce1840de,57e7bc4c-680b-4640-9fa1-ded7ce186fab,e08eec16-7c7a-4253-9bee-ea640d400a54,e313db89-4219-4a5e-a543-772a86068710,82053097-6b4f-44d9-a750-549df44b36b4,35c51098-5cd8-44b0-b1cd-c8ce2f5dae89';
            $titleFr = 'Carte d’infrastructure énergétique';
            break;

          case 1633:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/a9f0b1c5-df3d-4aa4-8b03-2d9c860823da,7253103f-7bf5-4cea-9500-87a9d564e3f7';
            $title = 'Northern major projects map';
            $uriFr = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/a9f0b1c5-df3d-4aa4-8b03-2d9c860823da,7253103f-7bf5-4cea-9500-87a9d564e3f7';
            $titleFr = 'Carte des grands projets nordiques';
            break;

          case 1635:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/23eb8b56-dac8-4efc-be7c-b8fa11ba62e9,db177a8c-5d7d-49eb-8290-31e6a45d786c,a1e18963-25dd-4219-a33f-1a38c4971250,d2d6057f-d7c4-45d9-9fd9-0a58370577e0,32bf34ea-d51f-46c9-9945-563989dfcc7b';
            $title = 'Marine protected areas and species at risk map';
            $uriFr = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/23eb8b56-dac8-4efc-be7c-b8fa11ba62e9,db177a8c-5d7d-49eb-8290-31e6a45d786c,a1e18963-25dd-4219-a33f-1a38c4971250,d2d6057f-d7c4-45d9-9fd9-0a58370577e0,32bf34ea-d51f-46c9-9945-563989dfcc7b';
            $titleFr = 'Carte d’aires marines protégées et espèces en péril';
            break;

          case 1637:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/2bcf34b5-4e9a-431b-9e43-1eace6c873bd,73dbaaca-24b6-4c04-bd52-e7b7ab0c3d8f,b6567c5c-8339-4055-99fa-63f92114d9e4';
            $title = 'Indigenous communities and tribal councils map';
            $uriFr = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/2bcf34b5-4e9a-431b-9e43-1eace6c873bd,73dbaaca-24b6-4c04-bd52-e7b7ab0c3d8f,b6567c5c-8339-4055-99fa-63f92114d9e4';
            $titleFr = 'Carte des collectivités autochtones et conseils tribaux';
            break;

          case 1639:
            $uri = 'http://open.canada.ca/en/open-maps';
            $title = 'Open maps';
            $uriFr = 'http://ouvert.canada.ca/fr/cartes-ouvertes';
            $titleFr = 'Cartes ouvertes';
            break;

          case 1693:
            $uri = 'http://open.canada.ca/en/content/third-biennial-plan-open-government-partnership';
            $title = 'Third Biennial Plan to the Open Government Partnership (2016-18)';
            $uriFr = 'http://ouvert.canada.ca/fr/contenu/troisieme-plan-biannuel-partenariat-gouvernement-ouvert';
            $titleFr = 'Le troisième Plan biannuel dans le cadre du Partenariat pour un Gouvernement Ouvert (2016-18)';
            break;

          case 1723:
            $uri = 'http://open.canada.ca/en/maps/open-data-canada';
            $title = 'Open government across Canada';
            $uriFr = 'http://ouvert.canada.ca/fr/cartes/donnees-ouvertes-au-canada';
            $titleFr = 'Le gouvernement ouvert à travers le Canada';
            break;

          case 1881:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/305c8c89-8f3d-44db-ad26-9a4f540e06eb,7d42d280-ccca-4d7b-ba2b-2e1494cf1f4b';
            $title = 'Map of major natural resource projects in Canada';
            $uriFr = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/305c8c89-8f3d-44db-ad26-9a4f540e06eb,7d42d280-ccca-4d7b-ba2b-2e1494cf1f4b';
            $titleFr = 'Carte des grands projets de ressources naturelles au Canada';
            break;

          case 1899:
            $uri = 'http://open.canada.ca/en/content/third-biennial-plan-open-government-partnership';
            $title = 'Third Biennial Plan to the Open Government Partnership';
            $uriFr = 'http://open.canada.ca/fr/contenu/troisieme-plan-biannuel-partenariat-gouvernement-ouvert';
            $titleFr = 'Le troisième Plan biannuel dans le cadre du Partenariat pour un Gouvernement Ouvert';
            break;

          case 1900:
            $uri = 'http://www1.canada.ca/consultingcanadians/';
            $title = 'Explore all Government of Canada public consultations';
            $uriFr = 'http://www1.canada.ca/consultationdescanadiens/';
            $titleFr = 'Explorez toutes les consultations publiques du gouvernement du Canada';
            break;

          case 1924:
            $uri = 'http://pilot.open.canada.ca/en/open-by-default-pilot';
            $title = 'Open by Default Pilot';
            $uriFr = 'http://pilot.open.canada.ca/fr/pilote-ouverture-par-defaut';
            $titleFr = 'Projet pilote « Ouvert par défaut »';
            break;

          case 1969:
            $uri = 'http://open.canada.ca/en/blog/open-data-inventory-help-us-prioritize-release-open-data-most-relevant-you';
            $title = 'Blog: Open Data Inventory';
            $uriFr = 'http://open.canada.ca/fr/blogue/communication-repertoire-donnees-ouvertes-aidez-nous-a-prioriser-donnees-ouvertes-plus';
            $titleFr = 'Blogue : Répertoire de données ouvertes?';
            break;

          case 1970:
            $uri = 'http://open.canada.ca/en/content/what-we-heard-summary-report-open-government-consultations-march-31-july-15-2016';
            $title = 'What We Heard: Summary Report on Open Government Consultations';
            $uriFr = 'http://open.canada.ca/fr/contenu/ce-que-nous-avons-entendu-rapport-sommaire-consultations-gouvernement-ouvert-31-mars-15-juillet-2016';
            $titleFr = 'Ce que nous avons entendu : Rapport sommaire sur les consultations sur le gouvernement ouvert';
            break;

          case 2087:
            $uri = 'http://open.canada.ca/en/content/progress-tracker-third-biennial-plan-open-government-partnership';
            $title = 'Progress tracker for the Third Biennial Plan to the Open Government Partnership';
            $uriFr = 'http://open.canada.ca/fr/contenu/systeme-du-suivi-du-troisieme-plan-biannuel-cadre-du-partenariat-gouvernement-ouvert';
            $titleFr = 'Système du suivi du troisième Plan biannuel dans le cadre du Partenariat pour un gouvernement ouvert';
            break;
        }

        $media_image->field_image_link = [
          'uri' => $uri,
          'title' => $title,
        ];

        $media_image->addTranslation(
          'fr',
          [
            'field_image_link' => [
              'uri' => $uriFr,
              'title' => $titleFr,
            ],
            'image' => [
              'target_id' => $event->getRow()->getSourceProperty('fid'),
              'alt' => $event->getRow()->getSourceProperty('alt_fr'),
              'title' => $event->getRow()->getSourceProperty('title_fr'),
            ],
            'name' => $event->getRow()->getSourceProperty('filename'),
          ]
        );

        $media_image->save();
      }
    }

    if ($event->getMigration()->id() == 'od_ext_db_node_idea' ||
        $event->getMigration()->id() == 'od_ext_db_node_suggested_app' ||
        $event->getMigration()->id() == 'od_ext_db_node_suggested_dataset') {
      $votes = $event->getRow()->getSourceProperty('likes');
      $destinationIds = $event->getDestinationIdValues();
      foreach ($votes as $vote) {
        $storage = $this->entityManager->getStorage('vote');
        $data = $vote;
        $voteData = [
          'entity_type' => 'node',
          'entity_id'   => $destinationIds[0],
          'type'      => 'vote',
          'field_name'  => 'field_vud',
          'user_id' => 0,
        ];
        $vote = $storage->create($voteData);
        $vote->setValue($data);
        $vote->save();
      }
    }

    if ($event->getMigration()->id() == 'od_ext_db_node_page') {
      $sourceBid = $event->getRow()->getSourceProperty('nid');
      $contentType = $event->getRow()->getSourceProperty('content_type');
      $destBid = $event->getDestinationIdValues();

      if (!empty($contentType) && $contentType == 'landing_page') {
        $storage = $this->entityManager->getStorage('node');
        $node = $storage->load($destBid[0]);
        $type = 'landing_page';
        $tmpDisplay = 'full';
        $this->panelizer->setPanelsDisplay($node, 'full', 'two_column');
      }
    }

    // Block logic for panelizer / blocks assignment.
    if ($event->getMigration()->id() == 'od_ext_block_basic') {
      $sourceBid = $event->getRow()->getSourceProperty('bid');
      $destBid = $event->getDestinationIdValues();
      if (!empty($sourceBid)) {
        switch ($sourceBid) {
          case 'open_data':
          case 'open_dialogue':
          case 'open_info':
          case 'about_open_gov':
            $this->entityQueueCreate('pillars', $destBid);
            break;

          case 'feedback_form':
            $block_content = $this->entityTypeManager->getStorage('block_content')->load($destBid[0]);
            $block = $this->entityManager->getStorage('block')->create([
              'id' => 'feedback',
              'plugin' => 'block_content:' . $block_content->uuid(),
              'region' => 'content_footer',
              'provider' => 'block_content',
              'weight' => -11,
              'theme' => $this->config->get('system.theme')->get('default'),
              'visibility' => [],
              'settings' => [
                'label' => 'Feedback',
                'label_display' => FALSE,
              ],
              'third_party_settings' => [
                'block_class' => [
                  'classes' => 'col-sm-4 col-xs-6 mrgn-tp-lg',
                ],
              ],
            ]);
            $block->save();
            break;

          case 'form_privacy_statement':
            $block_content = $this->entityTypeManager->getStorage('block_content')->load($destBid[0]);
            $block = $this->entityManager->getStorage('block')->create([
              'id' => 'form_privacy_statement',
              'plugin' => 'block_content:' . $block_content->uuid(),
              'region' => 'content_footer',
              'provider' => 'block_content',
              'weight' => -99,
              'theme' => $this->config->get('system.theme')->get('default'),
              'visibility' => [
                'node_type' => [
                  'id' => 'node_type',
                  'bundles' => [
                    'webform' => 'webform',
                  ],
                  'context_mapping' => [
                    'node' => '@node.node_route_context:node',
                  ],
                ],
              ],
              'settings' => [
                'label' => 'Privacy Statement',
                'label_display' => FALSE,
              ],
              'third_party_settings' => [
                'block_class' => [
                  'classes' => 'col-md-12 mrgn-tp-lg',
                ],
              ],
            ]);
            $block->save();
            break;

          case 'consultation_closed':
            $content_types = [
              'consultation' => [
                'display' => 'default',
                'region' => 'content',
                'weight' => -2,
              ],
            ];
            foreach ($content_types as $type => $value) {
              $uuid = $this->uuidService;
              $uuid = $uuid->generate();
              $block_content = $this->entityTypeManager->getStorage('block_content')->load($destBid[0]);
              $tmpDisplay = $value['display'];
              $displays = $this->panelizer->getDefaultPanelsDisplays('node', $type, $tmpDisplay);
              $display = $displays[$tmpDisplay];
              $display->addBlock([
                'id' => 'block_content:' . $block_content->uuid(),
                'label' => 'Consultation closed',
                'provider' => 'block_content',
                'label_display' => 0,
                'status' => 1,
                'info' => '',
                'view_mode' => 'full',
                'region' => $value['region'],
                'weight' => $value['weight'],
                'uuid' => $uuid,
                'context_mapping' => [],
              ]);
              $this->panelizer->setDefaultPanelsDisplay('default', 'node', $type, $tmpDisplay, $display);
              $this->panelizer->setDisplayStaticContexts('default', 'node', $type, $tmpDisplay, []);
              $this->invalidator->invalidateTags(["panelizer_default:node:{$type}:{$tmpDisplay}:default"]);
              $this->tempstore->get('panelizer.wizard')->delete("node__{$type}__{$tmpDisplay}__default");
              $this->tempstore->get('panels_ipe')->delete("node__{$type}__{$tmpDisplay}__default");
            }
            break;

          case 'pillars':
            $content_types = [
              'blog_post' => [
                'display' => 'default',
                'region' => 'left',
                'weight' => -5,
              ],
              'consultation' => [
                'display' => 'default',
                'region' => 'content',
                'weight' => -3,
              ],
              'page' => [
                'display' => 'default',
                'region' => 'content',
                'weight' => 1,
              ],
              'suggested_dataset' => [
                'display' => 'default',
                'region' => 'top_right',
                'weight' => -2,
              ],
            ];
            foreach ($content_types as $type => $value) {
              $uuid = $this->uuidService;
              $uuid = $uuid->generate();
              $block_content = $this->entityTypeManager->getStorage('block_content')->load($destBid[0]);
              $tmpDisplay = $value['display'];
              $displays = $this->panelizer->getDefaultPanelsDisplays('node', $type, $tmpDisplay);
              $display = $displays[$tmpDisplay];
              $display->addBlock([
                'id' => 'block_content:' . $block_content->uuid(),
                'label' => 'Pillars',
                'provider' => 'block_content',
                'label_display' => 0,
                'status' => 1,
                'info' => '',
                'view_mode' => 'full',
                'region' => $value['region'],
                'weight' => $value['weight'],
                'uuid' => $uuid,
                'context_mapping' => [],
              ]);
              $this->panelizer->setDefaultPanelsDisplay('default', 'node', $type, $tmpDisplay, $display);
              $this->panelizer->setDisplayStaticContexts('default', 'node', $type, $tmpDisplay, []);
              $this->invalidator->invalidateTags(["panelizer_default:node:{$type}:{$tmpDisplay}:default"]);
              $this->tempstore->get('panelizer.wizard')->delete("node__{$type}__{$tmpDisplay}__default");
              $this->tempstore->get('panels_ipe')->delete("node__{$type}__{$tmpDisplay}__default");
            }

            $block_content = $this->entityTypeManager->getStorage('block_content')->load($destBid[0]);
            $block = $this->entityManager->getStorage('block')->create([
              'id' => 'pillars',
              'plugin' => 'block_content:' . $block_content->uuid(),
              'region' => 'header',
              'provider' => 'block_content',
              'weight' => -5,
              'theme' => $this->config->get('system.theme')->get('default'),
              'visibility' => [
                'node_type' => [
                  'id' => 'node_type',
                  'bundles' => [
                    'webform' => 'webform',
                  ],
                  'context_mapping' => [
                    'node' => '@node.node_route_context:node',
                  ],
                ],
              ],
              'settings' => [
                'label' => 'Pillars',
                'label_display' => FALSE,
              ],
              'third_party_settings' => [
                'block_class' => [
                  'classes' => '',
                ],
              ],
            ]);
            $block->save();
            break;

          case 'user_registration':
            $block_content = $this->entityTypeManager->getStorage('block_content')->load($destBid[0]);
            $block = $this->entityManager->getStorage('block')->create([
              'id' => 'user_registration',
              'plugin' => 'block_content:' . $block_content->uuid(),
              'region' => 'header',
              'provider' => 'block_content',
              'weight' => -8,
              'theme' => $this->config->get('system.theme')->get('default'),
              'visibility' => [
                'request_path' => [
                  'id' => 'request_path',
                  'pages' => '/user/register',
                  'negate' => FALSE,
                  'context_mapping' => [],
                ],
              ],
              'settings' => [
                'label' => 'Registration Page',
                'label_display' => FALSE,
              ],
              'third_party_settings' => [
                'block_class' => [
                  'classes' => '',
                ],
              ],
            ]);
            $block->save();
            break;
        }
      }
    }

    // Block logic for queue assignment.
    if ($event->getMigration()->id() == 'od_ext_block_spotlight') {
      $sourceBid = $event->getRow()->getSourceProperty('bid');
      $destBid = $event->getDestinationIdValues();
      if (!empty($sourceBid)) {
        switch ($sourceBid) {
          case 'open_gov_across_canada':
          case 'open_maps':
          case 'open_gov_partnership':
            $this->entityQueueCreate('front_page', $destBid);
            break;
        }

        switch ($sourceBid) {
          case 'open_gov_partnership':
          case 'open_gov_receive_updates':
          case 'open_gov_across_canada':
            $this->entityQueueCreate('open_data', $destBid);
            break;
        }

        switch ($sourceBid) {
          case 'open_gov_across_canada':
          case 'open_gov_receive_updates':
          case 'open_gov_partnership':
            $this->entityQueueCreate('open_maps', $destBid);
            break;
        }

        switch ($sourceBid) {
          case 'open_gov_receive_updates':
          case 'open_gov_partnership':
          case 'open_gov_across_canada':
            $this->entityQueueCreate('access_info', $destBid);
            break;
        }
      }
    }

    if ($event->getMigration()->id() == 'od_ext_node_commitment' ||
        $event->getMigration()->id() == 'od_ext_node_commitment_translation') {
      $sourceBid = $event->getRow()->getSourceProperty('name');
      $title = $event->getRow()->getSourceProperty('title');
      $destBid = $event->getDestinationIdValues();
      $translations = $event->getRow()->getSourceProperty('translations');
      $menuName = $event->getRow()->getSourceProperty('menu_name');

      $titleEn = 'Commitments';
      $titleFr = 'Engagements';

      if (!empty($menuName)) {
        switch ($menuName) {
          case 'mtsar__2016_2018':
            $titleEn = 'Draft for Consultation: Mid-term Self-assessment on Third Biennial Plan to the Open Government Partnership (2016-2018)';
            $titleFr = 'Ébauche aux fins de consultation : Auto évaluation de mi parcours sur le troisième Plan biannuel dans le cadre du Partenariat pour un gouvernement ouvert (2016-2018)';
            break;

        }
      }

      $links = $this->entityTypeManager->getStorage('menu_link_content')
        ->loadByProperties(['title' => (!empty($translations)) ? $titleFr : $titleEn]);
      if ($link = reset($links)) {
        $links = $this->entityTypeManager->getStorage('menu_link_content')
          ->loadByProperties(['parent' => $link->getPluginId()]);
        $count = count($links);
        $this->menuLinkDependency($title, $link->getPluginId(), $translations, $destBid, $count);
      }
    }

    if ($event->getMigration()->id() == 'od_ext_node_landing_page' ||
        $event->getMigration()->id() == 'od_ext_node_landing_page_translation') {
      $sourceBid = $event->getRow()->getSourceProperty('name');
      $title = $event->getRow()->getSourceProperty('title');
      $destBid = $event->getDestinationIdValues();
      $translations = $event->getRow()->getSourceProperty('translations');

      if (!empty($sourceBid)) {
        switch ($sourceBid) {
          case 'commitments':
            $menu_link_content = [];
            $links = $this->entityTypeManager->getStorage('menu_link_content')
              ->loadByProperties(['title' => (!empty($translations)) ? 'Gouvernement ouvert' : 'Open Government']);
            if ($link = reset($links)) {
              $menu_link_content = $this->menuLinkDependency($title, $link->getPluginId(), $translations, $destBid);
            }

            $content_types = [
              'commitment' => [
                'display' => 'default',
                'region' => 'top_left',
                'weight' => 0,
              ],
            ];
            foreach ($content_types as $type => $value) {
              $uuid = $this->uuidService;
              $uuid = $uuid->generate();
              $tmpDisplay = $value['display'];
              $displays = $this->panelizer->getDefaultPanelsDisplays('node', $type, $tmpDisplay);
              $display = $displays[$tmpDisplay];
              $menu_name = (!empty($translations)) ? 'main_fr' : 'main';
              $display->addBlock([
                'id' => 'menu_block:' . $menu_name,
                'label' => 'Main navigation',
                'provider' => menu_block,
                'label_display' => visible,
                'level' => 1,
                'custom_level' => '1',
                'hide_children' => 0,
                'depth' => 0,
                'expand' => 1,
                'expand_only_active_trails' => 1,
                'parent' => $menu_name . ':menu_link_content:' . $menu_link_content->uuid(),
                'render_parent' => FALSE,
                'follow' => 1,
                'suggestion' => sidebar,
                'region' => top_left,
                'weight' => 0,
                'uuid' => $uuid,
                'context_mapping' => [],
              ]);
              $this->panelizer->setDefaultPanelsDisplay('default', 'node', $type, $tmpDisplay, $display);
              $this->panelizer->setDisplayStaticContexts('default', 'node', $type, $tmpDisplay, []);
              $this->invalidator->invalidateTags(["panelizer_default:node:{$type}:{$tmpDisplay}:default"]);
              $this->tempstore->get('panelizer.wizard')->delete("node__{$type}__{$tmpDisplay}__default");
              $this->tempstore->get('panels_ipe')->delete("node__{$type}__{$tmpDisplay}__default");
            }
            break;

          case 'contracts':
            if ($event->getMigration()->id() == 'od_ext_node_landing_page_translation') {
              $this->aliasStorage->save('/node/' . $destBid[0], '/search/contrats', 'fr');
            }
            break;

          case 'homepage':
            $this->menuLinkDependency($title, '', $translations, $destBid);
            break;

          case 'mtsar__2014_2016':
            $links = $this->entityTypeManager->getStorage('menu_link_content')
              ->loadByProperties(['title' => (!empty($translations)) ? 'Engagements' : 'Commitments']);
            if ($link = reset($links)) {
              $this->menuLinkDependency($title, $link->getPluginId(), $translations, $destBid);
            }
            break;

          case 'mtsar__2016_2018':
            $links = $this->entityTypeManager->getStorage('menu_link_content')
              ->loadByProperties(['title' => (!empty($translations)) ? 'Engagements' : 'Commitments']);
            if ($link = reset($links)) {
              $this->menuLinkDependency($title, $link->getPluginId(), $translations, $destBid);
            }
            break;

          case 'open_info':
            $links = $this->entityTypeManager->getStorage('menu_link_content')
              ->loadByProperties(['title' => (!empty($translations)) ? 'Gouvernement ouvert' : 'Open Government']);
            if ($link = reset($links)) {
              $this->menuLinkDependency($title, $link->getPluginId(), $translations, $destBid);
            }
            break;

          case 'open_by_default_pilot':
            $links = $this->entityTypeManager->getStorage('menu_link_content')
              ->loadByProperties(['title' => (!empty($translations)) ? 'Information ouverte' : 'Open Information']);
            if ($link = reset($links)) {
              $this->menuLinkDependency($title, $link->getPluginId(), $translations, $destBid);
            }
            break;

          case 'open_by_default_about':
            $links = $this->entityTypeManager->getStorage('menu_link_content')
              ->loadByProperties(['title' => (!empty($translations)) ? 'Projet pilote de l’« Ouverture par défaut »' : 'Open by Default Pilot']);
            if ($link = reset($links)) {
              $this->menuLinkDependency($title, $link->getPluginId(), $translations, $destBid);
            }
            break;
        }
      }
    }

    // Menu Link logic for menu assignment.
    if ($event->getMigration()->id() == 'od_ext_menu_link' ||
        $event->getMigration()->id() == 'od_ext_menu_link_translation') {
      $sourceBid = $event->getRow()->getSourceProperty('mlid');
      $title = $event->getRow()->getSourceProperty('link_title');
      $destBid = $event->getDestinationIdValues();
      $translations = $event->getRow()->getSourceProperty('translations');

      if (!empty($sourceBid)) {
        switch ($sourceBid) {
          case 'main_home':
            $links = $this->entityTypeManager->getStorage('menu_link_content')
              ->loadByProperties(['title' => (!empty($translations)) ? 'Gouvernement ouvert' : 'Open Government']);
            if ($link = end($links)) {
              $menu_link_content = $this->entityManager->getStorage('menu_link_content')->load($destBid[0]);
              $link->parent = $menu_link_content->getPluginId();
              $link->save();
            }
            break;
        }
      }
    }

    if ($event->getMigration()->id() == 'od_ext_db_taxonomy_term') {
      $name = $event->getRow()->getSourceProperty('translated_name');
      $description = $event->getRow()->getSourceProperty('translated_description');
      $destBid = $event->getDestinationIdValues();
      $storageTerm = $this->entityTypeManager->getStorage('taxonomy_term');

      if (!empty($name)) {
        $term = $storageTerm->load($destBid[0]);
        if ($term && !$term->hasTranslation('fr')) {
          $entity_array = $term->toArray();
          $translated_fields = [];
          $translated_fields['name'] = $name;
          $translated_fields['description'] = [
            'value' => $description,
            'format' => 'rich_text',
          ];
          $translated_entity_array = array_merge($entity_array, $translated_fields);
          $term->addTranslation('fr', $translated_entity_array)->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_ROW_SAVE] = 'onMigrationPreRowSave';
    $events[MigrateEvents::POST_ROW_SAVE] = 'onMigrationPostRowSave';
    $events[MigrateEvents::POST_IMPORT] = 'onMigrationPostImport';
    return $events;
  }

  /**
   * Add a specific entityqueue.
   */
  public function entityQueueCreate($queue, $destBid) {
    $entity_subqueue = $this->entityManager->getStorage('entity_subqueue')->load($queue);
    $items = $entity_subqueue->get('items')->getValue();
    $items[] = ['target_id' => $destBid[0]];
    $entity_subqueue->set('items', $items);
    $entity_subqueue->save();
  }

  /**
   * Add a menu link with dependency support.
   */
  public function menuLinkDependency($title, $link, $translations, $destBid, $weight = 0) {
    $menu_link_content = $this->entityManager->getStorage('menu_link_content')->create([
      'title' => $title,
      'link' => ['uri' => 'internal:/node/' . $destBid[0]],
      'menu_name' => (!empty($translations)) ? 'main_fr' : 'main',
      'langcode' => (!empty($translations)) ? 'fr' : 'en',
      'parent' => $link,
      'weight' => $weight,
    ]);
    $menu_link_content->save();
    $this->database->update('menu_link_content_data')
      ->fields(['link__uri' => 'entity:node/' . $destBid[0]])
      ->condition('id', $menu_link_content->id())
      ->execute();
    return $menu_link_content;
  }

}
