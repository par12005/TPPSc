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

  $result = array();
  $url = "http://api.datadryad.org/mn/object/doi:" . $doi;
  $response_xml_data = file_get_contents($url);

  preg_match('/<dcterms:title>(Data from: )?(.*)<\/dcterms:title>/', $response_xml_data, $matches);
  if (!empty($matches[2])) {
    $result['title'] = $matches[2];

    preg_match_all('/<dcterms:creator>(.*)<\/dcterms:creator>/', $response_xml_data, $matches);
    $secondary_authors = array_slice($matches[1], 1);
    $parts = explode(',', $matches[1][0]);
    $result['primary'] = trim(implode(" ", array_slice($parts, 1)) . " {$parts[0]}");
    if (!empty($secondary_authors)) {
      $result['second'] = array();
      foreach ($secondary_authors as $author) {
        $parts = explode(',', $author);
        $result['second'][] = trim(implode(" ", array_slice($parts, 1)) . " {$parts[0]}");
      }
      $result['second']['number'] = count($secondary_authors);
      $result['second']['check'] = FALSE;
    }
    preg_match('/<dcterms:available>(.*)<\/dcterms:available>/', $response_xml_data, $matches);
    $result['year'] = explode('-', $matches[1])[0];

    preg_match('/<dcterms:description>(.*)<\/dcterms:description>/', $response_xml_data, $matches);
    $result['abstract'] = $matches[1];

    preg_match_all('/<dwc:scientificName>(.*)<\/dwc:scientificName>/', $response_xml_data, $matches);
    $result['species'] = $matches[1];

    $url = "https://datadryad.org/resource/doi:" . $doi . "/mets.xml?show=full";
    $response_xml_data = file_get_contents($url);

    preg_match('/element="publicationName">(.*)<\/dim:field>/', $response_xml_data, $matches);
    $result['journal'] = $matches[1] ?? NULL;
  }
  return $result;
}
