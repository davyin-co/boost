<?php

use Drupal\Core\Cache\Cache;

/**
 * @file
 * Dispatcher daemon
 * This file is the heart of the dispatcher drush command.
 * It
 * implements most of the backend functionality.
 */

// This is necessary for signal handling to work
declare(ticks = 1);

/**
 * Implements hook_drush_command().
 */
function boost_drush_command() {
  $items = array();
  
  $items['boost-queued'] = array(
    'description' => 'Runs the tasks queue',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'drupal dependencies' => array(
      'boost'
    )
  );
  $items['boost-clear-all'] = array(
    'description' => 'Clear all boost cache files',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
    'drupal dependencies' => array(
      'boost'
    )
  );
  
  return $items;
}

/**
 * Drush command to execute hosting tasks.
 */
function drush_boost_clear_all() {
  $cacheBins = Cache::getBins();
  if(isset($cacheBins['boost'])){
    $cacheBins['boost']->purgeAll();
  }
  drush_log('Boost cache cleared.', 'ok');
}
/**
 * Drush command to execute hosting tasks.
 */
function drush_boost_queued() {
  if (function_exists('pcntl_signal')) {
    // reload the server on SIGHUP
    pcntl_signal(SIGHUP, 'boost_queued_restart');
    pcntl_signal(SIGINT, 'boost_queued_stop');
    pcntl_signal(SIGTERM, 'boost_queued_stop');
  }
  
  // Set a nice high time limit, if we can:
  if (function_exists('set_time_limit')) {
    @set_time_limit(0);
  }
  
  // in some environments (e.g. in "productin") ENV is not actually
  // set (!) so try to guess from $_SERVER
  if (strpos(ini_get('variables_order'), 'E') === FALSE) {
    if (strpos(ini_get('variables_order'), 'S') === FALSE) {
      drush_log(dt('Neither $_ENV nor $_SERVER are available to set up proper environment inheritance; ensure E and/or S is set in your php.ini\'s "variables_order" setting.'), 'warning');
    } else {
      $_ENV = $_SERVER;
    }
  }
  
  $end_time = 10800 + REQUEST_TIME;
  
  $queue = \Drupal::service('queue')->get('boost_cache_fetch_queue');
  $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('boost_cache_fetch_queue');
  
  drush_log('Boost queue daemon started.', 'ok');
  
  while ( TRUE ) {
    try {
      // Should we terminate.
      if (time() > $end_time) {
        // Restart the daemon to recycle leaked memory.
        boost_queued_restart();
      }
      
      while ( $item = $queue->claimItem() ) {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        
        // Should we terminate.
        if (REQUEST_TIME > $end_time) {
          // Restart the daemon to recycle leaked memory
          boost_queued_restart();
        }
      }
    } catch ( \Exception $e ) {
      sleep(1);
      drush_log('Restarting queue daemon.', 'warning');
      boost_queued_restart();
    }
    sleep(5);
  }
}

/**
 * Handle interruption signals gracefully
 * We do not want to interrupt children tasks, so we wait for them
 * before stopping.
 */
function boost_queued_stop($signal) {
  $status = NULL;
  pcntl_wait($status);
  drush_log('Releasing lock on task queue.');
  exit($status);
}

/**
 * Restart the dispatcher to work around memory leaks
 */
function boost_queued_restart($signal = NULL) {
  drush_log('Boost queue daemon restarting.', 'ok');
  try {
    // If we received a singal, process it.
    if (!is_null($signal)) {
      $status = NULL;
      pcntl_wait($status);
    }
    
    // We need the PCNTL extension to be able to auto restart.
    if (function_exists('pcntl_exec')) {
      $args = $_ENV['argv'];
      $drush = array_shift($args);
      
      // Strip sub-array to avoid warning "Array to string conversion"
      unset($_ENV['argv']);
      
      drush_log('Releasing lock on task queue.');
    }
  } catch ( \Exception $e ) {
    // Caught ... dropping.
  }
  
  // New try block, to still restart if e.g. the watchog log ging faild on a missing DB connection.
  try {
    if (function_exists('pcntl_exec')) {
      pcntl_exec($drush, $args, $_ENV);
      drush_dog('Could not restart the queue daemon, aborting.', 'error');
      /* NOTREACHED */
    }
  } catch ( \Exception $e ) {
    // Caught ... dropping.
  }
  
  // Explicit exit in case we're handling a signal
  exit(1);
}
