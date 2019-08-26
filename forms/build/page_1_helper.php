<?php

/**
 * @file
 */

/**
 *
 */
function tppsc_organism(&$form, &$form_state, $defaults) {

  if (isset($form_state['values']['organism']['number']) and $form_state['triggering_element']['#name'] == "Add Organism") {
    $form_state['values']['organism']['number']++;
  }
  elseif (isset($form_state['values']['organism']['number']) and $form_state['triggering_element']['#name'] == "Remove Organism" and $form_state['values']['organism']['number'] > 1) {
    $form_state['values']['organism']['number']--;
  }
  elseif (!empty($defaults)) {
    $form_state['values']['organism']['number'] = count($defaults);
  }
  $org_number = isset($form_state['values']['organism']['number']) ? $form_state['values']['organism']['number'] : NULL;

  if (!isset($org_number) and isset($form_state['saved_values'][TPPS_PAGE_1]['organism']['number'])) {
    $org_number = $form_state['saved_values'][TPPS_PAGE_1]['organism']['number'];
  }
  if (!isset($org_number)) {
    $org_number = 1;
  }

  $form['organism'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#title' => t('<div class="fieldset-title">Organism information:</div>'),
    '#description' => t('Please provide the name(s) of the species included in this publication.'),
    '#collapsible' => TRUE,
    '#prefix' => '<div id="organism-wrapper">',
    '#suffix' => '</div>',
  );

  $form['organism']['add'] = array(
    '#type' => 'button',
    '#title' => t('Add Organism'),
    '#button_type' => 'button',
    '#value' => t('Add Organism'),
    '#name' => t('Add Organism'),
    '#ajax' => array(
      'wrapper' => 'organism-wrapper',
      'callback' => 'tppsc_organism_callback',
    ),
  );

  $form['organism']['remove'] = array(
    '#type' => 'button',
    '#title' => t('Remove Organism'),
    '#button_type' => 'button',
    '#value' => t('Remove Organism'),
    '#name' => t('Remove Organism'),
    '#ajax' => array(
      'wrapper' => 'organism-wrapper',
      'callback' => 'tppsc_organism_callback',
    ),
  );

  $form['organism']['number'] = array(
    '#type' => 'hidden',
    '#value' => $org_number,
  );

  for ($i = 1; $i <= $org_number; $i++) {

    $form['organism']["$i"] = array(
      '#type' => 'textfield',
      '#title' => t("Species @num: *", array('@num' => $i)),
      '#autocomplete_path' => "species/autocomplete",
      '#attributes' => array(
        'data-toggle' => array('tooltip'),
        'data-placement' => array('left'),
        'title' => array('If your species is not in the autocomplete list, don\'t worry about it! We will create a new organism entry in the database for you.'),
      ),
    );

    if (!empty($form_state['saved_values'][TPPS_PAGE_1]['organism']["$i"])) {
      $form['organism']["$i"]['#default_value'] = $form_state['saved_values'][TPPS_PAGE_1]['organism']["$i"];
    }
    elseif (!empty($defaults[$i - 1])) {
      $form['organism']["$i"]['#value'] = $defaults[$i - 1];
    }
    else {
      $form['organism']["$i"]['#default_value'] = NULL;
    }
  }

  return $form;
}
