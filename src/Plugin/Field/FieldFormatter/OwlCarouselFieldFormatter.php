<?php

namespace Drupal\owlcarousel\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\owlcarousel\OwlCarouselGlobal;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'owl_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "owlcarousel_field_formatter",
 *   label = @Translation("OwlCarousel Carousel"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class OwlCarouselFieldFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  protected $currentUser;
  protected $imageStyleStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
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
      $container->get('entity_type.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $owlcarousel_default_settings = OwlCarouselGlobal::defaultSettings();
    return $owlcarousel_default_settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];
    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
    $element['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    ];

    $element['items'] = [
      '#type' => 'number',
      '#title' => $this->t('Items'),
      '#description' => $this->t('Maximum amount of items displayed at a time with the widest browser width.'),
      '#default_value' => $this->getSetting('items'),
    ];
		
    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // TODO: Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl()->toString();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        $url = Url::fromUri(file_create_url($image_uri));
      }
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
    }
    $owlcarousel_default_settings = OwlCarouselGlobal::defaultSettings();
    $settings = $owlcarousel_default_settings;
    foreach ($settings as $k => $v) {
      $s = $this->getSetting($k);
      $settings[$k] = isset($s) ? $s : $settings[$k];
    }
    return [
      '#theme' => 'owlcarousel',
      '#items' => $elements,
      '#settings' => $settings,
      '#attached' => ['library' => ['owlcarousel/owlcarousel']],
    ];

  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
