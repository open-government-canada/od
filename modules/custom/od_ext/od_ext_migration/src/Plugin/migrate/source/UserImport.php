<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for user_import content.
 *
 * @MigrateSource(
 *   id = "user_import"
 * )
 */
class UserImport extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('users', 'u')
      ->fields('u',
      [
        'uid',
        'name',
        'pass',
        'mail',
        'created',
        'access',
        'login',
        'status',
        'timezone',
        'language',
        'init',
        'uuid',
      ]
    );

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'uid' => $this->t('User ID'),
      'name' => $this->t('Name'),
      'pass' => $this->t('Pass'),
      'mail' => $this->t('Mail'),
      'created' => $this->t('Created'),
      'access' => $this->t('Access'),
      'login' => $this->t('Login'),
      'status' => $this->t('Status'),
      'timezone' => $this->t('Timezone'),
      'language' => $this->t('Language'),
      'init' => $this->t('Init'),
      'uuid' => $this->t('UUID'),
      'country_code' => $this->t('Country code'),
      'administrative_area' => $this->t('Administrative area'),
      'locality' => $this->t('Locality'),
      'dependent_locality' => $this->t('Dependent locality'),
      'postal_code' => $this->t('Postal code'),
      'sorting_code' => $this->t('Sorting code'),
      'address_line1' => $this->t('Address Line 1'),
      'address_line2' => $this->t('Address Line 2'),
      'organization' => $this->t('Organization'),
      'given_name' => $this->t('Given name'),
      'additional_name' => $this->t('Additional name'),
      'family_name' => $this->t('Family name'),
      'user_roles' => $this->t('User Roles'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'alias' => 'u',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Address.
    $address = $this->select('field_data_field_address1', 'db')
      ->fields('db',
      [
        'field_address1_country',
        'field_address1_administrative_area',
        'field_address1_sub_administrative_area',
        'field_address1_locality',
        'field_address1_dependent_locality',
        'field_address1_postal_code',
        'field_address1_thoroughfare',
        'field_address1_premise',
        'field_address1_sub_premise',
        'field_address1_organisation_name',
        'field_address1_name_line',
        'field_address1_first_name',
        'field_address1_last_name',
      ])
      ->condition('entity_id', $row->getSourceProperty('uid'))
      ->condition('revision_id', $row->getSourceProperty('uid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->execute()
      ->fetchAssoc();

    $user_roles = $this->select('users_roles', 'db')
      ->fields('db', ['rid'])
      ->condition('uid', $row->getSourceProperty('uid'))
      ->execute()
      ->fetchAllAssoc('rid');

    $subscribe_message = $this->select('field_data_opendata_message_subscribe', 'df')
      ->fields('df', ['opendata_message_subscribe_value'])
      ->condition('entity_id', $row->getSourceProperty('uid'))
      ->condition('revision_id', $row->getSourceProperty('uid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'user')
      ->execute()
      ->fetchCol();

    $subscribe_updates = $this->select('field_data_field_subscribe_updates', 'df')
      ->fields('df', ['field_subscribe_updates_value'])
      ->condition('entity_id', $row->getSourceProperty('uid'))
      ->condition('revision_id', $row->getSourceProperty('uid'))
      ->condition('language', $row->getSourceProperty('language'))
      ->condition('bundle', 'user')
      ->execute()
      ->fetchCol();

    $tmp_roles = [];
    if (!empty($user_roles)) {
      foreach ($user_roles as $user_role) {
        $role = 'anonymous';
        switch ($user_role['rid']) {
          case 1:
            // Role: anonymous => anonymous @ site wide.
            $role = 'anonymous';
            break;

          case 2:
            // Role: authenticated => authenticated @ site wide.
            $role = 'authenticated';
            break;

          case 3:
            // Role: administrator => administrator @ site wide.
            $role = 'administrator';
            break;

          case 4:
            // Role: tbs_editor => editor @ site wide.
            // Role: tbs_editor => department-tbs_editor @ groups.
            $role = 'editor';
            break;

          case 5:
            // Role: web_content_manager => creator @ site wide.
            // Role: web_content_manager => dept-web_content_manager @ groups.
            $role = 'creator';
            break;

          case 6:
            // Role: blog_editor => creator @ site wide.
            // Role: blog_editor => department-web_content_manager @ groups.
            $role = 'creator';
            break;

          case 7:
            // Role: comment_moderator => comment_moderator @ site wide.
            $role = 'comment_moderator';
            break;

          case 8:
            // Role: unverified_user => anomymous @ site wide.
            // Note: legacy db used logintoboggan for this role.
            $role = 'authenticated';
            break;

          case 9:
            // Role: content_reviewer => reviewer @ site wide.
            // Role: content_reviewer => department-content_reviewer @ groups.
            $role = 'reviewer';
            break;

          case 10:
            // Role: user_admin_subscriptions => authenticated @ site wide.
            // Note: legacy db only had 1 user.
            $role = 'authenticated';
            break;

          case 11:
            // Role: tbs_moderator => authenticated @ site wide.
            // Note: legacy db only had 1 user.
            $role = 'authenticated';
            break;
        }

        $tmp_roles[] = $role;
      }
    }

    $row->setSourceProperty('country_code', $address['field_address1_country']);
    $row->setSourceProperty('administrative_area', $address['field_address1_administrative_area']);
    $row->setSourceProperty('locality', $address['field_address1_locality']);
    $row->setSourceProperty('dependent_locality', $address['field_address1_dependent_locality']);
    $row->setSourceProperty('postal_code', $address['field_address1_postal_code']);
    $row->setSourceProperty('sorting_code', '');
    $row->setSourceProperty('address_line1', $address['field_address1_thoroughfare']);
    $row->setSourceProperty('address_line2', $address['field_address1_premise']);
    $row->setSourceProperty('organization', $address['field_address1_organisation_name']);
    $row->setSourceProperty('given_name', $address['field_address1_first_name']);
    $row->setSourceProperty('additional_name', '');
    $row->setSourceProperty('family_name', $address['field_address1_last_name']);
    $row->setSourceProperty('user_roles', $tmp_roles);
    $row->setSourceProperty('subscribe_message', $subscribe_message[0]);
    $row->setSourceProperty('subscribe_updates', $subscribe_updates[0]);

    return parent::prepareRow($row);
  }

}
