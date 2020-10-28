<?php

namespace Drupal\boost\PageCache\ResponsePolicy;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Url;

/**
 * A policy allowing delivery of cached pages when there is no session open.
 *
 * Do not serve cached pages to authenticated users, or to anonymous users when
 * $_SESSION is non-empty. $_SESSION may contain status messages from a form
 * submission, the contents of a shopping cart, or other userspecific content
 * that should not be cached and displayed to other users.
 */
class BoostPathFilter implements ResponsePolicyInterface {
  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    $base_path = base_path();
    $uri = trim($request->getRequestUri(), $base_path);
    try {
      $url = Url::fromUserInput('/' . $uri);
      if (!$url->isExternal()) {
        $uri = $url->getInternalPath();
      }
    } catch (\Exception $e) {
      $uri = trim($request->getRequestUri(), $base_path);
    }
    if (preg_match('#(^(admin|devel|cache|misc|batch|profiles|modules|sites|system|openid|themes|node/add|comment/reply|flag|manage|entity-browser|boost|relaxed|jsonapi|api|app/|search/|user/|my/))|((edit|user|user/(login|password|register))$)#', $uri)) {
      return static::DENY;
    }

    if (strpos($request->getRequestUri(), '?') > 0) {
      $with_parameter = \Drupal::config('boost.settings')->get('boost_with_parameter');
      if (empty($with_parameter)) {
        return static::DENY;
      }
    }
  }
}
