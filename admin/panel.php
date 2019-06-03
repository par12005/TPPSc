<?php

function tppsC_admin_panel_submit($form, &$form_state){
    
    global $base_url;

    $accession = $form_state['values']['form_table'];
    $submission = tpps_load_submission($accession, FALSE);
    $user = user_load($submission->uid);
    $to = $user->mail;
    $state = unserialize($submission->submission_state);
    $params = array();

    $from = variable_get('site_mail', '');
    $params['subject'] = "TPPSC Submission Rejected: {$state['saved_values'][TPPS_PAGE_1]['publication']['title']}";
    $params['uid'] = $user->uid;
    $params['reject-reason'] = $form_state['values']['reject-reason'];
    $params['base_url'] = $base_url;
    $params['title'] = $state['saved_values'][TPPS_PAGE_1]['publication']['title'];
    $params['body'] = '';

    $params['headers'][] = 'MIME-Version: 1.0';
    $params['headers'][] = 'Content-type: text/html; charset=iso-8859-1';
    
    if (isset($form_state['values']['params'])){
        foreach($form_state['values']['params'] as $param_id => $type){
            variable_set("tpps_param_{$param_id}_type", $type);
        }
    }

    if ($form_state['triggering_element']['#value'] == 'Reject'){
        drupal_mail('tppsC', 'user_rejected', $to, user_preferred_language($user), $params, $from, TRUE);
        unset($state['status']);
        tpps_update_submission($state, array('status' => 'Incomplete'));
        drupal_set_message(t('Submission Rejected. Message has been sent to user.'), 'status');
        drupal_goto('<front>');
    }
    else{
        module_load_include('php', 'tppsC', 'forms/submit/submit_all');
        global $user;
        $uid = $user->uid;
        $state['submitting_uid'] = $uid;
        
        $params['subject'] = "TPPSC Submission Approved: {$state['saved_values'][TPPS_PAGE_1]['publication']['title']}";
        $params['accession'] = $state['accession'];
        drupal_set_message(t('Submission Approved! Message has been sent to user.'), 'status');
        drupal_mail('tppsC', 'user_approved', $to, user_preferred_language(user_load_by_name($to)), $params, $from, TRUE);
        
        $state['status'] = 'Approved';
        tpps_update_submission($state);
        if ($state['saved_values']['summarypage']['release']) {
            tppsC_submit_all($accession);
        }
        else {
            $date = $state['saved_values']['summarypage']['release-date'];
            $time = strtotime("{$date['year']}-{$date['month']}-{$date['day']}");
            if (time() > $time) {
                tpps_submit_all($accession);
            }
            else {
                $delayed_submissions = variable_get('tpps_delayed_submissions', array());
                $delayed_submissions[$accession] = $accession;
                variable_set('tpps_delayed_submissions', $delayed_submissions);
            }
        }
    }
}
