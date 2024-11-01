<?php

namespace Drupal\menu_delete\Form;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The Class MenuDeleteItem.
 */
class MenuDeleteItem extends ConfirmFormBase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The array of menu items to delete.
   *
   * @var array
   */
  protected $menuItems = [];

  /**
   * The menu that items are being deleted from.
   *
   * @var string
   */
  protected $menuId = '';

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a MenuDeleteItem form object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, PrivateTempStoreFactory $temp_store_factory) {
    $this->entityRepository = $entity_repository;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_delete_item_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->menuItems), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.menu.edit_form', ['menu' => $this->menuId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $storage = $this->tempStoreFactory->get('menu_delete_item_confirm')->get($this->currentUser()->id());
    $this->menuId = $storage['menu_id'];
    $this->menuItems = $storage['items'];

    if (empty($this->menuItems)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $items = [];
    foreach ($this->menuItems as $item) {
      [$entity_type_id, $uuid] = explode(':', $item['id']);
      $link = $this->entityRepository->loadEntityByUuid($entity_type_id, $uuid);
      $items[$uuid] = $link->getTitle();
    }

    $form['menu_items'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->menuItems)) {
      $total_count = 0;

      foreach ($this->menuItems as $item) {
        [$entity_type_id, $uuid] = explode(':', $item['id']);
        $link = $this->entityRepository->loadEntityByUuid($entity_type_id, $uuid);
        $link->delete();
        $total_count++;
      }

      if ($total_count) {
        $this->messenger()->addMessage($this->formatPlural($total_count, 'Deleted 1 menu item.', 'Deleted @count menu items.'));
      }

      $this->tempStoreFactory->get('menu_delete_item_confirm')->delete($this->currentUser()->id());
    }

    $form_state->setRedirect('entity.menu.edit_form', ['menu' => $this->menuId]);
  }

}
