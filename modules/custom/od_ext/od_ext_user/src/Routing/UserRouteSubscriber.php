<?php

namespace Drupal\od_ext_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class UserRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change the title of the user registration page.
    if ($route = $collection->get('user.register')) {
      $route->setDefaults([
        '_entity_form' => 'user.register',
        '_title' => 'Registration Page',
      ]);
    }
  }

}
