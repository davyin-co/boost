<?php

namespace Drupal\boost\Plugin\Block;

use Drupal\Core\Entity\EntityInterface;
use Drupal\views\Plugin\Block\ViewsBlock as ViewsBlockBase;
use Drupal\Core\Form\FormStateInterface;
use function GuzzleHttp\json_encode;

class ViewsBlock extends ViewsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    if(empty($this->configuration['lazy_build'])){
      return parent::build();
    }
    
    $this->view->display_handler->preBlockBuild($this);

    $args = [];
    foreach ($this->view->display_handler->getHandlers('argument') as $argument_name => $argument) {
      // Initialize the argument value. Work around a limitation in
      // \Drupal\views\ViewExecutable::_buildArguments() that skips processing
      // later arguments if an argument with default action "ignore" and no
      // argument is provided.
      $args[$argument_name] = $argument->options['default_action'] == 'ignore' ? 'all' : NULL;

      if (!empty($this->context[$argument_name])) {
        if ($value = $this->context[$argument_name]->getContextValue()) {

          // Context values are often entities, but views arguments expect to
          // receive just the entity ID, convert it.
          if ($value instanceof EntityInterface) {
            $value = $value->id();
          }
          $args[$argument_name] = $value;
        }
      }
    }

    return [
      '#lazy_builder' => [
        'boost.lazy_builder:buildViews',
        [
          $this->view->id(),
          $this->displayID,
          json_encode(array_values($args)),
          json_encode($this->configuration),
        ],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $settings = parent::defaultConfiguration();
    $settings['lazy_build'] = 0;

    return $settings;
  }
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    $form['lazy_build'] = [
      '#title' => $this->t('Use lazy builder show this block.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['lazy_build'],
    ];
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['lazy_build'] = $form_state->getValue('lazy_build');
  }

}
