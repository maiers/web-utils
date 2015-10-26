<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('config.php');

header('Content-Type: application/json');
echo json_encode($configs, JSON_PRETTY_PRINT);