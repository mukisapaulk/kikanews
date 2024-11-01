<?php

namespace Drupal\simple_slideshow\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of 'simple_slideshow_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "simple_slideshow_field_formatter",
 *   label = @Translation("Simple Slideshow"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SimpleSlideshowFieldFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->imageStyleStorage = $this->entityTypeManager->getStorage('image_style');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
      'simple_slideshow_style'      => "none",
      'simple_slideshow_type'       => "slide",
      'simple_slideshow_autoplay'   => true,
      'simple_slideshow_rewind'     => true,
      'simple_slideshow_speed'      => 400,
      'simple_slideshow_start'      => 0,
      'simple_slideshow_perpage'    => 1,
      'simple_slideshow_permove'    => 1,
      'simple_slideshow_gap'        => NULL,
      'simple_slideshow_padding'    => NULL,
      'simple_slideshow_arrows'     => true,
      'simple_slideshow_pagination' => true,
      'simple_slideshow_arrowpath'  => NULL,
      'simple_slideshow_pausehover' => true,
      'simple_slideshow_lazyload'   => true,
      'simple_slideshow_direction'  => "ltr",
      'simple_slideshow_linkimgalt' => "",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t("Configure Image Styles"),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['simple_slideshow_style'] = [
      '#title' => t("Image style"),
      '#type' => 'select',
      '#default_value' => $this->getSetting('simple_slideshow_style'),
      '#empty_option' => t("None (original image)"),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];

    $element['simple_slideshow_type'] = [
      '#type' => 'select',
      '#title' => t("Type/Effect"),
      '#options' => ['slide' => 'slide', 'loop' => 'loop', 'fade' => 'fade'],
      '#default_value' => $this->getSetting('simple_slideshow_type'),
      '#description' => t("Slideshow type or effect"),
    ];

    $element['simple_slideshow_autoplay'] = [
      '#type' => 'select',
      '#title' => t("Autoplay"),
      '#options' => ['true'=> 'true', 'false' => 'false'],
      '#default_value' => $this->getSetting('simple_slideshow_autoplay'),
      '#description' => t("Autoplay"),
    ];
    
    $element['simple_slideshow_rewind'] = [
      '#title' => t("Rewind"),
      '#type' => 'select',
      '#default_value' => $this->getSetting('simple_slideshow_rewind'),
      '#options' => ['true'=> 'true', 'false' => 'false'],
      '#description' => t("Rewind/replay image. 'Rewind = true' same as 'Type/Effect=Loop'."),
    ];
    
    $element['simple_slideshow_speed'] = [
      '#title' => $this->t("Speed"),
      '#type' => 'number',
      '#default_value' => $this->getSetting('simple_slideshow_speed'),
      '#description' => $this->t('Speed for slideshow.'),
    ];
    
    $element['simple_slideshow_start'] = [
      '#type' => 'number',
      '#title' => t("Start Index"),
      '#default_value' => $this->getSetting('simple_slideshow_start'),
      '#description' => t("Start index from 0."),
    ];
    
    $element['simple_slideshow_perpage'] = [
      '#type' => 'number',
      '#title' => t("Slide per page"),
      '#default_value' => $this->getSetting('simple_slideshow_perpage'),
      '#description' => t("How many slides displayed per page."),
      '#min' => 1,
    ];

    $element['simple_slideshow_gap'] = [
      '#title' => $this->t("Gap"),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('simple_slideshow_gap'),
      '#description' => $this->t('Gap between image. Allow CSS format: 20px, 20%, 5em, etc'),
    ];
    
    $element['simple_slideshow_padding'] = [
      '#title' => $this->t("Padding"),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('simple_slideshow_padding'),
      '#description' => $this->t('Padding. Allow CSS format: 20px, 20%, 5em, etc'),
    ];
    
    $element['simple_slideshow_arrows'] = [
      '#title' => t("Arrows"),
      '#type' => 'select',
      '#default_value' => $this->getSetting('simple_slideshow_arrows'),
      '#options' => ['true'=> 'true', 'false' => 'false'],
      '#description' => t("Show or hide arrows."),
    ];
    
    $element['simple_slideshow_pagination'] = [
      '#title' => t("Pagination"),
      '#type' => 'select',
      '#default_value' => $this->getSetting('simple_slideshow_pagination'),
      '#options' => ['true'=> 'true', 'false' => 'false'],
      '#description' => t("Show or hide pagination."),
    ];
    
    $element['simple_slideshow_arrowpath'] = [
      '#title' => $this->t("Arrow Path"),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('simple_slideshow_arrowpath'),
      '#description' => $this->t("Changes the arrow SVG path, like 'm7.61 0.807-2.12...'. The SVG size must be 40×40."),
    ];
    
    $element['simple_slideshow_pausehover'] = [
      '#title' => t("Pause On Hover"),
      '#type' => 'select',
      '#default_value' => $this->getSetting('simple_slideshow_pausehover'),
      '#options' => ['true'=> 'true', 'false' => 'false'],
      '#description' => t("Pause on Hover."),
    ];
    
    $element['simple_slideshow_lazyload'] = [
      '#title' => t("Lazy Load"),
      '#type' => 'select',
      '#default_value' => $this->getSetting('simple_slideshow_lazyload'),
      '#options' => ['true'=> 'true', 'false' => 'false'],
      '#description' => t("Enables lazy loading."),
    ];
    
    $element['simple_slideshow_direction'] = [
      '#title' => t("Direction"),
      '#type' => 'select',
      '#default_value' => $this->getSetting('simple_slideshow_direction'),
      '#options' => ['ltr' => 'Left to right',
                     'rtl' => 'Right to left',
                     'ttb' => 'Top to bottom'],
      '#description' => t("The direction of the carousel."),
    ];
    
    $link_image_to = ['' => 'Nothing', 'linkimgalt' => 'Link in Image Alt field', 'file' => 'File'];
    $element['simple_slideshow_linkimgalt'] = [
      '#type' => 'select',
      '#title' => t("Link image to"),
      '#options' => $link_image_to,
      '#default_value' => $this->getSetting('simple_slideshow_linkimgalt'),
      '#description' => t("Type the link in Image ALT field, i.e: https://www.domain.tld/content-title."),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('simple_slideshow_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t("Image style: @style", ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t("Image style: Original image");
    }

    $simple_slideshow_type       = $this->getSetting('simple_slideshow_type');       $summary[] .= $this->t("Type/Effect:   $simple_slideshow_type");
    $simple_slideshow_autoplay   = $this->getSetting('simple_slideshow_autoplay');   $summary[] .= $this->t("Autoplay      : $simple_slideshow_autoplay");
    $simple_slideshow_rewind     = $this->getSetting('simple_slideshow_rewind');     $summary[] .= $this->t("Rewind        : $simple_slideshow_rewind");
    $simple_slideshow_speed      = $this->getSetting('simple_slideshow_speed');      $summary[] .= $this->t("Speed         : $simple_slideshow_speed");
    $simple_slideshow_start      = $this->getSetting('simple_slideshow_start');      $summary[] .= $this->t("Start Index   : $simple_slideshow_start");
    $simple_slideshow_perpage    = $this->getSetting('simple_slideshow_perpage');    $summary[] .= $this->t("Per Page      : $simple_slideshow_perpage");
    $simple_slideshow_permove    = $this->getSetting('simple_slideshow_permove');    $summary[] .= $this->t("Per Move      : $simple_slideshow_permove");
    $simple_slideshow_gap        = $this->getSetting('simple_slideshow_gap');        $summary[] .= $this->t("Gap           : $simple_slideshow_gap");
    $simple_slideshow_padding    = $this->getSetting('simple_slideshow_padding');    $summary[] .= $this->t("Padding       : $simple_slideshow_padding");
    $simple_slideshow_arrows     = $this->getSetting('simple_slideshow_arrows');     $summary[] .= $this->t("Arrows        : $simple_slideshow_arrows");
    $simple_slideshow_pagination = $this->getSetting('simple_slideshow_pagination'); $summary[] .= $this->t("Pagination    : $simple_slideshow_pagination");
    $simple_slideshow_arrowpath  = $this->getSetting('simple_slideshow_arrowpath');  $summary[] .= $this->t("Arrow Path    : $simple_slideshow_arrowpath");
    $simple_slideshow_pausehover = $this->getSetting('simple_slideshow_pausehover'); $summary[] .= $this->t("Pause on Hover: $simple_slideshow_pausehover");
    $simple_slideshow_lazyload   = $this->getSetting('simple_slideshow_lazyload');   $summary[] .= $this->t("Lazy Load     : $simple_slideshow_lazyload");
    $simple_slideshow_direction  = $this->getSetting('simple_slideshow_direction');  $summary[] .= $this->t("Direction     : $simple_slideshow_direction");
    $simple_slideshow_linkimgalt = $this->getSetting('simple_slideshow_linkimgalt'); $summary[] .= $this->t("Link Image to : $simple_slideshow_linkimgalt");
    
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Get the Entity value as array.
    $file = $items->getEntity()->toArray();

    // Early opt-out if the field is empty.
    $images = $this->getEntitiesToView($items, $langcode);
    if (empty($images)) {
      return $elements;
    }

    $image_style_setting = $this->getSetting('simple_slideshow_style');
    $image_style = NULL;
    if (!empty($image_style_setting)) {
      $image_style = \Drupal::entityTypeManager()->getStorage('image_style')->load($image_style_setting);
    }

    $image_uri_values = [];
    foreach ($images as $image) {
      $image_uri = $image->getFileUri();
      // Get image style URL.
      if ($image_style) {
        $image_uri = ImageStyle::load($image_style->getName())->buildUrl($image_uri);
      }
      else {
        // Get absolute path for original image.
        $image_uri = $image->createFileUrl(FALSE);
      }
      // Populate image uri's with fid.
      $fid = $image->toArray()['fid'][0]['value'];
      $image_uri_values[$fid] = ['uri' => $image_uri];
    }

    // Populate the title and alt of images based on fid.
    foreach (['title', 'alt'] as $element_name) {
      $field_name = $this->fieldDefinition->getName();
      if (array_key_exists($field_name, $file)) {
        foreach($file[$field_name] as $key => $value) {
          $image_uri_values[$value['target_id']]['alt'] = $value['alt'];
          $image_uri_values[$value['target_id']]['title'] = $value['title'];
        }
      }
    }

    // Enable prev next if only more than one image.
    /*
    $prev_next = $this->getSetting('simple_slideshow_prev_next');
    if (count($image_uri_values) <= 1) {
      $prev_next = FALSE;
    }
    */
    
    $elements[] = [
      '#theme' => 'simple_slideshow',
      '#url' => $image_uri_values,
      '#simple_slideshow_type'       => $this->getSetting('simple_slideshow_type'),
      '#simple_slideshow_autoplay'   => $this->getSetting('simple_slideshow_autoplay'),
      '#simple_slideshow_rewind'     => $this->getSetting('simple_slideshow_rewind'),
      '#simple_slideshow_speed'      => $this->getSetting('simple_slideshow_speed'),
      '#simple_slideshow_start'      => $this->getSetting('simple_slideshow_start'),
      '#simple_slideshow_perpage'    => $this->getSetting('simple_slideshow_perpage'),
      '#simple_slideshow_permove'    => $this->getSetting('simple_slideshow_permove'),
      '#simple_slideshow_gap'        => $this->getSetting('simple_slideshow_gap'),
      '#simple_slideshow_padding'    => $this->getSetting('simple_slideshow_padding'),
      '#simple_slideshow_arrows'     => $this->getSetting('simple_slideshow_arrows'),
      '#simple_slideshow_pagination' => $this->getSetting('simple_slideshow_pagination'),
      '#simple_slideshow_arrowpath'  => $this->getSetting('simple_slideshow_arrowpath'),
      '#simple_slideshow_pausehover' => $this->getSetting('simple_slideshow_pausehover'),
      '#simple_slideshow_lazyload'   => $this->getSetting('simple_slideshow_lazyload'),
      '#simple_slideshow_direction'  => $this->getSetting('simple_slideshow_direction'),
      '#simple_slideshow_linkimgalt' => $this->getSetting('simple_slideshow_linkimgalt'),
    ];

    // Attach the image field slide show library.
    $elements['#attached']['library'][] = 'simple_slideshow/simple_slideshow';

    // Not to cache this field formatter.
    $elements['#cache']['max-age'] = 0;

    return $elements;
  }

}
