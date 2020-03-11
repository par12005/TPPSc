<?php

/**
 * @file
 */

require_once 'page_1_helper.php';
require_once 'page_1_ajax.php';

/**
 *
 */
function tppsc_page_1_create_form(&$form, &$form_state) {

  $saved_values = $form_state['saved_values'][TPPS_PAGE_1] ?? array();

  if (empty($form_state['saved_values']['frontpage']['use_old_tgdr'])) {
    $form['doi'] = array(
      '#type' => 'textfield',
      '#title' => t('DOI: *'),
      '#ajax' => array(
        'callback' => 'tppsc_ajax_doi_callback',
        'wrapper' => "doi-wrapper",
      ),
      '#description' => 'Example: 123.456/dryad.789',
    );

    $doi = tpps_get_ajax_value($form_state, array('doi'));
  
    $species = array();
    $form['primaryAuthor'] = array(
      '#type' => 'hidden',
      '#disabled' => TRUE,
    );
    $form['organization'] = array(
      '#type' => 'hidden',
      '#disabled' => TRUE,
    );
    $form['publication'] = array(
      '#type' => 'hidden',
    );
    $form['organism'] = array(
      '#type' => 'fieldset',
    );
    if (!empty($doi)) {
      module_load_include('php', 'tpps', 'forms/build/page_1');
      $doi_info = tppsc_doi_info($doi);
      $species = $doi_info['species'] ?? array();

      $form_state['saved_values'][TPPS_PAGE_1]['publication']['status'] = 'Published';
      $tpps_form = array();
      $tpps_form = tpps_page_1_create_form($tpps_form, $form_state);
      $form['primaryAuthor'] = $tpps_form['primaryAuthor'];
      $form['organization'] = $tpps_form['organization'];
      $form['publication'] = $tpps_form['publication'];

      $form['organization']['#title'] = t('Organization:');
      $form['publication']['journal']['#title'] = t('Journal:');
      $form['publication']['status']['#title'] = t('Publication Status:');
      $form['publication']['status']['#disabled'] = TRUE;

      $form['doi']['#suffix'] = "The publication has been successfully loaded from Dryad<br>";
      $form['primaryAuthor']['#default_value'] = $doi_info['primary'] ?? "";
      $form['publication']['title']['#default_value'] = $doi_info['title'] ?? "";
      $form['publication']['abstract']['#default_value'] = $doi_info['abstract'] ?? "";
      $form['publication']['journal']['#default_value'] = $doi_info['journal'] ?? "";
      $form['publication']['year']['#default_value'] = $doi_info['year'] ?? "";
    }

    $org_number = tpps_get_ajax_value($form_state, array('organism', 'number'));
    if (!isset($org_number) and !empty($species)) {
      $org_number = $form_state['values']['organism']['number'] = count($species);
    }
    for ($i = 1; $i <= $org_number; $i++) {
      $org = tpps_get_ajax_value($form_state, array('organism', $i));
      if (empty($org) and !empty($species[$i - 1])) {
        $form_state['values']['organism'][$i] = $species[$i - 1];
      }
    }
    tppsc_organism($form, $form_state);

    $form['#prefix'] = '<div id="doi-wrapper">' . $form['#prefix'];
    $form['#suffix'] = (!empty($form['#suffix'])) ? $form['#suffix'] . '</div>' : '</div>';

    $form['Save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#prefix' => '<div class="input-description">* : Required Field</div>',
    );

    $form['Next'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
    );

    return $form;
  }

  module_load_include('php', 'tpps', 'forms/build/page_1');

  $form_state['saved_values'][TPPS_PAGE_1]['publication']['status'] = 'Published';
  $tpps_form = array();
  $tpps_form = tpps_page_1_create_form($tpps_form, $form_state);

  $form_state['ids']['project_id'] = chado_select_record('project_dbxref', array('project_id'), array(
    'dbxref_id' => $form_state['dbxref_id'],
  ))[0]->project_id;

  $pub_id = chado_select_record('project_pub', array('pub_id'), array(
    'project_id' => $form_state['ids']['project_id'],
  ))[0]->pub_id;

  $pub = chado_select_record('pub', array('*'), array(
    'pub_id' => $pub_id,
  ))[0];
  $title_default = $saved_values['publication']['title'] ?? $pub->title;
  $year_default = $saved_values['publication']['year'] ?? $pub->pyear;

  $abstract = chado_select_record('pubprop', array('value'), array(
    'pub_id' => $pub_id,
    'type_id' => array(
      'name' => 'Abstract',
      'cv_id' => array(
        'name' => 'tripal_pub',
      ),
    ),
  ))[0]->value;
  $abs_default = $saved_values['publication']['abstract'] ?? $abstract;

  $primary_author = chado_select_record('pubauthor', array('givennames', 'surname'), array(
    'pub_id' => $pub_id,
    'rank' => 0,
  ))[0];
  $primary_default = $saved_values['primaryAuthor'] ?? "$primary_author->givennames $primary_author->surname";

  $secondary_authors = chado_select_record('pubauthor', array('givennames', 'surname'), array(
    'pub_id' => $pub_id,
    'rank' => array(
      'op' => '!=',
      'data' => 0,
    ),
  ));

  $organisms = chado_query('SELECT genus, species '
    . 'FROM chado.organism WHERE organism_id IN ('
      . 'SELECT DISTINCT organism_id '
      . 'FROM chado.stock '
      . 'WHERE stock_id IN ('
        . 'SELECT stock_id '
        . 'FROM chado.project_stock '
        . 'WHERE project_id = :project_id));', array(':project_id' => $form_state['ids']['project_id']));

  $form['primaryAuthor'] = $tpps_form['primaryAuthor'];
  $form['primaryAuthor']['#default_value'] = $primary_default;

  $form['organization'] = $tpps_form['organization'];

  $form['publication'] = $tpps_form['publication'];
  $form['publication']['title']['#default_value'] = $title_default;
  $form['publication']['year']['#default_value'] = $year_default;
  $form['publication']['abstract']['#default_value'] = $abs_default;

  $form['organism'] = $tpps_form['organism'];

  $form['organization']['#title'] = t('Organization:');
  $form['publication']['journal']['#title'] = t('Journal:');
  $form['publication']['status']['#title'] = t('Publication Status:');
  $form['publication']['status']['#disabled'] = TRUE;

  $i = 1;
  foreach ($secondary_authors as $author) {
    $form['publication']['secondaryAuthors'][$i]['#default_value'] = $saved_values['publication']['secondaryAuthors'][$i] ?? "$author->givennames $author->surname";
    $i++;
  }

  $i = 1;
  foreach ($organisms as $org) {
    $form['organism'][$i]['#default_value'] = $saved_values['organism'][$i] ?? "$org->genus $org->species";
    $i++;
  }
  $form['organism']['number'] = array(
    '#type' => 'hidden',
    '#value' => tpps_get_ajax_value($form_state, array('organism', 'number'), $i - 1),
  );

  $form['Save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#prefix' => '<div class="input-description">* : Required Field</div>',
  );

  $form['Next'] = array(
    '#type' => 'submit',
    '#value' => t('Next'),
  );

  return $form;
}
