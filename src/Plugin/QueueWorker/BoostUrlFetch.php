<?php
/**
 * @file
 */
namespace Drupal\boost\Plugin\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes Tasks for deploy.
 *
 * @QueueWorker(
 *   id = "boost_url_fetch_queue",
 *   title = @Translation("Boost url fetch")
 * )
 */
class BoostUrlFetch extends QueueWorkerBase{

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $file = $data->file;
    $url = $data->url;
    _boost_fetch_url($url);
  }
    
}
