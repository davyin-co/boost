<?php

namespace Drupal\boost\PageCache\RequestPolicy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A policy allowing delivery of cached pages when there is no session open.
 *
 * Do not serve cached pages to authenticated users, or to anonymous users when
 * $_SESSION is non-empty. $_SESSION may contain status messages from a form
 * submission, the contents of a shopping cart, or other userspecific content
 * that should not be cached and displayed to other users.
 */
class Domains implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    $domain_to_exclude = \Drupal::config('boost.settings')->get('boost_skip_domain')?:[];
    $domain = $request->getHost();
    foreach ($domain_to_exclude as $ed){
      if(trim($ed) == $domain){
        return static::DENY;
      }
    }
    return static::ALLOW;
  }

}
