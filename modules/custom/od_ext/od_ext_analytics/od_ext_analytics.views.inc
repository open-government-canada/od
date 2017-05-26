<?php

/**
 * @file
 * Contains od_ext_analytics.views.inc.
 */

/**
 * Implements hook_views_data_alter().
 */
function od_ext_analytics_views_data() {
  $data['google_analytics']['organization_name'] = [
    'title' => t('Organization Name'),
    'filter' => [
      'title' => t('Organization Name'),
      'help' => t('Specify a list of titles analytics can use to filter by Organization Name.'),
      'field' => 'dimension3',
      'id' => 'od_ext_analytics_organization_name',
    ],
  ];
  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function od_ext_analytics_views_data_alter(array &$data) {
  $data['google_analytics']['dimension3']['filter']['id'] = 'google_analytics_org_name';
}
