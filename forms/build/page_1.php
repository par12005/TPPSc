<?php

/**
 * @file
 * TPPSc Page 1. Creates the Publication/Species Information page.
 */

require_once 'page_1_ajax.php';

/**
 * Shows Page 1 form.
 */
function tppsc_page_1_create_form(&$form, &$form_state) {
  module_load_include('php', 'tpps', 'forms/build/page_1');
  return tpps_page_1_create_form($form, $form_state);
}
