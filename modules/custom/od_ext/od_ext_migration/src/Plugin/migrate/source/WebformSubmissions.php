<?php

namespace Drupal\od_ext_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for app content.
 *
 * @MigrateSource(
 *   id = "webform_submissions"
 * )
 */
class WebformSubmissions extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('webform_submissions', 'wfs');
    $query->fields('wfs', [
      'nid',
      'sid',
      'uid',
      'submitted',
      'remote_addr',
      'is_draft',
    ]);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Webform node Id'),
      'sid' => $this->t('Webform submission Id'),
      'uid' => $this->t('User Id of submitter'),
      'submitted' => $this->t('Submission timestamp'),
      'remote_addr' => $this->t('IP Address of submitter'),
      'is_draft' => $this->t('Whether this submission is draft'),
      'webform_id' => $this->t('Id to be used for Webform'),
      'webform_data' => $this->t('Webform submitted data'),
      'webform_uri' => $this->t('Submission uri'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $sid = $row->getSourceProperty('sid');
    $submitted_data = $this->buildSubmittedData($sid);
    $webform_id = $this->getWebformId($nid);
    $row->setSourceProperty('webform_id', $webform_id);
    $row->setSourceProperty('webform_data', $submitted_data);
    $row->setSourceProperty('webform_uri', '/form/' . $webform_id);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['sid']['type'] = 'integer';
    $ids['sid']['alias'] = 'wfs';
    return $ids;
  }

  /**
   * Helper function to get the local webform id.
   */
  private function getWebformId($nid) {
    $map = [
      14 => 'frequently_asked_questions',
      51 => 'suggest_dataset',
      52 => 'contact',
      53 => 'submit_app',
      287998 => 'app_ideas',
      381508 => 'suggest_idea',
      390716 => 'ati_records',
      390733 => 'suggest_open_information',
      390748 => 'receive_email',
      556613 => 'submit_event',
      564523 => 'suggest_idea_action_plan',
    ];
    if (isset($map[$nid])) {
      return $map[$nid];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Build submitted data from webform submitted data table.
   */
  private function buildSubmittedData($sid) {
    $query = $this->select('webform_submitted_data', 'wfsd');
    $query->innerJoin('webform_component', 'wc', 'wc.nid=wfsd.nid AND wc.cid=wfsd.cid');

    $query->fields('wfsd', [
      'no',
      'data',
    ])->fields('wc', [
      'form_key',
      'extra',
    ]);
    $wf_submissions = $query->condition('sid', $sid)->execute();

    $submitted_data = [];
    $multiple = '';
    foreach ($wf_submissions as $wf_submission) {
      $extra = unserialize($wf_submission['extra']);
      if (!empty($extra['multiple'])) {
        if ($wf_submission['form_key'] != $multiple) {
          $item = [];
        }
        $multiple = $wf_submission['form_key'];
        $item[$wf_submission['no']] = $wf_submission['data'];
      }
      else {
        $item = $wf_submission['data'];
      }

      switch ($wf_submission['form_key']) {
        case 'strongfederal_government_institution_that_publishes_the_dataset_strong':
          $wf_submission['form_key'] = 'federal_government_institution';
          break;

        case 'strongdescription_of_the_dataset_you_would_like_to_make_available_strong':
          $wf_submission['form_key'] = 'description_dataset';
          break;

        case 'postal_zip_code':
          $wf_submission['form_key'] = 'postal_code';
          break;

        case 'province_state':
          $wf_submission['form_key'] = 'state_province';
          break;
      }

      $submitted_data[$wf_submission['form_key']] = $item;
    }
    return $submitted_data;
  }

}
