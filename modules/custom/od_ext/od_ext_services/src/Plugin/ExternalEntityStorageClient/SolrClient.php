<?php

namespace Drupal\od_ext_services\Plugin\ExternalEntityStorageClient;

use Drupal\external_entities\ExternalEntityInterface;
use Drupal\external_entities\ExternalEntityStorageClientBase;

/**
 * Solr implementation of an external entity storage client.
 *
 * @ExternalEntityStorageClient(
 *   id = "solr_client",
 *   name = "Solr"
 * )
 */
class SolrClient extends ExternalEntityStorageClientBase {

  /**
   * The amount of Solr records.
   *
   * @var \Drupal\external_entities\ResponseDecoderFactoryInterface
   */
  protected $count;

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    return $this->count;
  }

  /**
   * {@inheritdoc}
   */
  public function setCount($count) {
    return $this->count = $count;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(ExternalEntityInterface $entity) {
    $this->httpClient->delete(
      $this->configuration['endpoint'] . '/' . $entity->externalId(),
      ['headers' => $this->getHttpHeaders()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $options = [
      'headers' => $this->getHttpHeaders(),
      'query' => [
        'fq' => 'id:' . $id,
      ],
    ];
    if ($this->configuration['parameters']['single']) {
      $options['query'] += $this->configuration['parameters']['single'];
    }
    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      $options
    );
    $result = $this->decoder->getDecoder($this->configuration['format'])->decode($response->getBody());
    return (object) $result['response']['docs'][0];
  }

  /**
   * {@inheritdoc}
   */
  public function save(ExternalEntityInterface $entity) {
    if ($entity->externalId()) {
      $response = $this->httpClient->put(
        $this->configuration['endpoint'] . '/' . $entity->externalId(),
        [
          'body' => (array) $entity->getMappedObject(),
          'headers' => $this->getHttpHeaders(),
        ]
      );
      $result = SAVED_UPDATED;
    }
    else {
      $response = $this->httpClient->post(
        $this->configuration['endpoint'],
        [
          'body' => (array) $entity->getMappedObject(),
          'headers' => $this->getHttpHeaders(),
        ]
      );
      $result = SAVED_NEW;
    }

    // @todo: is it standard REST to return the new entity?
    $object = (object) $this->decoder->getDecoder($this->configuration['format'])->decode($response->getBody());
    $entity->mapObject($object);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters) {
    $response = $this->httpClient->get(
      $this->configuration['endpoint'],
      [
        'query' => $parameters + $this->configuration['parameters']['list'],
        'headers' => $this->getHttpHeaders(),
      ]
    );
    $results = $this->decoder->getDecoder($this->configuration['format'])->decode($response->getBody());
    $this->setCount($results['response']['numFound']);
    $results = $results['response']['docs'];
    foreach ($results as &$result) {
      $result = ((object) $result);
    }
    return $results;
  }

}
