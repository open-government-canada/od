<?php

namespace Drupal\od_ext_analytics\Plugin\views\field;

/**
 * @file
 * Definition of Drupal\od_ext_analytics\Plugin\views\field\FieldOrgPkgCount.
 */

use Drupal\google_analytics_reports\Plugin\views\field\GoogleAnalyticsStandard;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("od_ext_analytics_field_pkg_count")
 */
class FieldOrgPkgCount extends GoogleAnalyticsStandard {

  /**
   * Query to run for this field.
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Render the organization name but selecting it from the remote enttiy.
   */
  public function render(ResultRow $values) {
    $uuid = $values->dimension3;
    $package_count = 0;
    $uri = 'http://open.canada.ca/data/api/3/action/organization_show?id=' . $uuid;

    try {
      $response = \Drupal::httpClient()->get($uri, ['headers' => ['Accept' => 'text/plain'], 'timeout' => 600]);
      $data = (string) $response->getBody();
      if (empty($data)) {
        return FALSE;
      }
    }
    catch (RequestException $e) {
      return FALSE;
    }

    $data = json_decode($data, TRUE);
    if (isset($data['result'])) {
      $data = $data['result'];
      $package_count = $data['package_count'];
    }
    return $package_count;
  }

}
