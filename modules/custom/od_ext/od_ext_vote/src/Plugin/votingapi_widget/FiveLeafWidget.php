<?php

namespace Drupal\od_ext_vote\Plugin\votingapi_widget;

use Drupal\votingapi_widgets\Plugin\VotingApiWidgetBase;

/**
 * Assigns ownership of a node to a user.
 *
 * @VotingApiWidget(
 *   id = "fiveleaf",
 *   label = @Translation("Fiveleaf rating"),
 *   values = {
 *    1 = @Translation("Poor"),
 *    2 = @Translation("Not so poor"),
 *    3 = @Translation("average"),
 *    4 = @Translation("good"),
 *    5 = @Translation("very good"),
 *   },
 * )
 */
class FiveLeafWidget extends VotingApiWidgetBase {

  /**
   * Vote form.
   */
  public function buildForm($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name, $style, $show_results, $read_only = FALSE) {
    $form = $this->getForm($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name, $style, $show_results, $read_only);
    $build = [
      'rating' => [
        '#theme' => 'container',
        '#attributes' => [
          'class' => [
            'votingapi-widgets',
            'fiveleaf',
            ($read_only) ? 'read_only' : '',
          ],
        ],
        '#children' => [
          'form' => $form,
        ],
      ],
      '#attached' => [
        'library' => [
          'od_ext_vote/fiveleaf',
        ],
      ],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyles() {
    return [
      'opengov-fiveleaf' => t('OpenGov Leafs'),
    ];
  }

}
