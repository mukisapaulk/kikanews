<?php

namespace Drupal\node_protector\Controller;

/**
 * Module Controller.
 */
class NodeProtectorController {

  /**
   * {@inheritdoc}
   */
  public function home() {
    return [
      '#markup' => 'Welcome to Node Protector.',
    ];
  }

}
