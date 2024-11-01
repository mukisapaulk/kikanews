<?php

namespace Drupal\term_manager\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
# use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Class AdminToolbarToolsSettingsForm.
 *
 * @package Drupal\admin_toolbar_tools\Form
 */
class TermManagerMoveForm extends FormBase {

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
  #public function __construct(ConfigFactoryInterface $configFactory, MenuLinkManagerInterface $menuLinkManager, CacheBackendInterface $cacheMenu) {
  #  parent::__construct($configFactory);
  #  $this->cacheMenu = $cacheMenu;
  #  $this->menuLinkManager = $menuLinkManager;
  #}

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
    $term_depth = $config->get('term_manager_depth');
    
    $vocabularies = Vocabulary::loadMultiple();

    /*
    foreach($vocabularies as $voc) {
      //print_r($voc->id()); exit;
      $term_array[$voc->id()] = $voc->label();
        
      for ($i=1; $i<= $term_depth; $i++) {
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($voc->id(), 0, $i);
        if (!is_null($terms)) {
          //print "<pre>"; print_r($terms); print "</pre>"; exit;    
          foreach ($terms as $term) {
            $term_array[$term->tid] = str_repeat("-", $term->depth + 1) . $term->name . " ($term->tid)";
          }    
        }
      }
    }
    */
    
    $term_array = [];
    foreach($vocabularies as $voc) {
        $term_array[$voc->id()] = $voc->label();
        foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($voc->id()) as $item) {
          $value = $voc->id() . '|' . 
          $term_array[$voc->id(). '|' . $item->tid] = str_repeat('-', $item->depth + 1) . "$item->name ($item->tid)";
        }
    }
    
    $form['term_manager_tid'] = [
      '#type' => 'select',
      '#title' => $this->t('Source of tid'),
      '#default_value' => $config->get('term_manager_nid'),
      '#required' => FALSE,
      '#description' => $this->t('Select tid to move to new Vocabulary.'),
      '#options' => $term_array,
    ];
    
    
    $voc_array= [];
    foreach($vocabularies as $voc) {
      $voc_array[$voc->id()] = $voc->label();
    }
      
    $form['term_manager_vid'] = [
      '#type' => 'select',
      '#title' => $this->t('Target of vid'),
      '#default_value' => $config->get('term_manager_vid'),
      '#required' => FALSE,
      '#description' => $this->t('Select vid target.'),
      '#options' => $voc_array,
    ];

    $form['actions'] = ['#type' => 'actions', '#tree' => FALSE];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
    return $form;
    # return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term_manager_tid = $form_state->getValue('term_manager_tid');
    $vid_target       = $form_state->getValue('term_manager_vid');
    
    $term_select = explode('|', $term_manager_tid);
    $vid_source = $term_select[0];
    $tid_source = $term_select[1];
    
    \Drupal::messenger()->addStatus("Move tid $tid_source from Vocabulary '$vid_source' to '$vid_target'.");
   
    if ($vid_source == $vid_target) {
      \Drupal::messenger()->addWarning("Target Vocabulary must be different from source.");
      return;
    }
    
    if (!is_numeric($tid_source)) {
      \Drupal::messenger()->addWarning("Select tid first.");
      return;
    }

    $terms_items = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid_source, $tid_source);
    // print "<pre>"; print_r($terms_items); print "<pre>"; exit;
    
    foreach ($terms_items as $terms_item) {
        $term_child = Term::load($terms_item->tid);
        $term_child->vid->setValue($vid_target);
        $term_child->Save();
        
        # print "<pre>Term: "; print_r($term); print "<pre>";
        # print_r($term_child); print " "; 
    }
    
    $term_parent = Term::load($tid_source);
    $term_parent->vid->setValue($vid_target);
    $term_parent->Save();
    
    \Drupal::messenger()->addStatus("The term $tid_source from  Vocabulary '$vid_source' moves to '$vid_target'."); 
    \Drupal::service('cache.render')->invalidateAll();
  }
}
