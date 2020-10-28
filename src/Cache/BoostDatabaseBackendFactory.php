<?php

namespace Drupal\boost\Cache;

use Drupal\Core\Database\Connection;
use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\DatabaseBackendFactory;

class BoostDatabaseBackendFactory extends DatabaseBackendFactory {

  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\Core\Cache\DatabaseBackendFactory::get()
   */
  public function get($bin) {
    $max_rows = $this->getMaxRowsForBin($bin);
    return new BoostDatabaseBackend($this->connection, $this->checksumProvider, $bin, $max_rows);
  }

}
