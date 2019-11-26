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

  if (isset($form_state['saved_values'][TPPS_PAGE_1])) {
    $values = $form_state['saved_values'][TPPS_PAGE_1];
  }
  else {
    $values = array();
  }

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
  
    $form['#prefix'] = '<div id="doi-wrapper">' . $form['#prefix'];
    $form['#suffix'] = (!empty($form['#suffix'])) ? $form['#suffix'] . '</div>' : '</div>';
  
    if (!empty($form_state['values']['doi'])) {
      $doi = $form_state['values']['doi'];
      $form_state['saved_values'][TPPS_PAGE_1]['doi'] = $form_state['values']['doi'];
    }
    elseif (!empty($form_state['saved_values'][TPPS_PAGE_1]['doi'])) {
      $doi = $form_state['saved_values'][TPPS_PAGE_1]['doi'];
    }
    elseif (!empty($form_state['complete form']['doi']['#value'])) {
      $doi = $form_state['complete form']['doi']['#value'];
    }
  
    $species = array();
  
    if (!empty($doi)) {
  
      $url = "http://api.datadryad.org/mn/object/doi:" . $doi;
      $response_xml_data = file_get_contents($url);
  
      preg_match('/<dcterms:title>(Data from: )?(.*)<\/dcterms:title>/', $response_xml_data, $matches);
      $title = $matches[2];
      if (!empty($title)) {
        $form['doi']['#suffix'] = "The publication has been successfully loaded from Dryad:<br>";
        $form['doi']['#suffix'] .= "Title: $title<br><br>";
  
        preg_match_all('/<dcterms:creator>(.*)<\/dcterms:creator>/', $response_xml_data, $matches);
        $primary_author = $matches[1][0];
        $form['doi']['#suffix'] .= "Primary Author: $primary_author<br><br>";
  
        preg_match_all('/<dwc:scientificName>(.*)<\/dwc:scientificName>/', $response_xml_data, $matches);
        $species = $matches[1];
      }
    }
  
    tppsc_organism($form, $form_state, $species);
  
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

  $form_state['ids']['project_id'] = chado_select_record('project_dbxref', array('project_id'), array(
    'dbxref_id' => $form_state['dbxref_id'],
  ))[0]->project_id;

  $pub_id = chado_select_record('project_pub', array('pub_id'), array(
    'project_id' => $form_state['ids']['project_id'],
  ))[0]->pub_id;

  $pub = chado_select_record('pub', array('*'), array(
    'pub_id' => $pub_id,
  ))[0];

  $abstract = chado_select_record('pubprop', array('value'), array(
    'pub_id' => $pub_id,
    'type_id' => array(
      'name' => 'Abstract',
      'cv_id' => array(
        'name' => 'tripal_pub',
      ),
    ),
  ))[0]->value;

  $primary_author = chado_select_record('pubauthor', array('givennames', 'surname'), array(
    'pub_id' => $pub_id,
    'rank' => 0,
  ))[0];

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

  $form['primaryAuthor'] = array(
    '#type' => 'textfield',
    '#title' => t('Primary Author:'),
    '#value' => "$primary_author->givennames $primary_author->surname",
    '#disabled' => TRUE,
  );

  $form['publication'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    'title' => array(
      '#type' => 'textfield',
      '#title' => t('Publication Title:'),
      '#value' => $pub->title,
    ),
    'year' => array(
      '#type' => 'textfield',
      '#title' => t('Publication Year:'),
      '#value' => $pub->pyear,
    ),
    'abstract' => array(
      '#type' => 'textarea',
      '#title' => t('Publication Abstract:'),
      '#value' => $abstract,
    ),
    'secondaryAuthors' => array(
      '#type' => 'fieldset',
      'number' => array(
        '#type' => 'hidden',
        '#value' => count($secondary_authors),
      ),
      'check' => array(
        '#type' => 'hidden',
        '#value' => FALSE,
      ),
    ),
    '#disabled' => TRUE,
  );

  $i = 1;
  foreach ($secondary_authors as $author) {
    $form['publication']['secondaryAuthors'][$i] = array(
      '#type' => 'textfield',
      '#title' => t('Secondary Author @i', array('@i' => $i)),
      '#value' => "$author->givennames $author->surname",
    );

    $i++;
  }

  $form['organism'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#disabled' => TRUE,
  );

  $i = 1;
  foreach ($organisms as $org) {
    $form['organism'][$i] = array(
      '#type' => 'textfield',
      '#title' => t('Species @num', array('@num' => $i)),
      '#value' => "$org->genus $org->species",
    );
    $i++;
  }
  $form['organism']['number'] = array(
    '#type' => 'hidden',
    '#value' => $i - 1,
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
