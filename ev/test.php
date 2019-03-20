<?php
error_reporting(E_ALL ^ E_NOTICE);
if (!isset($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen($_SERVER['SCRIPT_NAME']));
}
define("APP_PATH",$_SERVER['DOCUMENT_ROOT'].'/ev/');
require(APP_PATH . '_classes/Autoloader.class.php');
Autoloader::setCacheFilePath(APP_PATH . 'tmp/class_path_cache.txt');
Autoloader::excludeFolderNamesMatchingRegex('/^CVS|\..*$/');
Autoloader::setClassPaths(array(
    APP_PATH . '_classes/',
));
spl_autoload_register(array('Autoloader', 'loadClass'));


echo "<pre>";
$params = array('title' => 'New Years Celebration',
        'datetime' => array('start' => '2012-12-31 20:00', 'end' => '2013-01-01 02:00'),
        'location' => 'Lake House',
        'description' => 'Come celebrate New Years with Bert and Ernie.'
    );
$link = 
$gCal = GoogleCalendar::createEventReminder($params);
echo " $link ";
