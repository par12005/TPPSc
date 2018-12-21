<?php

function tppsC_completed_display($form, $form_state){
    
    global $user;
    global $base_url;
    $params = drupal_get_query_parameters();
    $accession = $params['id'];
    $link = "$base_url/tppsC_details/accession/$accession";
    
    $old_form_state = variable_get('tppsC_complete_' . $user->mail . $accession);
    $values = $old_form_state['saved_values'];
    
    $css = "
    .tgdr_complete_submission {
        background: #ffffff;
        border: 1px solid #cccccc;
        border-radius: 4px;
        padding: 0px 10px;
    }		
    ";
    drupal_add_css($css, 'inline');
    
    $display = "<label for='tppsC_$accession'>{$values[TPPSC_PAGE_1]['publication']['title']}</label><p>You can view the TPPSC Details page with completed and approved TPPSC and TGDR data <a href=\"$link\">here</a></p>";
    
    $display .= tppsC_table_display($old_form_state);
    
    $form['a'] = array(
      '#type' => 'hidden',
      '#suffix' => $display
    );
    
    return $form;
}
