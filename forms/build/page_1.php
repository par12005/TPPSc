<?php

/**
 * @file
 */

require_once 'page_1_helper.php';
require_once 'page_1_ajax.php';

/**
 *
 */
function page_1_create_form(&$form, $form_state) {

  if (isset($form_state['saved_values'][TPPS_PAGE_1])) {
    $values = $form_state['saved_values'][TPPS_PAGE_1];
  }
  else {
    $values = array();
  }

  $form['doi'] = array(
    '#type' => 'textfield',
    '#title' => t('DOI: *'),
    '#ajax' => array(
      'callback' => 'ajax_doi_callback',
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

    preg_match('/<dcterms:title>(.*)<\/dcterms:title>/', $response_xml_data, $matches);
    $title = $matches[1];
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

  organism($form, $form_state, $species);

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
