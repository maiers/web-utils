<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('config.php');

// read keys from language files
$langFiles = scandir($languageFiles);
$keys = [];
foreach ($langFiles as $file) {
  $matches = null;
  if (preg_match($languageFilePattern, $file, $matches)) {
    //$translation = parse_ini_file("$languageFiles/$file");
    $translation = getFileContents("$languageFiles/$file", $lineDelimiter);
    foreach ($translation as $key => $value) {
      if (strlen(trim($key)) === 0)
        continue;
      if (!array_key_exists($key, $keys)) {
        $keys[$key] = ["langs" => [], "usage" => 0, "replacement" => []];
      }
      $keys[$key]["langs"][] = $matches[1];
      if (array_key_exists($key, $keyReplacement)) {
        $keys[$key]["replacement"] = $keyReplacement[$key];
      }
    }
  }
}

$flatKeys = array_map(function($key, $value) {
  $value["key"] = $key;
  return $value;
}, array_keys($keys), $keys);

// read files
function getFileContents($path, $delimiter) {
  return parseJavaProperties($path);
}

/**
 * source: http://blog.rafaelsanches.com/2009/08/05/reading-java-style-properties-file-in-php/
 * @param type $txtProperties
 * @return string
 */
function parseJavaProperties($file) {

  $txtProperties = file_get_contents($file);

  $result = array();

  $lines = explode("\n", $txtProperties);
  $key = "";

  $isWaitingOtherLine = false;
  foreach ($lines as $i => $line) {

    if (empty($line) || (!$isWaitingOtherLine && strpos($line, "#") === 0))
      continue;

    if (!$isWaitingOtherLine) {
      $key = substr($line, 0, strpos($line, '='));
      $value = substr($line, strpos($line, '=') + 1, strlen($line));
    } else {
      $value .= $line;
    }

    /* Check if ends with single '\' */
    if (strrpos($value, "\\") === strlen($value) - strlen("\\")) {
      $value = substr($value, 0, strlen($value) - 1) . "\n";
      $isWaitingOtherLine = true;
    } else {
      $isWaitingOtherLine = false;
    }

    $result[$key] = $value;
    unset($lines[$i]);
  }

  return $result;
}

header('Content-Type: application/json');
echo json_encode($flatKeys, JSON_PRETTY_PRINT);
