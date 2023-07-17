<?php

namespace Drupal\mobile_native_share\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Referenced Content Moderation Settings.
 */
class MobileNativeShareSettings extends ConfigFormBase {

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a AddToAnySettingsForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal\Core\Config\ConfigFactoryInterface.
   */
  public function __construct(ModuleHandler $module_handler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $module_handler;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * Provides Configuration Form name.
   */
  public function getFormId() {
    return 'mobile_native_share_settings';
  }

  /**
   * Provides Configuration Page name for Accessing the values.
   */
  protected function getEditableConfigNames() {
    return [
      "mobile_native_share.settings",
    ];
  }

  /**
   * Creates a Form for Configuring the Module.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config("mobile_native_share.settings");
    $form['mobile_native_share']['style'] = [
      '#type' => 'select',
      '#options' => [
        'default' => 'Default',
        'fixed-icon' => 'Fixed',
      ],
      '#default_value' => $config->get("style"),
      '#title' => $this->t('Display style'),
    ];
    $form['mobile_native_share']['icon'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get("icon"),
      '#title' => $this->t('Icon'),
    ];
    $entities = self::getContentEntities();
    $linkableEntities = [
      //        'block_content',
      //        'comment',
      //        'commerce_product',
      //        'commerce_store',
      //        'contact_message',
      //        'media',
      'node',
      //        'paragraph',
    ];
    foreach ($entities as $entity) {
      $entityId = $entity->id();
      if (!in_array($entityId, $linkableEntities)) {
        continue;
      }
      $entityType = $entity->getBundleEntityType();
      // Get all available bundles for the current entity.
      $bundles = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo($entityId);

      foreach ($bundles as $machine_name => $bundle) {
        $label = $bundle['label'];
        // Some labels are TranslatableMarkup objects (such as the File entity).
        if ($label instanceof TranslatableMarkup) {
          $label = $label->render();
        }
        $form['mobile_native_share'][$entityId][$machine_name] = [
          '#type' => 'details',
          '#title' => $this->t('@entity', ['@entity' => $label]),
        ];
        $form['mobile_native_share'][$entityId][$machine_name][$machine_name] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable'),
          '#default_value' => $config->get("entities.{$entityId}.{$machine_name}.enable"),
        ];
        $form['mobile_native_share'][$entityId][$machine_name][$machine_name . '_title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Title'),
          '#default_value' => $config->get("entities.{$entityId}.{$machine_name}.title"),
        ];
        $form['mobile_native_share'][$entityId][$machine_name][$machine_name . '_description'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Description'),
          '#default_value' => $config->get("entities.{$entityId}.{$machine_name}.description"),
        ];
      }

    }
    if ($this->moduleHandler->moduleExists('token')) {
      $form['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
        '#global_types' => TRUE,
        '#click_insert' => TRUE,
        '#show_restricted' => FALSE,
        '#recursion_limit' => 3,
        '#text' => $this->t('Browse available tokens'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submits the Configuration Form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal\Core\Form\FormStateInterface.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('mobile_native_share.settings')->set('icon', $values['icon']);
    $this->config('mobile_native_share.settings')
      ->set('style', $values['style']);

    foreach (self::getContentEntities() as $entity) {
      $entityId = $entity->id();
      $bundles = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo($entityId);
      foreach ($bundles as $machine_name => $bundle) {
        if (!array_key_exists($machine_name, $values)) {
          continue;
        }
        $this->config('mobile_native_share.settings')
          ->set("entities.{$entityId}.{$machine_name}.title", $values[$machine_name . '_title']);
        $this->config('mobile_native_share.settings')
          ->set("entities.{$entityId}.{$machine_name}.description", $values[$machine_name . '_description']);
        $this->config('mobile_native_share.settings')
          ->set("entities.{$entityId}.{$machine_name}.enable", $values[$machine_name]);
      }

    }
    $this->config('mobile_native_share.settings')->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Get entities content.
   *
   * @return array
   *   Array of entities content.
   */
  public static function getContentEntities() {
    $content_entity_types = [];
    $entity_type_definitions = \Drupal::entityTypeManager()->getDefinitions();
    /* @var $definition \Drupal\Core\Entity\EntityTypeInterface */
    foreach ($entity_type_definitions as $definition) {
      if ($definition instanceof ContentEntityType) {
        $content_entity_types[] = $definition;
      }
    }

    return $content_entity_types;
  }

}
