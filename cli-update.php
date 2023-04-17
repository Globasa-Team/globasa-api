<?php
namespace globasa_api;
$app_path = __DIR__;
require_once("{$app_path}/init.php");

Update_controller::update_terms($c, $data);