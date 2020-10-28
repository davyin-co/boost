<?php

namespace Drupal\boost\Cache;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Defines a default cache implementation.
 *
 * This is Drupal's default cache implementation. It uses the database to store
 * cached data. Each cache bin corresponds to a database table by the same name.
 *
 * @ingroup cache
 */
class BoostDatabaseBackend extends DatabaseBackend implements CacheTagsInvalidatorInterface {
  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\Core\Cache\CacheTagsInvalidatorInterface::invalidateTags()
   */
  public function invalidateTags(array $tags) {
    $queue_enabled = \Drupal::config('boost.settings')->get('boost_pre_fetch_queue');
    $tags_to_exclude = \Drupal::config('boost.settings')->get('boost_skip_tags')?:[];
    foreach ($tags as $tag){
      if(!in_array($tag,$tags_to_exclude)){
        boost_invalidate_cache_tag($tag, $queue_enabled);
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    // Do nothing on cache flush
  }
  /**
   * Delete all cache.
   */
  public function purgeAll() {
    parent::deleteAll();
    $boost_dir = boost_get_cache_dir();
    $count = _boost_rmdir($boost_dir, TRUE);
  }
  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    
  }
  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    
  }
  
}
