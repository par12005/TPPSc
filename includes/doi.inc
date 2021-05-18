<?php

/**
 * @file
 * Defines DOI-related functions for TPPSc.
 */

/**
 * Loads info about a Dryad DOI.
 *
 * @param string $doi
 *   The doi being loaded.
 *
 * @return mixed
 *   The retrieved information, or FALSE on failure.
 */
function tppsc_doi_info($doi) {
  if (empty($doi)) {
    return FALSE;
  }

  if (!preg_match(tppsc_doi_regex(), $doi)) {
    drupal_set_message(t('Invalid DOI format. Example DOI: 123.456/dryad.789'), 'warning');
    return FALSE;
  }

  $result = array();
  $url = "http://datadryad.org/api/v2/datasets/doi%3A" . urlencode($doi);
  $response_json_data = json_decode(file_get_contents($url));

  if (!empty($response_json_data)) {
    $result['title'] = $response_json_data->title;

    $authors = $response_json_data->authors;
    $result['primary'] = trim(implode(" ", array($authors[0]->firstName, $authors[0]->lastName)));
    $result['second'] = array();
    $authors = array_slice($authors, 1);
    foreach ($authors as $author) {
      $result['second'][] = trim(implode(" ", array($author->firstName, $author->lastName)));
    }
    $result['second']['number'] = count($authors);
    $result['second']['check'] = FALSE;

    $result['year'] = explode('-', $response_json_data->publicationDate)[0];

    $result['abstract'] = $response_json_data->abstract;

    $result['species'] = array();
    foreach ($response_json_data->keywords as $keyword) {
      if (preg_match('/^([A-Z][a-z]* [a-z]+)$/', $keyword, $matches)) {
        $result['species'][] = $matches[1];
      }
    }
  }
  return $result;
}

/**
 * Returns expression for matching DOIs.
 *
 * @return string
 *   The regular expression pattern for DOIs.
 */
function tppsc_doi_regex() {
  return '/^10\.\d{4,9}[\-._;()\/:A-Za-z0-9]+$/';
}
