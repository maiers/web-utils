<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(__DIR__ . '/../../vendor/autoload.php');
        
// handle config
$configPath = __DIR__ . "/../config";
$configs = glob("$configPath/*.yml", GLOB_BRACE);
$defaultConfigPath = "$configPath/default.yml";

$activeConfigId = filter_input(INPUT_GET, "config", FILTER_SANITIZE_NUMBER_INT);
if ($activeConfigId === null) {
  $activeConfigId = 0;
}

$activeConfigPath = $configs[$activeConfigId];

if (!file_exists($activeConfigPath)) {
  throw new Exception("Config path not set");
}

// load config
$activeConfig = Spyc::YAMLLoad($activeConfigPath);

// set variables
$languageFiles = $activeConfig["languageFiles"]["path"];
$languageFilePattern = $activeConfig["languageFiles"]["pattern"];
$scanFolders = $activeConfig["codeFiles"]["paths"];
$scanIncludePatterns = $activeConfig["codeFiles"]["include"];
$scanExcludePatterns = $activeConfig["codeFiles"]["exclude"];
$lineDelimiter = $activeConfig["lineEnding"];
$keyReplacement = $activeConfig["keys"]["replace"];