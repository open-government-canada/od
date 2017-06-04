<?php

namespace Drupal\od_ext_breadcrumb\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class ProactiveDisclosureBreadCrumbBuilder extends PathBasedBreadcrumbBuilder {

  use StringTranslationTrait;

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The inbound path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Site config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The language manager de.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Constructs the MainBreadCrumbBuilder.
   *
   * @param \Drupal\Core\Routing\RequestContext $context
   *   The router request context.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The menu link access service.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\PathValidator $pathValidator
   *   The path validator.
   */
  public function __construct(
    RequestContext $context,
    AccessManagerInterface $access_manager,
    RequestMatcherInterface $router,
    InboundPathProcessorInterface $path_processor,
    ConfigFactoryInterface $config_factory,
    TitleResolverInterface $title_resolver,
    AccountInterface $current_user,
    CurrentPathStack $current_path,
    LanguageManagerInterface $language_manager,
    PathValidator $pathValidator) {
    $this->context = $context;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->config = $config_factory->get('system.site');
    $this->titleResolver = $title_resolver;
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
    $this->languageManager = $language_manager;
    $this->pathValidator = $pathValidator;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $path = trim($this->context->getPathInfo(), '/');
    $path_elements = explode('/', $path);
    if (!empty($path_elements[0]) && $path_elements[0] == 'search') {
      if (!empty($path_elements[1])) {
        $core = [
          'ati',
          'contracts',
          'contractsa',
          'grants',
          'hospitalitya',
          'hospitalityq',
          'inventory',
          'reclassification',
          'travela',
          'travelq',
          'wrongdoing',
        ];
        if (in_array($path_elements[1], $core)) {
          return TRUE;
        }
      }
    }
    elseif (!empty($path_elements[0]) && $path_elements[0] == 'proactive-disclosure') {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $links = [];

    // General path-based breadcrumbs. Use the actual request path, prior to
    // resolving path aliases, so the breadcrumb can be defined by simply
    // creating a hierarchy of path aliases.
    $path = trim($this->context->getPathInfo(), '/');
    $path_elements = explode('/', $path);
    $exclude = [];

    // Add the url.path.parent cache context. This code ignores the last path
    // part so the result only depends on the path parents.
    $breadcrumb->addCacheContexts(['url.path.parent']);

    while (count($path_elements) > 1) {
      array_pop($path_elements);

      if (!empty($path_elements[0]) &&  empty($path_elements[1])) {
        $path_elements[0] = 'proactive-disclosure';
      }

      $route_request = $this->getRequestForPath('/' . implode('/', $path_elements), $exclude);
      if ($route_request) {
        $route_match = RouteMatch::createFromRequest($route_request);
        $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
        // The set of breadcrumb links depends on the access result, so merge
        // the access result's cacheability metadata.
        $breadcrumb = $breadcrumb->addCacheableDependency($access);
        if ($access->isAllowed()) {
          $title = $this->titleResolver->getTitle($route_request, $route_match->getRouteObject());
          if (!isset($title)) {
            // Fallback to using the raw path component as the title if the
            // route is missing a _title or _title_callback attribute.
            $title = str_replace(['-', '_'], ' ', Unicode::ucfirst(end($path_elements)));
          }
          $url = Url::fromRouteMatch($route_match);
          $links[] = new Link($title, $url);
        }
      }
    }

    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    $breadcrumb->setLinks(array_reverse($links));

    $route = $route_match->getRouteObject();
    if ($route && !$route->getOption('_admin_route')) {
      $links = $breadcrumb->getLinks();

      if (!empty($links) && $links[0]->getText() == $this->t('Home')) {
        $url = 'https://www.canada.ca';
        if ($this->languageManager->getCurrentLanguage()->getId() == 'fr') {
          $url = 'https://www.canada.ca/fr';
        }
        $link = array_shift($links);
        $link->setUrl(Url::fromUri($url));
        $open_info = $this->pathValidator->getUrlIfValid('open-information');
        if (!empty($open_info)) {
          $linkOpenGov = Link::createFromRoute($this->t('Open Government'), '<front>');
          $linkOpenInfo = Link::createFromRoute($this->t('Open Information'), $open_info->getRouteName(), $open_info->getRouteParameters());
          array_unshift($links, $link, $linkOpenGov, $linkOpenInfo);
        }
      }

      $breadcrumb = new Breadcrumb();
      $breadcrumb->addCacheContexts(['url.path.parent']);
      $breadcrumb->setLinks($links);
    }

    return $breadcrumb;
  }

}
