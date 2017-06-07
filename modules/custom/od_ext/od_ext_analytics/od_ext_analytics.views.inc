<?php

/**
 * @file
 * Contains od_ext_analytics.views.inc.
 */

 /**
  * Implements hook_views_data().
  */
function od_ext_analytics_views_data() {
  $data['google_analytics']['ga_org_name'] = [
    'title' => t('Organization Name'),
    'group' => t('Custom variables or Columns'),
    'field' => [
      'title' => t('Organization Name'),
      'help' => t('Get the organization name that the current event belongs to.'),
      'id' => 'od_ext_analytics_field_org_name',
    ],
  ];

  $data['google_analytics']['ga_pkg_count'] = [
    'title' => t('Package Count'),
    'group' => t('Custom variables or Columns'),
    'field' => [
      'title' => t('Package Count'),
      'help' => t('Get the package count that the current event belongs to.'),
      'id' => 'od_ext_analytics_field_pkg_count',
    ],
  ];

  $data['google_analytics']['ga_record_name'] = [
    'title' => t('Record Name'),
    'group' => t('Custom variables or Columns'),
    'field' => [
      'title' => t('Record Name'),
      'help' => t('Get the Record Name that the current event belongs to.'),
      'id' => 'od_ext_analytics_field_rec_name',
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
