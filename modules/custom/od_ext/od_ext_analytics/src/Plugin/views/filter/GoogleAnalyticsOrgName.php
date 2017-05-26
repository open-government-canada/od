<?php

namespace Drupal\od_ext_analytics\Plugin\views\filter;

/**
 * @file
 * Contains \Drupal\od_ext_analytics\Plugin\views\filter\GoogleAnalyticsOrgName.
 */

use Drupal\google_analytics_reports\Plugin\views\filter\GoogleAnalyticsString;

/**
 * Basic textfield filter to handle string filtering commands.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("google_analytics_org_name")
 */
class GoogleAnalyticsOrgName extends GoogleAnalyticsString {

  /**
   * Helper function to get uuid of organization_name.
   */
  private function getOrganization($group, $field, $org_name, $operator) {
    $uri = 'http://open.canada.ca/data/api/action/organization_list?all_fields=true';
    try {
      $response = \Drupal::httpClient()->get($uri, ['headers' => ['Accept' => 'text/plain']]);
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
      foreach ($data as $org) {
        if (strstr($org['title'], $org_name)) {
          $uuid[] = $org['id'];
        }
      }
    }

    if (count($org_name) > 1) {
      // Need another way to handle multiple records.
      foreach ($uuid as $id) {
        $this->query->addWhere($group, $field, $id, $operator);
      }
    }
    elseif (count($org_name) == 1) {
      $this->query->addWhere($group, $field, $uuid[0], $operator);
    }
  }

  /**
   * Operation Equality.
   *
   * @param string $field
   *   Field name.
   */
  public function opEqual($field) {
    $this->getOrganization($this->options['group'], $field, $this->value, '==');
  }

  /**
   * Operation non-equality.
   *
   * @param string $field
   *   Field name.
   */
  public function opInequal($field) {
    $this->getOrganization($this->options['group'], $field, $this->value, '!=');
  }

  /**
   * Operation contains.
   *
   * @param string $field
   *   Field name.
   */
  public function opContains($field) {
    $this->getOrganization($this->options['group'], $field, $this->value, '=@');
  }

  /**
   * Operation not.
   *
   * @param string $field
   *   Field name.
   */
  public function opNot($field) {
    $this->getOrganization($this->options['group'], $field, $this->value, '!@');
  }

  /**
   * Operation regex match.
   *
   * @param string $field
   *   Field name.
   */
  public function opRegex($field) {
    $this->getOrganization($this->options['group'], $field, $this->value, '=~');
  }

  /**
   * Operation regex not match.
   *
   * @param string $field
   *   Field name.
   */
  public function opNotRegex($field) {
    $this->getOrganization($this->options['group'], $field, $this->value, '!~');
  }

}
