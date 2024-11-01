<?php

namespace Drupal\node_protector\Form;

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
class NodeProtectorSettingsForm extends ConfigFormBase {

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
      'node_protector.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_protector_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('node_protector.settings');

    $form['node_protector_nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NID'),
      '#default_value' => $config->get('node_protector_nid'),
      '#required' => FALSE,
      '#description' => $this->t('NID to protect from Delete action. Usually the NID of your Frontpage.'),
    ];

    $site_config = $this->config('system.site');
    $front_page = $site_config->get('page.front');
    // $config->set('node_protector_frontpage', $front_page);
    $form['node_protector_auto'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Node Protector Auto'),
      '#description' => $this->t('Check it if you want to automatically protect Frontpage from deleting. Current Frontage is: %front_page', ['%front_page' => $front_page]),
      '#default_value' => $config->get('node_protector_auto'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('node_protector.settings')
      ->set('node_protector_nid', $form_state->getValue('node_protector_nid'))
      ->set('node_protector_auto', $form_state->getValue('node_protector_auto'))
      ->save();
    parent::submitForm($form, $form_state);
    $this->cacheMenu->invalidateAll();
    $this->menuLinkManager->rebuild();
  }

}
