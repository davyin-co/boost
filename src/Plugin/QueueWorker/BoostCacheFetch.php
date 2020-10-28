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
 *   id = "boost_cache_fetch_queue",
 *   title = @Translation("Boost cache fetch")
 * )
 */
class BoostCacheFetch extends QueueWorkerBase{

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    boost_invalidate_cache_tag($data, false, true);
  }
    
}
