<?php

namespace Drupal\boost\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Cache\Cache;

/**
 * Class BaseImportForm.
 * 
 * @package Drupal\ccms_permission\Form
 */
class BoostSettingsForm extends ConfigFormBase {

  /**
   *
   * {@inheritdoc}
   *
   */
  protected function getEditableConfigNames() {
    return [
      'boost.settings'
    ];
  }

  /**
   *
   * {@inheritdoc}
   *
   */
  public function getFormId() {
    return 'boost_settings_form';
  }

  /**
   *
   * {@inheritdoc}
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('boost.settings');
    
    $form['boost_expire_cron'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove old cache files on cron.'),
      '#default_value' => $config->get('boost_expire_cron'),
      '#description' => t('If enabled, each time cron runs Boost will check each cached page and delete those that have expired (maximum cache lifetime). The expiration time is displayed in the comment that Boost adds to the bottom of the html pages it creates. This setting is recommended for most sites.')
    );
    
    $period = array(
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      64800,
      86400,
      2 * 86400,
      3 * 86400,
      4 * 86400,
      5 * 86400,
      6 * 86400,
      604800,
      2 * 604800,
      3 * 604800,
      4 * 604800,
      8 * 604800,
      16 * 604800,
      52 * 604800
    );
    $options = array_combine($period, $period);
    // Maximum cache lifetime
    $form['boost_lifetime_max'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('Maximum Cache Lifetime'),
      '#default_value' => $config->get('boost_lifetime_max')
    );
    
    $form['boost_lazy_builder'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use ajax for lazy builder render elements.'),
      '#default_value' => $config->get('boost_lazy_builder'),
    );
    
    $form['boost_pre_fetch_queue'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use queue for fetch page.'),
      '#default_value' => $config->get('boost_pre_fetch_queue'),
    );
    $form['boost_pre_fetch_ip'] = array(
      '#type' => 'textfield',
      '#title' => t('Fetch host ip'),
      '#default_value' => $config->get('boost_pre_fetch_ip'),
      '#description' => t('Use ip and host header for fetch page, leave blank to use default url.'),
      '#states' => [
        'visible' => [':input[name="boost_pre_fetch_queue"]' => ['checked' => TRUE]]
      ]
    );
    
    $form['boost_with_parameter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Cache page with parameter.'),
      '#default_value' => $config->get('boost_with_parameter'),
    );
    
    $form['boost_skip_domain'] = array(
      '#type' => 'textarea',
      '#title' => t('Input domain(s) where you do not to cache'),
      '#default_value' => implode("\n",($config->get('boost_skip_domain')?:[])),
      '#description' => t('Please enter a domain name per line.')
    );
    
    $form['boost_skip_tags'] = array(
      '#type' => 'textarea',
      '#title' => t('Input tag(s) where you do not to clean cache'),
      '#default_value' => implode("\n",($config->get('boost_skip_tags')?:[])),
      '#description' => t('Please enter a tag name per line.')
    );
    
    $form += parent::buildForm($form, $form_state);
    
    
    $form['clean'] = [
      '#type' => 'details',
      '#title' => t('Clean cache files'),
      '#open' => TRUE,
      '#weight' => 99,
    ];
    $form['clean']['boost_clean_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Remove static file of this url.'),
      '#default_value' => '',
    );
    $form['clean']['clear_url'] = [
      '#type' => 'submit',
      '#value' => t('Remove Single Url'),
      '#submit' => ['::cleanSingleUrl'],
    ];
    $form['clean']['clear_all'] = [
      '#type' => 'submit',
      '#attributes' => ['class' => ['button--danger']],
      '#value' => t('Remove All'),
      '#submit' => ['::cleanAll'],
    ];
    
    return $form;
  }

  /**
   *
   * {@inheritdoc}
   *
   */
  public function cleanSingleUrl(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('boost_clean_url');
    if(!empty($url)){
      $cid = $url . ':html';
      $cache = \Drupal::cache('boost')->get($cid,TRUE);
      if($cache){
        $file = $cache->data;
        if(file_exists($file)){
          unlink($file);
        }
        _boost_fetch_url($url);
      }
      drupal_set_message(t('Boost cache of url @url cleared.',['@url' => $url]));
    }else{
      drupal_set_message(t('Empty url.'));
    }
  }
  /**
   *
   * {@inheritdoc}
   *
   */
  public function cleanAll(array &$form, FormStateInterface $form_state) {
    $cacheBins = Cache::getBins();
    if(isset($cacheBins['boost'])){
      $cacheBins['boost']->purgeAll();
    }
    drupal_set_message(t('All boost cache cleared.'));
  }
  /**
   *
   * {@inheritdoc}
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('boost.settings');
    
    $domains = explode("\n",$form_state->getValue('boost_skip_domain',''));
    $domains = array_map('trim',$domains);
    $tags = explode("\n",$form_state->getValue('boost_skip_tags',''));
    $tags = array_map('trim',$tags);
    
    $config->set('boost_expire_cron', $form_state->getValue('boost_expire_cron'))
    ->set('boost_lifetime_max', $form_state->getValue('boost_lifetime_max'))
    ->set('boost_lazy_builder', $form_state->getValue('boost_lazy_builder'))
    ->set('boost_pre_fetch_queue', $form_state->getValue('boost_pre_fetch_queue'))
    ->set('boost_pre_fetch_ip', $form_state->getValue('boost_pre_fetch_ip'))
    ->set('boost_with_parameter', $form_state->getValue('boost_with_parameter'))
    ->set('boost_skip_domain', $domains)
    ->set('boost_skip_tags', $tags)
    ->save();
    
    boost_htaccess_cache_dir_put();
    parent::submitForm($form, $form_state);
  }
}
