<?php

namespace Drupal\term_manager\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminToolbarToolsSettingsForm.
 *
 * @package Drupal\admin_toolbar_tools\Form
 */
class TermManagerSettingsForm extends ConfigFormBase {

  /**
   * The cache menu instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheMenu;

  /**
   * The menu link manager instance.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * AdminToolbarToolsSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   A menu link manager instance.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheMenu
   *   A cache menu instance.
   */
  public function __construct(ConfigFactoryInterface $configFactory, MenuLinkManagerInterface $menuLinkManager, CacheBackendInterface $cacheMenu) {
    parent::__construct($configFactory);
    $this->cacheMenu = $cacheMenu;
    $this->menuLinkManager = $menuLinkManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.menu.link'),
      $container->get('cache.menu')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'term_manager.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'term_manager_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('term_manager.settings');
    
    $form['term_manager_depth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term Depth'),
      '#default_value' => $config->get('term_manager_depth'),
      '#required' => FALSE,
      '#description' => $this->t('You can set depth of tid, by default 5.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      
    $this->config('term_manager.settings')
      ->set('term_manager_nid',       $form_state->getValue('term_manager_nid'))
      ->set('term_manager_auto',      $form_state->getValue('term_manager_auto'))
      ->save();
    parent::submitForm($form, $form_state);
    $this->cacheMenu->invalidateAll();
    $this->menuLinkManager->rebuild();
  }

}
