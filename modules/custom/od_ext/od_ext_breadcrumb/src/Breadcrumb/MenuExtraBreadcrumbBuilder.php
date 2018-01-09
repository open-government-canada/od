<?php

namespace Drupal\od_ext_breadcrumb\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\menu_breadcrumb\MenuBasedBreadcrumbBuilder;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Menu\MenuActiveTrail;

/**
 * {@inheritdoc}
 */
class MenuExtraBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * The configuration object generator.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The menu active trail interface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The admin context generator.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Menu Breadcrumbs configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The menu where the current page or taxonomy match has taken place.
   *
   * @var string
   */
  private $menuName;

  /**
   * The menu trail leading to this match.
   *
   * @var string
   */
  private $menuTrail;

  /**
   * Node of current path if taxonomy attached.
   *
   * @var \Drupal\node\Entity\Node
   */
  private $taxonomyAttachment;

  /**
   * Content language code (used in both applies() and build()).
   *
   * @var string
   */
  private $contentLanguage;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  private $lock;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MenuActiveTrailInterface $menu_active_trail,
    MenuLinkManagerInterface $menu_link_manager,
    AdminContext $admin_context,
    TitleResolverInterface $title_resolver,
    RequestStack $request_stack,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
  CacheBackendInterface $cache,
  LockBackendInterface $lock
  ) {
    $this->configFactory = $config_factory;
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuLinkManager = $menu_link_manager;
    $this->adminContext = $admin_context;
    $this->titleResolver = $title_resolver;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $this->configFactory->get('menu_breadcrumb.settings');
    $this->requestStack = $request_stack;
    $this->cache = $cache;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $parameters = $route_match->getParameters()->all();
    return ((!empty($parameters['node'])) && (is_object($parameters['node'])) && (count($this->menuActiveTrail->getActiveTrailIds('main')) == 1));
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $menu_breadcrumb = new MenuBasedBreadcrumbBuilder($this->configFactory, $this->menuActiveTrail, $this->menuLinkManager, $this->adminContext, $this->titleResolver, $this->requestStack, $this->languageManager, $this->entityTypeManager, $this->config);
    $menu_breadcrumb->applies($route_match);
    $menu_breadcrumb_object = $menu_breadcrumb->build($route_match);
    $links = $menu_breadcrumb_object->getLinks();
    $breadcrumb = new Breadcrumb();

    array_unshift($links, Link::createFromRoute($this->t('Open Dialogue'), 'entity.node.canonical', ['node' => "887"], ['absolute' => TRUE, 'attributes' => ['class' => 'this-class']]));
    array_unshift($links, Link::createFromRoute($this->t('Open Government'), 'entity.node.canonical', ['node' => "885"], ['absolute' => TRUE, 'attributes' => ['class' => 'this-class']]));
    $homeLink = Link::createFromRoute($this->t('Home'), '<front>');
    $url = 'https://www.canada.ca/en.html';
    if ($this->languageManager->getCurrentLanguage()->getId() == 'fr') {
      $url = 'https://www.canada.ca/fr.html';
    }
    $homeLink->setUrl(Url::fromUri($url));
    array_unshift($links, $homeLink);
    $breadcrumb->setLinks($links);

    return $breadcrumb;
  }

}
