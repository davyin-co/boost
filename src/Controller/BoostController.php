<?php

namespace Drupal\boost\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\boost\Render\Placeholder\BoostStrategy;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Site\Settings;

/**
 * Returns responses for boost module routes.
 */
class BoostController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;
  
  /**
   * 
   * @param RendererInterface $renderer
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('renderer')
        );
  }
  /**
   * 
   * @param Request $request
   */
  public function placeHolderReplace(Request $request) {
    $callback = $request->get('callback');
    $arguments = $request->get('args',[]);
    $token = $request->get('token');
    
    $c_token = Crypt::hashBase64($callback . serialize($arguments) . Settings::getHashSalt());
    if(empty($token) || $token != $c_token){
      throw new AccessDeniedHttpException();
    }
    $elements = [
      '#lazy_builder' => [
        $callback,
        $arguments
      ],
      '#create_placeholder' => FALSE,
    ];
    $boost_placeholder_id = BoostStrategy::generateBoostPlaceholderId('', $elements);
    $render = $this->renderer->renderRoot($elements);
    
    if(empty($render)){
      $render = [
        '#markup' => ''
      ];
    }
    
    $ajax_response = new AjaxResponse();
    $boost_placeholder_id = Html::decodeEntities($boost_placeholder_id);
    $ajax_response->addCommand(new ReplaceCommand(sprintf('[data-boost-placeholder-id="%s"]', $boost_placeholder_id), $render));
    
    return $ajax_response;
  }

}
