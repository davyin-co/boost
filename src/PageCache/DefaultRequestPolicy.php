<?php

namespace Drupal\boost\PageCache;

use Drupal\Core\PageCache\RequestPolicy\CommandLineOrUnsafeMethod;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Core\PageCache\ChainRequestPolicy;
use Drupal\boost\PageCache\RequestPolicy\IsAnonymous;
use Drupal\boost\PageCache\RequestPolicy\GetMethod;
use Drupal\boost\PageCache\RequestPolicy\Domains;

/**
 * The default page cache request policy.
 *
 * Delivery of cached pages is denied if either the application is running from
 * the command line or the request was not initiated with a safe method (GET or
 * HEAD). Also caching is only allowed for requests without a session cookie.
 */
class DefaultRequestPolicy extends ChainRequestPolicy {

  /**
   * Constructs the default page cache request policy.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   */
  public function __construct(SessionConfigurationInterface $session_configuration) {
    $this->addPolicy(new CommandLineOrUnsafeMethod());
    $this->addPolicy(new GetMethod());
    $this->addPolicy(new Domains());
  }

}
