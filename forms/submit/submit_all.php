<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_submit_all($accession) {

  $form_state = tpps_load_submission($accession);
  $form_state['status'] = 'Submission Job Running';
  tpps_update_submission($form_state, array('status' => 'Submission Job Running'));
  if (empty($form_state['saved_values']['frontpage']['use_old_tgdr'])) {
    tpps_submission_clear_db($accession);
  }
  $project_id = $form_state['ids']['project_id'] ?? NULL;
  $transaction = db_transaction();

  try {
    $form_state = tpps_load_submission($accession);
    $values = $form_state['saved_values'];
    $firstpage = $values[TPPS_PAGE_1];
    $form_state['file_rank'] = 0;
    $form_state['ids'] = array();

    $project_record = array(
      'name' => $firstpage['publication']['title'],
      'description' => $firstpage['publication']['abstract'],
    );
    if (!empty($project_id)) {
      $project_record['project_id'] = $project_id;
    }
    $form_state['ids']['project_id'] = tpps_chado_insert_record('project', $project_record);

    module_load_include('php', 'tpps', 'forms/submit/submit_all');
    tpps_tripal_entity_publish('Project', array($firstpage['publication']['title'], $form_state['ids']['project_id']));

    tppsc_submit_page_1($form_state);

    tppsc_submit_page_2($form_state);

    tppsc_submit_page_3($form_state);

    tppsc_submit_page_4($form_state);

    tpps_update_submission($form_state);

    tpps_file_parsing($accession);

    tpps_submission_rename_files($accession);
    $form_state = tpps_load_submission($accession);
    $form_state['status'] = 'Approved';
    tpps_update_submission($form_state, array('status' => 'Approved'));
  }
  catch (Exception $e) {
    $transaction->rollback();
    $form_state = tpps_load_submission($accession);
    $form_state['status'] = 'Pending Approval';
    tpps_update_submission($form_state, array('status' => 'Pending Approval'));
    watchdog_exception('tppsc', $e);
  }
}

/**
 *
 */
