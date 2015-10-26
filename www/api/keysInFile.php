<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('config.php');

$file = filter_input(INPUT_POST, "file");

// scan files for key usage
$result = ["path" => $file, "replaced" => 0, "keys" => ["_sum" => 0]];
foreach ($keys as $key => $value) {
  $contents = file_get_contents($file);
  $lines = explode($lineDelimiter, $contents);
  for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $count = 0;
    if (($count = preg_match_all("/$key/", $line)) > 0) {
      $firstLine = max(0, $i - 1);
      $context = array_slice($lines, $firstLine, 3);
      $context = implode($lineDelimiter, $context);
      $context = htmlspecialchars($context);
      //echo "$line<br>$i<br>$file<br>$key<br>$context<hr>";
      $keys[$key]["usage"] += $count;
      $result["keys"]["_sum"] += $count;
      if (!array_key_exists($key, $result["keys"])) {
        $result["keys"][$key] = 0;
      }
      $result["keys"][$key] += $count;
      // key is to be replace
      if (array_key_exists($key, $keyReplacement)) {
        preg_replace_callback("/$key/", function() {
          global $key, $result, $keyReplacement;
          $result["replaced"] ++;
          return $keyReplacement[$key];
        }, $line);
      }
    }
  }
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);