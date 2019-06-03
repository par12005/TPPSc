<?php

/**
 * @file
 */

/**
 *
 */
function page_1_validate_form(&$form, &$form_state) {

  if ($form_state['submitted'] == '1') {

    $form_values = $form_state['values'];
    $doi = $form_values['doi'];
    $organism = $form_values['organism'];
    $organism_number = $form_values['organism']['number'];

    if (!$doi) {
      form_set_error("DOI: field is required.");
    }
    else {
      $url = "http://api.datadryad.org/mn/object/doi:" . $doi;
      $response_xml_data = file_get_contents($url);

      preg_match('/<dcterms:title>(.*)<\/dcterms:title>/', $response_xml_data, $matches);
      $form_state['values']['publication']['title'] = $matches[1];

      preg_match_all('/<dcterms:creator>(.*)<\/dcterms:creator>/', $response_xml_data, $matches);
      $secondary_authors = array_slice($matches[1], 1);
      $parts = explode(',', $matches[1][0]);
      $form_state['values']['primaryAuthor'] = trim(implode(" ", array_slice($parts, 1)) . " {$parts[0]}");
      if (!empty($secondary_authors)) {
        $form_state['values']['publication']['secondaryAuthors'] = array();
        foreach ($secondary_authors as $author) {
          $parts = explode(',', $author);
          $form_state['values']['publication']['secondaryAuthors'][] = trim(implode(" ", array_slice($parts, 1)) . " {$parts[0]}");
        }
        $form_state['values']['publication']['secondaryAuthors']['number'] = count($secondary_authors);
        $form_state['values']['publication']['secondaryAuthors']['check'] = FALSE;
      }

      preg_match('/<dcterms:available>(.*)<\/dcterms:available>/', $response_xml_data, $matches);
      $form_state['values']['publication']['year'] = explode('-', $matches[1])[0];

      preg_match('/<dcterms:description>(.*)<\/dcterms:description>/', $response_xml_data, $matches);
      $form_state['values']['publication']['abstract'] = $matches[1];

      $url = "https://datadryad.org/resource/doi:" . $doi . "/mets.xml?show=full";
      $response_xml_data = file_get_contents($url);

      preg_match('/element="publicationName">(.*)<\/dim:field>/', $response_xml_data, $matches);
      $form_state['values']['publication']['journal'] = $matches[1];
    }

    for ($i = 1; $i <= $organism_number; $i++) {
      $name = $organism[$i];

      if ($name == '') {
        form_set_error("organism[$i", "Tree Species $i: field is required.");
      }
      else {
        $name = explode(" ", $name);
        $genus = $name[0];
        $species = implode(" ", array_slice($name, 1));
        $name = implode(" ", $name);
        $empty_pattern = '/^ *$/';
        $correct_pattern = '/^[A-Z|a-z|.| |-]+$/';
        if (!isset($genus) or !isset($species) or preg_match($empty_pattern, $genus) or preg_match($empty_pattern, $species) or !preg_match($correct_pattern, $genus) or !preg_match($correct_pattern, $species)) {
          form_set_error("organism[$i", check_plain("Tree Species $i: please provide both genus and species in the form \"<genus> <species>\"."));
        }
      }
    }
  }
}
