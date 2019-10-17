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
    $doi = $form_values['doi'];
    $organism = $form_values['organism'];
    $organism_number = $form_values['organism']['number'];
    $old_tgdr = !empty($form_state['saved_values']['frontpage']['use_old_tgdr']) ? $form_state['saved_values']['frontpage']['old_tgdr'] : NULL;

    if (empty($old_tgdr)) {
      if (!$doi) {
        form_set_error("DOI: field is required.");
      }
      else {
        tppsc_save_doi_pub($form_state);
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
        $form_state['stats']['author_count'] = $secondary_authors_number + 1;
        $form_state['stats']['species_count'] = $organism_number;
      }
    }
  }
}
