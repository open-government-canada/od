<?php

namespace Drupal\od_ext_migration\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\media_entity\Entity\Media;
use Symfony\Component\HttpFoundation\Session\Session;

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
   * Constructs a new MigrationSubscriber.
   *
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
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, SessionManagerInterface $session_manager, Session $session, AccountInterface $current_user, FlagServiceInterface $flag_service) {
    $this->entityManager = $entity_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory;
    $this->sessionManager = $session_manager;
    $this->session = $session;
    $this->currentUser = $current_user;
    $this->flagService = $flag_service;
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
            break;

          case 1631:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/a2dd0554-03f8-4edc-a3b3-67b47c5c9d6d,9554ed18-6ab2-477f-9545-da091eba762f,636b9550-3700-4e66-8259-5cfc8159a784,40fbe40c-01cd-49d3-8add-0d20ed64c90d,490db619-ab58-4a2a-a245-2376ce1840de,57e7bc4c-680b-4640-9fa1-ded7ce186fab,e08eec16-7c7a-4253-9bee-ea640d400a54,e313db89-4219-4a5e-a543-772a86068710,82053097-6b4f-44d9-a750-549df44b36b4,35c51098-5cd8-44b0-b1cd-c8ce2f5dae89';
            $title = 'Energy infrastructure map';
            break;

          case 1633:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/a9f0b1c5-df3d-4aa4-8b03-2d9c860823da,7253103f-7bf5-4cea-9500-87a9d564e3f7';
            $title = 'Northern major projects map';
            break;

          case 1635:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/23eb8b56-dac8-4efc-be7c-b8fa11ba62e9,db177a8c-5d7d-49eb-8290-31e6a45d786c,a1e18963-25dd-4219-a33f-1a38c4971250,d2d6057f-d7c4-45d9-9fd9-0a58370577e0,32bf34ea-d51f-46c9-9945-563989dfcc7b';
            $title = 'Marine protected areas and species at risk map';
            break;

          case 1637:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/2bcf34b5-4e9a-431b-9e43-1eace6c873bd,73dbaaca-24b6-4c04-bd52-e7b7ab0c3d8f,b6567c5c-8339-4055-99fa-63f92114d9e4';
            $title = 'Indigenous communities and tribal councils map';
            break;

          case 1639:
            $uri = 'http://open.canada.ca/en/open-maps';
            $title = 'Open maps';
            break;

          case 1693:
            $uri = 'http://open.canada.ca/en/content/third-biennial-plan-open-government-partnership';
            $title = 'Third Biennial Plan to the Open Government Partnership (2016-18)';
            break;

          case 1723:
            $uri = 'http://open.canada.ca/en/maps/open-data-canada';
            $title = 'Open government across Canada';
            break;

          case 1881:
            $uri = 'http://open.canada.ca/data/en/fgpv_vpgf/305c8c89-8f3d-44db-ad26-9a4f540e06eb,7d42d280-ccca-4d7b-ba2b-2e1494cf1f4b';
            $title = 'Map of major natural resource projects in Canada';
            break;

          case 1899:
            $uri = 'http://open.canada.ca/en/content/third-biennial-plan-open-government-partnership';
            $title = 'Third Biennial Plan to the Open Government Partnership';
            break;

          case 1900:
            $uri = 'http://www1.canada.ca/consultingcanadians/';
            $title = 'Explore all Government of Canada public consultations';
            break;

          case 1924:
            $uri = 'http://pilot.open.canada.ca/en/open-by-default-pilot';
            $title = 'Open by Default Pilot';
            break;

          case 1969:
            $uri = 'http://open.canada.ca/en/blog/open-data-inventory-help-us-prioritize-release-open-data-most-relevant-you';
            $title = 'Blog: Open Data Inventory';
            break;

          case 1970:
            $uri = 'http://open.canada.ca/en/content/what-we-heard-summary-report-open-government-consultations-march-31-july-15-2016';
            $title = 'What We Heard: Summary Report on Open Government Consultations';
            break;

          case 2087:
            $uri = 'http://open.canada.ca/en/content/progress-tracker-third-biennial-plan-open-government-partnership';
            $title = 'Progress tracker for the Third Biennial Plan to the Open Government Partnership';
            break;
        }

        $media_image->field_image_link = [
          'uri' => $uri,
          'title' => $title,
        ];

        switch ($sourceMid) {
          case 1558:
            $uri = 'http://ouvert.canada.ca/fr/blogue/apercu-donnees-ouvertes-au-canada';
            $title = 'Blogue : Données ouvertes au Canada : Un aperçu';
            break;

          case 1631:
            $uri = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/a2dd0554-03f8-4edc-a3b3-67b47c5c9d6d,9554ed18-6ab2-477f-9545-da091eba762f,636b9550-3700-4e66-8259-5cfc8159a784,40fbe40c-01cd-49d3-8add-0d20ed64c90d,490db619-ab58-4a2a-a245-2376ce1840de,57e7bc4c-680b-4640-9fa1-ded7ce186fab,e08eec16-7c7a-4253-9bee-ea640d400a54,e313db89-4219-4a5e-a543-772a86068710,82053097-6b4f-44d9-a750-549df44b36b4,35c51098-5cd8-44b0-b1cd-c8ce2f5dae89';
            $title = 'Carte d’infrastructure énergétique';
            break;

          case 1633:
            $uri = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/a9f0b1c5-df3d-4aa4-8b03-2d9c860823da,7253103f-7bf5-4cea-9500-87a9d564e3f7';
            $title = 'Carte des grands projets nordiques';
            break;

          case 1635:
            $uri = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/23eb8b56-dac8-4efc-be7c-b8fa11ba62e9,db177a8c-5d7d-49eb-8290-31e6a45d786c,a1e18963-25dd-4219-a33f-1a38c4971250,d2d6057f-d7c4-45d9-9fd9-0a58370577e0,32bf34ea-d51f-46c9-9945-563989dfcc7b';
            $title = 'Carte d’aires marines protégées et espèces en péril';
            break;

          case 1637:
            $uri = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/2bcf34b5-4e9a-431b-9e43-1eace6c873bd,73dbaaca-24b6-4c04-bd52-e7b7ab0c3d8f,b6567c5c-8339-4055-99fa-63f92114d9e4';
            $title = 'Carte des collectivités autochtones et conseils tribaux';
            break;

          case 1639:
            $uri = 'http://ouvert.canada.ca/fr/cartes-ouvertes';
            $title = 'Cartes ouvertes';
            break;

          case 1693:
            $uri = 'http://ouvert.canada.ca/fr/contenu/troisieme-plan-biannuel-partenariat-gouvernement-ouvert';
            $title = 'Le troisième Plan biannuel dans le cadre du Partenariat pour un Gouvernement Ouvert (2016-18)';
            break;

          case 1723:
            $uri = 'http://ouvert.canada.ca/fr/cartes/donnees-ouvertes-au-canada';
            $title = 'Le gouvernement ouvert à travers le Canada';
            break;

          case 1881:
            $uri = 'http://ouvert.canada.ca/data/fr/fgpv_vpgf/305c8c89-8f3d-44db-ad26-9a4f540e06eb,7d42d280-ccca-4d7b-ba2b-2e1494cf1f4b';
            $title = 'Carte des grands projets de ressources naturelles au Canada';
            break;

          case 1899:
            $uri = 'http://open.canada.ca/fr/contenu/troisieme-plan-biannuel-partenariat-gouvernement-ouvert';
            $title = 'Le troisième Plan biannuel dans le cadre du Partenariat pour un Gouvernement Ouvert';
            break;

          case 1900:
            $uri = 'http://www1.canada.ca/consultationdescanadiens/';
            $title = 'Explorez toutes les consultations publiques du gouvernement du Canada';
            break;

          case 1924:
            $uri = 'http://pilot.open.canada.ca/fr/pilote-ouverture-par-defaut';
            $title = 'Projet pilote « Ouvert par défaut »';
            break;

          case 1969:
            $uri = 'http://open.canada.ca/fr/blogue/communication-repertoire-donnees-ouvertes-aidez-nous-a-prioriser-donnees-ouvertes-plus';
            $title = 'Communication du répertoire de données ouvertes';
            break;

          case 1970:
            $uri = 'http://open.canada.ca/fr/contenu/ce-que-nous-avons-entendu-rapport-sommaire-consultations-gouvernement-ouvert-31-mars-15-juillet-2016';
            $title = 'Ce que nous avons entendu : Rapport sommaire sur les consultations sur le gouvernement ouvert';
            break;

          case 2087:
            $uri = 'http://open.canada.ca/fr/contenu/systeme-du-suivi-du-troisieme-plan-biannuel-cadre-du-partenariat-gouvernement-ouvert';
            $title = 'Système du suivi du troisième Plan biannuel dans le cadre du Partenariat pour un gouvernement ouvert';
            break;
        }

        $media_image->addTranslation(
          'fr',
          [
            'field_image_link' => [
              'uri' => $uri,
              'title' => $title,
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

    if ($event->getMigration()->id() == 'od_ext_db_node_suggested_app' ||
      $event->getMigration()->id() == 'od_ext_db_node_suggested_dataset') {
      $likes = $event->getRow()->getSourceProperty('likes');

      if (!empty($likes)) {
        $destinationIds = $event->getDestinationIdValues();
        $node_storage = $this->entityManager->getStorage('node');
        $node = $node_storage->load($destinationIds[0]);
        $account = $this->currentUser;
        $sessId = $this->session->getId();

        foreach ($likes as $like) {
          $flag = $this->flagService->getFlagById('like');
          $is_flagged = $flag->isFlagged($node, $account, $sessId);
          if (!$is_flagged && !empty($like)) {
            $this->flagService->flag($flag, $node, $account, $sessId);
          }
          $this->session->invalidate();
        }

      }
    }

    if ($event->getMigration()->id() == 'od_ext_block_basic') {
      $sourceBid = $event->getRow()->getSourceProperty('bid');
      $destBid = $event->getDestinationIdValues();
      if (!empty($sourceBid)) {
        switch ($sourceBid) {
          case 'open_data':
          case 'open_dialogue':
          case 'open_info':
          case 'about_open_gov':
            $entity_subqueue = $this->entityManager->getStorage('entity_subqueue')->load('pillars');
            $items = $entity_subqueue->get('items')->getValue();
            $items[] = ['target_id' => $destBid[0]];
            $entity_subqueue->set('items', $items);
            $entity_subqueue->save();
            break;
        }
      }
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
