<?php

namespace Drupal\od_ext_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for page example routes.
 */
class AnalyticsLandingPage extends ControllerBase {

  /**
   * Constructs a default landing page for the Calendar View.
   */
  public function analytics() {

    return new RedirectResponse('analytics/summary');
  }

}