function tppsc_submit_page_1(&$form_state) {

  $project_id = $form_state['ids']['project_id'];
  $dbxref_id = $form_state['dbxref_id'];
  $firstpage = $form_state['saved_values'][TPPS_PAGE_1];

  tpps_chado_insert_record('project_dbxref', array(
    'project_id' => $project_id,
    'dbxref_id' => $dbxref_id,
  ));

  tpps_chado_insert_record('contact', array(
    'name' => $firstpage['primaryAuthor'],
    'type_id' => array(
      'cv_id' => array(
        'name' => 'tripal_contact',
      ),
      'name' => 'Person',
      'is_obsolete' => 0,
    ),
  ));

  $author_string = $firstpage['primaryAuthor'];
  if (!$firstpage['publication']['secondaryAuthors']['check'] and $firstpage['publication']['secondaryAuthors']['number'] != 0) {
    // Some older TPPSc submissions have zero-indexed author arrays.
    if (array_key_exists(0, $firstpage['publication']['secondaryAuthors'])) {
      array_unshift($firstpage['publication']['secondaryAuthors'], '');
    }
    for ($i = 1; $i <= $firstpage['publication']['secondaryAuthors']['number']; $i++) {
      tpps_chado_insert_record('contact', array(
        'name' => $firstpage['publication']['secondaryAuthors'][$i],
        'type_id' => array(
          'cv_id' => array(
            'name' => 'tripal_contact',
          ),
          'name' => 'Person',
          'is_obsolete' => 0,
        ),
      ));
      $author_string .= "; {$firstpage['publication']['secondaryAuthors'][$i]}";
    }
  }

  $publication_id = tpps_chado_insert_record('pub', array(
    'title' => $firstpage['publication']['title'],
    'series_name' => $firstpage['publication']['journal'],
    'type_id' => array(
      'cv_id' => array(
        'name' => 'tripal_pub',
      ),
      'name' => 'Journal Article',
      'is_obsolete' => 0,
    ),
    'pyear' => $firstpage['publication']['year'],
    'uniquename' => "$author_string {$firstpage['publication']['title']}. {$firstpage['publication']['journal']}; {$firstpage['publication']['year']}",
  ));
  tpps_tripal_entity_publish('Publication', array($firstpage['publication']['title'], $publication_id));

  tpps_chado_insert_record('project_pub', array(
    'project_id' => $project_id,
    'pub_id' => $publication_id,
  ));

  $names = explode(" ", $firstpage['primaryAuthor']);
  $first_name = $names[0];
  $last_name = implode(" ", array_slice($names, 1));

  tpps_chado_insert_record('pubauthor', array(
    'pub_id' => $publication_id,
    'rank' => '0',
    'surname' => $last_name,
    'givennames' => $first_name,
  ));

  if (!$firstpage['publication']['secondaryAuthors']['check'] and $firstpage['publication']['secondaryAuthors']['number'] != 0) {
    for ($i = 1; $i <= $firstpage['publication']['secondaryAuthors']['number']; $i++) {
      $names = explode(" ", $firstpage['publication']['secondaryAuthors'][$i]);
      $first_name = $names[0];
      $last_name = implode(" ", array_slice($names, 1));
      tpps_chado_insert_record('pubauthor', array(
        'pub_id' => $publication_id,
        'rank' => "$i",
        'surname' => $last_name,
        'givennames' => $first_name,
      ));
    }
  }

  $organism_number = $firstpage['organism']['number'];

  for ($i = 1; $i <= $organism_number; $i++) {
    $parts = explode(" ", $firstpage['organism'][$i]);
    $genus = $parts[0];
    $species = implode(" ", array_slice($parts, 1));
    if (isset($parts[2]) and ($parts[2] == 'var.' or $parts[2] == 'subsp.')) {
      $infra = implode(" ", array_slice($parts, 2));
    }
    else {
      $infra = NULL;
    }
    $record = array(
      'genus' => $genus,
      'species' => $species,
      'infraspecific_name' => $infra,
    );

    if (preg_match('/ x /', $species)) {
      $record['type_id'] = array(
        'name' => 'speciesaggregate',
        'cv_id' => array(
          'name' => 'taxonomic_rank',
        ),
      );
    }
    $form_state['ids']['organism_ids'][$i] = tpps_chado_insert_record('organism', $record);

    $code_query = chado_select_record('organismprop', array('value'), array(
      'type_id' => array(
        'name' => 'organism 4 letter code',
      ),
      'organism_id' => $form_state['ids']['organism_ids'][$i],
    ));

    if (empty($code_query)) {
      $g_offset = 0;
      $s_offset = 0;
      do {
        if (isset($trial_code)) {
          if ($s_offset < strlen($species) - 2) {
            $s_offset++;
          }
          elseif ($g_offset < strlen($genus) - 2) {
            $s_offset = 0;
            $g_offset++;
          }
          else {
            throw new Exception("TPPS was unable to create a 4 letter species code for the species '$genus $species'.");
          }
        }
        $trial_code = substr($genus, $g_offset, 2) . substr($species, $s_offset, 2);
        $new_code_query = chado_select_record('organismprop', array('value'), array(
          'type_id' => array(
            'name' => 'organism 4 letter code',
          ),
          'value' => $trial_code,
        ));
      } while (!empty($new_code_query));

      tpps_chado_insert_record('organismprop', array(
        'organism_id' => $form_state['ids']['organism_ids'][$i],
        'type_id' => chado_get_cvterm(array('name' => 'organism 4 letter code'))->cvterm_id,
        'value' => $trial_code,
      ));
    }

    tpps_chado_insert_record('project_organism', array(
      'organism_id' => $form_state['ids']['organism_ids'][$i],
      'project_id' => $project_id,
    ));

    tpps_tripal_entity_publish('Organism', array("$genus $species", $form_state['ids']['organism_ids'][$i]));
  }
}

/**
 *
 */
function tppsc_submit_page_2(&$form_state) {
  module_load_include('php', 'tpps', 'forms/submit/submit_all');
  tpps_submit_page_2($form_state);
}

/**
 *
 */
function tppsc_submit_page_3(&$form_state) {

  module_load_include('php', 'tpps', 'forms/submit/submit_all');
  tpps_submit_page_3($form_state);
}

/**
 *
 */
function tppsc_submit_page_4(&$form_state) {

  module_load_include('php', 'tpps', 'forms/submit/submit_all');
  tpps_submit_page_4($form_state);
}
