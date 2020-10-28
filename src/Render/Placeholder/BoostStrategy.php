<?php

namespace Drupal\boost\Render\Placeholder;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\PageCache\ChainRequestPolicyInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Site\Settings;

/**
 * Defines the boost placeholder strategy, to send HTML in ajax.
 */
class BoostStrategy implements PlaceholderStrategyInterface {

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;
  
  /**
   * The boost request policy.
   *
   * @var ChainRequestPolicyInterface
   */
  protected $requestPolicy;
  
  /**
   * 
   * @var boolean
   */
  protected $enabled = TRUE;

  /**
   * Constructs a new BoostStrategy class.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(SessionConfigurationInterface $session_configuration, RequestStack $request_stack, RouteMatchInterface $route_match, ChainRequestPolicyInterface $requestPolicy) {
    $this->sessionConfiguration = $session_configuration;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
    $this->requestPolicy = $requestPolicy;
    $this->enabled = \Drupal::config('boost.settings')->get('boost_lazy_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders) {
    
    if(!$this->enabled){
      return [];
    }
    $request = $this->requestStack->getCurrentRequest();

    // @todo remove this check when https://www.drupal.org/node/2367555 lands.
    if (!$request->isMethodCacheable()) {
      return [];
    }
    if($this->requestPolicy->check($request) === RequestPolicyInterface::DENY){
      return [];
    }

    return $this->doProcessPlaceholders($placeholders);
  }

  /**
   * Transforms placeholders to Boost placeholders, either no-JS or JS.
   *
   * @param array $placeholders
   *   The placeholders to process.
   *
   * @return array
   *   The Boost placeholders.
   */
  protected function doProcessPlaceholders(array $placeholders) {
    $overridden_placeholders = [];
    foreach ($placeholders as $placeholder => $placeholder_elements) {
      if(!static::placeholderIsAttributeSafe($placeholder)){
        if($override = static::createBoostPlaceholder($placeholder, $placeholder_elements)){
          $overridden_placeholders[$placeholder] = $override;
        }
      }
    }
    return $overridden_placeholders;
  }

  /**
   * Determines whether the given placeholder is attribute-safe or not.
   *
   * @param string $placeholder
   *   A placeholder.
   *
   * @return bool
   *   Whether the placeholder is safe for use in a HTML attribute (in case it's
   *   a placeholder for a HTML attribute value or a subset of it).
   */
  protected static function placeholderIsAttributeSafe($placeholder) {
    assert(is_string($placeholder));
    return $placeholder[0] !== '<' || $placeholder !== Html::normalize($placeholder);
  }

  /**
   * Creates a Boost JS placeholder.
   *
   * @param string $original_placeholder
   *   The original placeholder.
   * @param array $placeholder_render_array
   *   The render array for a placeholder.
   *
   * @return array
   *   The resulting Boost JS placeholder render array.
   */
  protected static function createBoostPlaceholder($original_placeholder, array $placeholder_render_array) {
    $boost_placeholder_id = static::generateBoostPlaceholderId($original_placeholder, $placeholder_render_array);
    if($boost_placeholder_id){
      return [
        '#markup' => '<span data-boost-placeholder-id="' . Html::escape($boost_placeholder_id) . '"></span>',
        '#cache' => [
          'max-age' => 0,
        ],
        '#attached' => [
          'library' => [
              'boost/boost',
            ],
          'drupalSettings' => [
            'boostPlaceholderIds' => [$boost_placeholder_id => TRUE],
          ],
        ],
      ];
    }else{
      return false;
    }
  }

  /**
   * Generates a Boost placeholder ID.
   *
   * @param string $original_placeholder
   *   The original placeholder.
   * @param array $placeholder_render_array
   *   The render array for a placeholder.
   *
   * @return string
   *   The generated Boost placeholder ID.
   */
  public static function generateBoostPlaceholderId($original_placeholder, array $placeholder_render_array) {
    // Generate a Boost placeholder ID (to be used by Boost's JavaScript).
    // @see \Drupal\Core\Render\PlaceholderGenerator::createPlaceholder()
    if (isset($placeholder_render_array['#lazy_builder'])) {
      $callback = $placeholder_render_array['#lazy_builder'][0];
      $arguments = $placeholder_render_array['#lazy_builder'][1];
      foreach ($arguments as $index => $value){
        if(empty($value)){
          $arguments[$index] = "";
        }else{
          $arguments[$index] = (string)$value; 
        }
      }
      $token = Crypt::hashBase64($callback . serialize($arguments) . Settings::getHashSalt());
      return UrlHelper::buildQuery(['callback' => $callback, 'args' => $arguments, 'token' => $token]);
    }
    // When the placeholder's render array is not using a #lazy_builder,
    // anything could be in there: only #lazy_builder has a strict contract that
    // allows us to create a more sane selector. Therefore, simply the original
    // placeholder into a usable placeholder ID, at the cost of it being obtuse.
    else {
      return false;
    }
  }

}
