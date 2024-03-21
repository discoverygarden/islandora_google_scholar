<?php

namespace Drupal\islandora_google_scholar\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\islandora\IslandoraUtils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Provides a block with configurable altmetrics options.
 *
 * @Block(
 *   id = "altmetrics_block",
 *   admin_label = @Translation("Altmetrics Block"),
 *   category = @Translation("Custom")
 * )
 */
class AltmetricsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Definition of path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * Constructs a new AltmetricsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\path_alias\AliasManagerInterface $pathAliasManager
   *   The path alias object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, AliasManagerInterface $pathAliasManager,) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->pathAliasManager = $pathAliasManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Get the altmetrics options.
    $options = $this->getAltmetricsOptions();

    // Add a select list field for altmetrics options.
    $form['data_badge_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Badge Type'),
      '#description' => $this->t('Select the badge type to display.'),
      '#options' => $options,
      '#default_value' => $this->configuration['data_badge_type'] ?? 'donut',
    ];

    // Add a select list field for data-badge-popover options.
    $form['data_badge_popover'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Badge Popover'),
      '#description' => $this->t('Select the position of the popover for the badge.'),
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $this->configuration['data_badge_popover'] ?? 'left',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save the configuration of the block.
    $this->configuration['data_badge_type'] = $form_state->getValue('data_badge_type');
    $this->configuration['data_badge_popover'] = $form_state->getValue('data_badge_popover');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the data value.
    $data = $this->getData();

    if ($data === NULL) {
      return [];
    }

    // Get the selected altmetrics option and data badge popover option.
    $data_badge_type = $this->configuration['data_badge_type'] ?? 'donut';
    $data_badge_popover = $this->configuration['data_badge_popover'] ?? 'left';

    // Build the block content based on the selected altmetrics
    // option and data badge popover option.
    $content = [
      '#theme' => 'altmetrics_block',
      '#data_badge_type' => $data_badge_type,
      '#data_badge_popover' => $data_badge_popover,
      '#data' => $data,
    ];

    // Attach altmetrics JS library.
    $content['#attached']['library'][] = 'islandora_google_scholar/altmetrics-js-library';

    return $content;
  }

  /**
   * Retrieves the value of the data attribute based on node fields.
   *
   * @return string
   *   The value of the data attribute.
   */
  protected function getData() {
    // Get the current node.
    $node = $this->routeMatch->getParameter('node');

    // Check if the current page is an Islandora object.
    if ($node instanceof NodeInterface && $node->hasField(IslandoraUtils::MEMBER_OF_FIELD)) {
      // Get the value of the 'field_doi' field.
      if (!empty($node->get('field_doi')) && !$node->get('field_doi')->isEmpty()) {
        return "data-doi=" . $node->get('field_doi')->value;
      }
      // Get the value of the 'field_handle' field.
      elseif (!empty($node->get('field_handle')) && !$node->get('field_handle')->isEmpty()) {
        return "data-handle=" . $node->get('field_handle')->value;
      }
      // Get the value of the 'field_pubmed_number' field.
      elseif (!empty($node->get('field_pubmed_number')) && !$node->get('field_pubmed_number')->isEmpty()) {
        return "data-pmid=" . $node->get('field_pubmed_number')->value;
      }
      // Get the value of the 'field_isbn' field.
      elseif (!empty($node->get('field_isbn')) && !$node->get('field_isbn')->isEmpty()) {
        return "data-isbn=" . $node->get('field_isbn')->value;
      }
      // If none of the above fields are available,
      // generate data attribute from the node URL.
      else {
        $node_url = $this->pathAliasManager->getAliasByPath('/node/' . $node->id());
        return "data-uri=" . Url::fromUserInput($node_url)->setAbsolute()->toString();
      }
    }
    else {
      return '';
    }
  }

  /**
   * Retrieves the available altmetrics options.
   *
   * @return array
   *   An array of altmetrics options.
   */
  protected function getAltmetricsOptions() {
    return [
      'donut' => $this->t('Donut'),
      'medium-donut' => $this->t('Medium Donut'),
      'large-donut' => $this->t('Large Donut'),
      '1' => '1',
      '4' => '4',
      'bar' => $this->t('Bar'),
      'medium-bar' => $this->t('Medium Bar'),
      'large-bar' => $this->t('Large Bar'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Get the current node ID.
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      $node_id = $node->id();
    }

    // Initialize cache tags with the default tags.
    $cache_tags = parent::getCacheTags();

    // If a node ID is available, add a cache tag specific to that node.
    if (!empty($node_id)) {
      $cache_tags[] = 'node:' . $node_id;
    }

    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Initialize cache contexts with the default contexts.
    $cache_contexts = parent::getCacheContexts();

    // Add 'url' cache context to cache the block per URL.
    $cache_contexts[] = 'url';

    // Always include 'user.node_grants:view' context.
    $cache_contexts[] = 'user.node_grants:view';

    return $cache_contexts;
  }

}
