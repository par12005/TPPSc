<?php

function ajax_doi_callback(&$form, $form_state){
    
    return $form;
}

function tppsC_organism_callback($form, &$form_state){
    
    return $form['organism'];
}