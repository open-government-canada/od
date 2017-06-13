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
    $row->setSourceProperty('user_roles', $user_roles);

    return parent::prepareRow($row);
  }

}
