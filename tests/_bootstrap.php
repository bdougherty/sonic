<?php
use \Sonic\App;
$lib_path = str_replace('tests/_bootstrap.php', 'lib', realpath(__FILE__));
set_include_path($lib_path);

include 'Sonic/App.php';

$app = App::getInstance();
$app->addSetting(App::AUTOLOAD, true);

$app->start(App::COMMAND_LINE);
