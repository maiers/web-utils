<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('config.php');

// get list of files
$codeFiles = [];
foreach ($scanFolders as $folder) {
  $codeFiles = array_merge($codeFiles, dirToArray($folder, $scanIncludePatterns, $scanExcludePatterns));
}

/**
 * Create a flat array of all files matching the provided patterns
 * within the provided directory and all of its sub-directories using
 * recursion.
 * 
 * Will remove all files matching any of the exclude patterns. Will
 * only include those files matching at least one of the include 
 * patterns.
 * 
 * Patterns are provided as arrays of regex string patterns.
 * 
 * @param string $dir to start with
 * @param array $includePatterns
 * @param array $excludePatterns
 * @return array
 */
function dirToArray($dir, $includePatterns, $excludePatterns) {
  $out = [];
  $files = scandir($dir);
  foreach ($files as $file) {
    if (!in_array($file, array(".", ".."))) {
      $sub = "$dir/$file";
      //echo $sub . "<br>";
      if (is_dir($sub)) {
        $out = array_merge($out, dirToArray($sub, $includePatterns, $excludePatterns));
      } else {
        $exclude = false;
        foreach ($excludePatterns as $pattern) {
          if (preg_match($pattern, $sub)) {
            $exclude = true;
            break;
          }
        }
        if (!$exclude) {
          foreach ($includePatterns as $pattern) {
            if (preg_match($pattern, $sub)) {
              $out[] = $sub;
              break;
            }
          }
        }
      }
    }
  }
  return $out;
}

header('Content-Type: application/json');
echo json_encode($codeFiles);