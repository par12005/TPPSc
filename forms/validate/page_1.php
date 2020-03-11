<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_page_1_validate_form(&$form, &$form_state) {

  if ($form_state['submitted'] == '1') {

    $form_values = $form_state['values'];
    $doi = $form_values['doi'] ?? NULL;
    $organism = $form_values['organism'];
    $organism_number = $form_values['organism']['number'];
    $old_tgdr = $form_state['saved_values']['frontpage']['old_tgdr'] ?? NULL;

    if (empty($old_tgdr)) {
      if (!$doi) {
        form_set_error('doi', "DOI: field is required.");
      }
    }

    if (!$form_values['primaryAuthor']) {
      form_set_error('primaryAuthor', 'Primary Author: field is required.');
    }
    if (!$form_values['publication']['title']) {
      form_set_error('publication][title', 'Title of Publication: field is required.');
    }
    if (!$form_values['publication']['year']) {
      form_set_error('publication][year', 'Year of Publication: field is required.');
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

    if (!form_get_errors()) {
      $form_state['stats']['author_count'] = $form_state['values']['publication']['secondaryAuthors']['number'] + 1;
      $form_state['stats']['species_count'] = $organism_number;
    }
  }
}
