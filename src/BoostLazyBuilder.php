<?php

namespace Drupal\boost;


use Drupal\Core\Entity\EntityTypeManager;
use Drupal\views\ViewExecutableFactory;
use Drupal\views\Element\View;
use Drupal\Component\Utility\Xss;
use function GuzzleHttp\json_decode;

/**
 */
class BoostLazyBuilder {
  
  /**
   * 
   * @var EntityTypeManager
   */
  protected $entityTypeManager;
  
  /**
   * 
   * @var ViewExecutableFactory
   */
  protected $viewExecutable;
  
  public function __construct(EntityTypeManager $entityTypeManager, ViewExecutableFactory $viewExecutable) {
    $this->entityTypeManager = $entityTypeManager;
    $this->viewExecutable = $viewExecutable;
  }
  
  public function buildViews($name, $dispaly_id, $args = '', $configuration = '') {
    
    $args = json_decode($args, TRUE);
    $configuration = json_decode($configuration, TRUE);
    
    if(empty($args)){
      $args = [];
    }
    if(empty($configuration)){
      $configuration = [];
    }
    
    // Load the view.
    $view = $this->entityTypeManager->getStorage('view')->load($name);
    $view = $this->viewExecutable->get($view);
    $displaySet = $view->setDisplay($dispaly_id);
    
    // We ask ViewExecutable::buildRenderable() to avoid creating a render cache
    // entry for the view output by passing FALSE, because we're going to cache
    // the whole block instead.
    if ($output = $view->buildRenderable($dispaly_id, $args, FALSE)) {
      
      // Block module expects to get a final render array, without another
      // top-level #pre_render callback. So, here we make sure that Views'
      // #pre_render callback has already been applied.
      $output = View::preRenderViewElement($output);
      
      // Override the label to the dynamic title configured in the view.
      if (empty($configuration['views_label']) && $view->getTitle()) {
        $output['#title'] = ['#markup' => $view->getTitle(), '#allowed_tags' => Xss::getHtmlTagList()];
      }
      
      // When view_build is empty, the actual render array output for this View
      // is going to be empty. In that case, return just #cache, so that the
      // render system knows the reasons (cache contexts & tags) why this Views
      // block is empty, and can cache it accordingly.
      if (empty($output['view_build'])) {
        $output = ['#cache' => $output['#cache']];
      }
      
      return $output;
    }
    
    return [];
  }
}
