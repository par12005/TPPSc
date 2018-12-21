<?php

function tppsC_admin_panel($form, &$form_state){
    
    global $user;
    global $base_url;
    
    $params = drupal_get_query_parameters();
    $accession = isset($params['accession']) ? $params['accession'] : NULL;
    
    if (!isset($user->roles[3]) or $user->roles[3] !== 'administrator'){
        drupal_access_denied();
        return $form;
    }
    elseif (empty($accession)){
        
        $query = db_select('variable', 'v')
            ->fields('v', array('name'))
            ->condition('v.name', db_like("tppsC_complete_") . '%', 'LIKE')
            ->execute();
        
        $display = "<table style='width:-webkit-fill-available' border='1'><thead>";
        $display .= "<tr><th>Accession Number</th><th>Title</th><th>Status</th></tr>";
        $display .= "</thead><tbody>";
        $data = array();
        while (($result = $query->fetchObject())){
            $name = $result->name;
            $state = variable_get($name, NULL);
            if (!empty($state)){
                
                $item = array(
                  'link' => l($state['accession'], "$base_url/tppsC-admin-panel?accession={$state['accession']}"),
                  'title' => $state['saved_values'][PAGE_1]['publication']['title'],
                  'status' => $state['status'],
                );
                if ($state['status'] == "Pending Approval"){
                    array_unshift($data, $item);
                }
                else {
                    $data[] = $item;
                }
            }
        }
        
        foreach ($data as $item){
            $display .= "<tr><td>{$item['link']}</td><td>{$item['title']}</td><td>{$item['status']}</td></tr>";
        }
        $display .= "</tbody></table>";
        
        $form['a'] = array(
          '#type' => 'hidden',
          '#suffix' => $display,
        );
    }
    else {
        $results = db_select('variable', 'v')
            ->fields('v', array('name'))
            ->condition('v.name', db_like("tppsC_complete_") . '%' . db_like("$accession"), 'LIKE')
            ->execute()
            ->fetchAssoc();
        $var_name = $results['name'];
        $submission_state = variable_get($var_name);
        $status = $submission_state['status'];
        $display = l("Back to TPPSC Admin Panel", "$base_url/tppsC-admin-panel");
        $display .= tppsC_table_display($submission_state);
        
        $form['form_table'] = array(
          '#type' => 'hidden',
          '#value' => $var_name,
          '#suffix' => $display
        );

        if ($status == "Pending Approval"){
            
            $form['params'] = array(
              '#type' => 'fieldset',
              '#title' => 'Select Environmental parameter types:',
              '#tree' => TRUE,
              '#description' => ''
            );
            
            $orgamism_num = $submission_state['saved_values'][PAGE_1]['organism']['number'];
            $show_layers = FALSE;
            for ($i = 1; $i <= $orgamism_num; $i++){
                if ($submission_state['saved_values'][PAGE_4]["organism-$i"]['environment']['use_layers']){
                    foreach ($submission_state['saved_values'][PAGE_4]["organism-$i"]['environment']['env_layers'] as $layer => $layer_id){
                        if (!empty($layer_id)){
                            foreach ($submission_state['saved_values'][PAGE_4]["organism-$i"]['environment']['env_params'][$layer] as $param_name => $param_id){
                                if (!empty($param_id)){
                                    $type = variable_get("tppsC_param_{$param_id}_type", NULL);
                                    if (empty($type)){
                                        $query = db_select('cartogratree_fields', 'f')
                                            ->fields('f', array('display_name'))
                                            ->condition('field_id', $param_id)
                                            ->execute();
                                        $result = $query->fetchObject();
                                        $name = $result->display_name;

                                        $form['params'][$param_id] = array(
                                          '#type' => 'radios',
                                          '#title' => "Select Type for environmental layer parameter \"$name\":",
                                          '#options' => array(
                                            'attr_id' => 'attr_id',
                                            'cvterm' => 'cvterm'
                                          ),
                                          '#required' => TRUE,
                                        );
                                        $show_layers = TRUE;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            if (!$show_layers){
                unset($form['params']);
            }

            $form['approve-check'] = array(
              '#type' => 'checkbox',
              '#title' => t('This submission has been reviewed and approved.')
            );

            $form['reject-reason'] = array(
              '#type' => 'textarea',
              '#title' => t('Reason for rejection:'),
              '#states' => array(
                'invisible' => array(
                  ':input[name="approve-check"]' => array('checked' => TRUE)
                )
              )
            );

            $form['REJECT'] = array(
              '#type' => 'submit',
              '#value' => t('Reject'),
              '#states' => array(
                'invisible' => array(
                  ':input[name="approve-check"]' => array('checked' => TRUE)
                )
              )
            );

            $form['APPROVE'] = array(
              '#type' => 'submit',
              '#value' => t('Approve'),
              '#states' => array(
                'visible' => array(
                  ':input[name="approve-check"]' => array('checked' => TRUE)
                )
              )
            );
        }
    }
    return $form;
}

function tppsC_admin_panel_validate($form, &$form_state){
    if ($form_state['submitted'] == '1'){
        if ($form_state['values']['reject-reason'] == '' and $form_state['triggering_element']['#value'] == 'Reject'){
            form_set_error('reject-reason', 'Please explain why the submission was rejected.');
        }
    }
}

function tppsC_admin_panel_submit($form, &$form_state){
    
    global $base_url;

    $var_name = $form_state['values']['form_table'];
    $suffix = substr($var_name, 14);
    $to = substr($var_name, 14, -7);
    $state = variable_get($var_name);
    $params = array();

    $from = variable_get('site_mail', '');
    $params['subject'] = "TPPSC Submission Rejected: {$state['saved_values'][PAGE_1]['publication']['title']}";
    $params['uid'] = user_load_by_name($to)->uid;
    $params['reject-reason'] = $form_state['values']['reject-reason'];
    $params['base_url'] = $base_url;
    $params['title'] = $state['saved_values'][PAGE_1]['publication']['title'];
    $params['body'] = '';

    $params['headers'][] = 'MIME-Version: 1.0';
    $params['headers'][] = 'Content-type: text/html; charset=iso-8859-1';
    
    if (isset($form_state['values']['params'])){
        foreach($form_state['values']['params'] as $param_id => $type){
            variable_set("tppsC_param_{$param_id}_type", $type);
        }
    }

    if ($form_state['triggering_element']['#value'] == 'Reject'){
        
        drupal_mail('tppsC', 'user_rejected', $to, user_preferred_language(user_load_by_name($to)), $params, $from, TRUE);
        variable_del($var_name);
        unset($state['status']);
        variable_set('tppsC_incomplete_' . $suffix, $state);
        dpm('Submission Rejected. Message has been sent to user.');
        drupal_goto('<front>');
    }
    else{
        module_load_include('php', 'tppsC', 'forms/submit/submit_all');
        global $user;
        $uid = $user->uid;
        $includes = array();
        $includes[] = module_load_include('module', 'tppsC');
        
        $params['subject'] = "TPPSC Submission Approved: {$state['saved_values'][PAGE_1]['publication']['title']}";
        $params['accession'] = $state['accession'];
        
        $state['status'] = 'Approved';
        variable_set($var_name, $state);
        tppsC_submit_all($state);
        $args = array($state);
        $jid = tripal_add_job("TPPSC File Parsing - {$state['accession']}", 'tppsC', 'tppsC_file_parsing', $args, $uid, 10, $includes, TRUE);
        $state['job_id'] = $jid;
        
        dpm('Submission Approved! Message has been sent to user.');
        drupal_mail('tppsC', 'user_approved', $to, user_preferred_language(user_load_by_name($to)), $params, $from, TRUE);
    }
}
