<?php
namespace Drupal\term_manager\Controller;

# use Drupal\Core\Controller\ControllerBase;
# use Symfony\Component\DependencyInjection\ContainerInterface;
# use Symfony\Component\HttpFoundation\JsonResponse;


class TermManagerController {
  public function home() {
    return array(
      '#markup' => 'Welcome to Term Manager.'
    );
  }
  
  public function move() {
    return array(
      '#markup' => 'Term Manager: Move Term to another Vocabulary.'
    );
  }
}