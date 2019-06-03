<?php

/**
 * @file
 * Defines a function to populate the TPPS Submissions user tab.
 */

/**
 * This function populates the TPPS Submissions user tab.
 *
 * @param int $user
 *   The id of the user.
 *
 * @global string $base_url
 *   The base URL of the site.
 *
 * @return string
 *   The HTML of the TPPS user tab.
 */
function tppsC_tab_create($user) {

  global $base_url;
  $params = drupal_get_query_parameters();
  $final = "";
  $t1 = $t2 = FALSE;

  if (isset($params['accession']) and isset($params['action']) and $params['action'] === 'DELETE') {
    tpps_delete_submission($params['accession']);
  }

  $and = db_and()
    ->condition('uid', $user->uid)
    ->condition('status', 'Incomplete');
  $results = db_select('tpps_submission', 's')
    ->fields('s')
    ->condition($and)
    ->execute();

  $style =
  '<style>
    .tpps_profile_tab {
      background: #ffffff;
      border: 1px solid #cccccc;
      border-radius: 4px;
      padding: 0px 10px;
    }
  </style>';
  $display = "<label for='tpps_incomplete_table'>Incomplete Submissions</label><div class='tpps_profile_tab'><table id='tpps_incomplete_table' style='width:-webkit-fill-available' border='1'><thead>";
  $display .= "<tr><th>Title</th><th>Primary Author</th><th>Number of Species</th><th>Abstract</th><th>Journal</th><th></th></tr>";
  $display .= "</thead><tbody>";

  foreach ($results as $item) {
    $state = unserialize($item->submission_state);
    $prefix = ($state['tpps_type'] == 'tppsC') ? 'tppsC' : 'tpps';

    $display .= "<tr>";

    if (isset($state['saved_values'][TPPS_PAGE_1]['publication']['title'])) {
      $title = check_plain($state['saved_values'][TPPS_PAGE_1]['publication']['title']);
      $display .= "<td><a href='$base_url/$prefix?accession={$state['accession']}' style='text-decoration: underline;'>$title</a></td>";
    }
    else {
      $display .= "<td></td>";
    }

    if (isset($state['saved_values'][TPPS_PAGE_1]['primaryAuthor'])) {
      $author = check_plain($state['saved_values'][TPPS_PAGE_1]['primaryAuthor']);
      $display .= "<td>$author</td>";
    }
    else {
      $display .= "<td></td>";
    }

    if (isset($state['saved_values'][TPPS_PAGE_1]['organism']['number'])) {
      $species_number = check_plain($state['saved_values'][TPPS_PAGE_1]['organism']['number']);
      $display .= "<td>$species_number</td>";
    }
    else {
      $display .= "<td></td>";
    }

    if (isset($state['saved_values'][TPPS_PAGE_1]['publication']['abstract'])) {
      $abstract = check_plain($state['saved_values'][TPPS_PAGE_1]['publication']['abstract']);
      if (strlen($abstract) > 103) {
        $abstract = check_plain(substr($abstract, 0, 100) . "...");
      }
      $display .= "<td>$abstract</td>";
    }
    else {
      $display .= "<td></td>";
    }

    if (isset($state['saved_values'][TPPS_PAGE_1]['publication']['journal'])) {
      $journal = check_plain($state['saved_values'][TPPS_PAGE_1]['publication']['journal']);
      $display .= "<td>$journal</td>";
    }
    else {
      $display .= "<td></td>";
    }

    if (isset($state['accession'])) {
      $display .= "<td style='text-align:center'><input type='submit' id='delete-{$state['accession']}' onclick='";
      $title = (isset($state['saved_values'][TPPS_PAGE_1]['publication']['title'])) ? ' titled ' . $state['saved_values'][TPPS_PAGE_1]['publication']['title'] : '';
      $display .= "if (confirm(\"Attention! \\n\\nAre you sure you want to delete your submission{$title}?\\nThis action cannot be undone, and all of your data will be lost!\")){window.location.href = \"$base_url/user/{$user->uid}/tpps?accession={$state['accession']}&action=DELETE\"}";
      $display .= "' name='op' class='form-button' value='Delete'></td>";
    }
    else {
      $display .= '<td></td>';
    }

    $display .= "</tr>";
  }

  if (!isset($item)) {
    $display = $style;
  }
  else {
    $display .= "</tbody></table></div><br>";
    $t1 = TRUE;
  }

  $final .= $display;
  $item = NULL;

  $or = db_or()
    ->condition('status', 'Pending Approval')
    ->condition('status', 'Approved');
  $and = db_and()
    ->condition('uid', $user->uid)
    ->condition($or);
  $results = db_select('tpps_submission', 's')
    ->fields('s')
    ->condition($and)
    ->execute();

  $display = "<label for='tpps_complete_table'>Complete Submissions</label><div class='tpps_profile_tab'><table id='tpps_complete_table' style='width:-webkit-fill-available' border='1'><thead>";
  $display .= "<tr><th>Title</th><th>Primary Author</th><th>Number of Species</th><th>Abstract</th><th>Journal</th><th>Status</th></tr>";
  $display .= "</thead><tbody>";

  foreach ($results as $item) {
    $state = unserialize($item->submission_state);

    $display .= "<tr>";

    if (isset($state['saved_values'][TPPS_PAGE_1]['publication']['title'])) {
      $title = check_plain($state['saved_values'][TPPS_PAGE_1]['publication']['title']);
      $display .= "<td><a href='$base_url/completed-submission?id={$state['accession']}' style='text-decoration: underline;'>$title</a></td>";
    }
    else {
      $display .= "<td></td>";
    }

    if (isset($state['saved_values'][TPPS_PAGE_1]['primaryAuthor'])) {
      $author = check_plain($state['saved_values'][TPPS_PAGE_1]['primaryAuthor']);
      $display .= "<td>$author</td>";
    }
    else {
      $display .= "<td></td>";
    }

    if (isset($state['saved_values'][TPPS_PAGE_1]['organism']['number'])) {
      $species_number = check_plain($state['saved_values'][TPPS_PAGE_1]['organism']['number']);
      $display .= "<td>$species_number</td>";
    }
    else {
      $display .= "<td></td>";
    }

    if (isset($state['saved_values'][TPPS_PAGE_1]['publication']['abstract'])) {
      $abstract = check_plain($state['saved_values'][TPPS_PAGE_1]['publication']['abstract']);
      if (strlen($abstract) > 103) {
        $abstract = check_plain(substr($abstract, 0, 100) . "...");
      }
      $display .= "<td>$abstract</td>";
    }
    else {
      $display .= "<td></td>";
    }

    if (isset($state['saved_values'][TPPS_PAGE_1]['publication']['journal'])) {
      $journal = check_plain($state['saved_values'][TPPS_PAGE_1]['publication']['journal']);
      $display .= "<td>$journal</td>";
    }
    else {
      $display .= "<td></td>";
    }

    $display .= "<td>{$state['status']}</td></tr>";
  }

  if (!isset($item)) {
    $display = "";
  }
  else {
    $display .= "</tbody></table></div><br>";
    $t2 = TRUE;
  }

  if (!$t1 and !$t2) {
    $display = $style;
    $display .= "<h2>Could not find any Submissions</h2>";
    $display .= "<p>We could not find any incomplete TPPS Submissions or TPPS Submissions pending approval. If you would like to start a new TPPS Submission, you can start <a href=$base_url/tpps>here.</a></p>";
  }

  $final .= $display;

  return $final;
}