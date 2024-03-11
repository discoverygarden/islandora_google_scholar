<?php

/**
 * @file
 * Contains islandora_google_scholar.tokens.inc.
 *
 * This file provides additional islandora and islandora_google_scholar tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_token_info().
 */
function islandora_google_scholar_token_info() {
  $type = [
    'name' => t('Islandora Tokens'),
    'description' => t('Tokens for Islandora objects.'),
  ];

  $node['gs_publication_date'] = [
    'name' => t("GS Publication Date"),
    'description' => t('Publication Date for Google Scholar'),
  ];

  $node['gs_author'] = [
    'name' => t("GS Author"),
    'description' => t('Author for Google Scholar'),
  ];

  return [
    'types' => ['islandoratokens' => $type],
    'tokens' => ['islandoratokens' => $node],
  ];
}

/**
 * Implements hook_tokens().
 */
function islandora_google_scholar_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'islandoratokens' && !empty($data['node'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'gs_publication_date':
          $replacements[$original] = islandora_google_scholar_publication_date($data['node'], $original);
          break;

        case 'gs_author':
          $replacements[$original] = islandora_google_scholar_author($data['node']);
          break;
      }
    }
  }
  return $replacements;
}

/**
 * Gets Publication Date for Google Scholar.
 *
 * Show Date Issued. If not available show Date Created.
 *
 * @param object $node
 *   A core drupal node object.
 *
 * @return string
 *   Date.
 */
function islandora_google_scholar_publication_date($node, $original) {
  if (!empty($node->field_edtf_date_issued) && !$node->field_edtf_date_issued->isEmpty()) {
    return controlled_access_terms_normalize_date_format($node, 'field_edtf_date_issued', $original);
  }
  elseif (!empty($node->field_edtf_date_created) && !$node->field_edtf_date_created->isEmpty()) {
    return controlled_access_terms_normalize_date_format($node, 'field_edtf_date_created', $original);
  }
}

/**
 * Gets Author for Google Scholar.
 *
 * Retrieves authors from multiple fields and returns a
 * comma-separated list of unique authors.
 *
 * @param object $node
 *   The node object containing the fields.
 * @return string
 *   The comma-separated list of unique authors.
 */
function islandora_google_scholar_author($node) {
  $authors = [];

  // Process the 'field_linked_agent' field.
  $authors = process_field_for_authors($node->field_linked_agent, $authors);

  // Process the 'field_organizations' field.
  $authors = process_field_for_authors($node->field_organizations, $authors);

  return implode(', ', array_unique($authors));
}

/**
 * Process a field to extract authors.
 *
 * @param object $field
 *   The field object to process.
 * @param array $authors
 *   The array containing authors.
 *
 * @return array
 *   The updated array of authors.
 */
function process_field_for_authors($field, array $authors) {
  if (!$field->isEmpty()) {
    $fields_data = $field->getValue();

    foreach ($fields_data as $field_data) {
      if ($field_data['rel_type'] == 'relators:aut') {
        $term = Term::load($field_data['target_id']);
        if (!empty($term)) {
          $authors[] = $term->getName();
        }
      }
    }
  }

  return $authors;
}
