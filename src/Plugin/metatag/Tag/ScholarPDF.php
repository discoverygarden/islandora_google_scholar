<?php

namespace Drupal\islandora_google_scholar\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Appends the first attached 'research output' PDF as a citation_pdf_url.
 *
 * @MetatagTag(
 *   id = "citation_first_attached_research_output",
 *   label = @Translation("Citation PDF URL (First Attached Research Output)"),
 *   description = @Translation("Append the first attached research output as the citation_pdf_url."),
 *   name = "citation_pdf_url",
 *   group = "islandora_google_scholar",
 *   weight = 1,
 *   type = "uri",
 *   multiple = FALSE,
 *   secure = TRUE,
 *   long = FALSE,
 * )
 */
class ScholarPDF extends MetaNameBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The canonical URL for the first PDF on the entity this tag refers to.
   *
   * @see ScholarPDF::getFirstPdfUrl()
   *
   * @var string|null
   */
  protected $firstPDFUrl = NULL;

  /**
   * The node this applies to.
   *
   * If we are not in the context of a node, this will be FALSE.
   *
   * @see ScholarPDF::getNode()
   *
   * @var Drupal\node\NodeInterface|bool
   */
  protected $node = NULL;

  /**
   * Constructor for the plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin properties.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->getNode();
    $this->getFirstPdfUrl();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []) {
    return [
      '#type' => 'checkbox',
      '#title' => $this->label(),
      '#default_value' => $this->value,
      '#description' => $this->description(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function value() {
    // If we are in the context of a node, we want the first PDF URL. Otherwise,
    // we want the regular value.
    if (!$this->getNode() instanceof NodeInterface) {
      return $this->value;
    }
    return $this->getFirstPdfUrl();
  }

  /**
   * Gets the node that this tag applies to.
   *
   * @return Drupal\node\NodeInterface|bool
   *   The node that this tag applies to, or FALSE if we are not in the context
   *   of a node.
   */
  public function getNode() {
    if (is_null($this->node)) {
      $this->node = $this->request->attributes->get('node');
      if (!is_null($this->node) && !$this->node instanceof NodeInterface) {
        $this->node = $this->entityTypeManager
          ->getStorage('node')
          ->load($this->node);
      }
      if (!$this->node) {
        $this->node = FALSE;
      }
    }
    return $this->node;
  }

  /**
   * Gets the first attached 'research output' PDF from the request node.
   *
   * @return string
   *   The first canonical URL of an attached Research Output PDF media to the
   *   node from the request. If no such thing exists, an empty string will be
   *   returned.
   */
  public function getFirstPdfUrl() {
    if (is_null($this->firstPDFUrl)) {
      $this->firstPDFUrl = '';
      $node = $this->getNode();
      // Early optimization.
      if (!$node instanceof NodeInterface) {
        return $this->firstPDFUrl;
      }
      else {
        $research_output_terms = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->getQuery()
          ->condition('field_external_uri', 'http://pcdm.org/use#ResearchOutput')
          ->execute();
        $term = reset($research_output_terms);
        if ($term) {
          $research_output_media = (array) $this->entityTypeManager
            ->getStorage('media')
            ->getQuery()
            ->condition('field_media_of', $node->id())
            ->condition('field_media_use', $term)
            ->sort('field_weight')
            ->execute();
          foreach ($this->entityTypeManager->getStorage('media')->loadMultiple($research_output_media) as $media) {
            if ($media) {
              $file = $this->entityTypeManager
                ->getStorage('file')
                ->load($media->getSource()->getSourceFieldValue($media));
              if ($file && $file->getMimeType() == 'application/pdf') {
                // Only attach if viewable, but this should still be the end of
                // the line.
                if ($file->access('view')) {
                  $this->firstPDFUrl = $file->createFileUrl(FALSE);
                }
                return $this->firstPDFUrl;
              }
            }
          }
        }
      }
    }
    return $this->firstPDFUrl;
  }

}
