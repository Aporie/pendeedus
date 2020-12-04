<?php

$databases['default']['default'] = array(
  'database' => 'admin_pendeedus',
  'username' => 'admin_pendeedus',
  'password' => 'TvplOKR3oQKzq6IW',
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

//$config['system.performance']['css']['preprocess'] = FALSE;
//$config['system.performance']['js']['preprocess'] = FALSE;
//
//$settings['cache']['bins']['render'] = 'cache.backend.null';
//$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
//
//$settings['cache']['bins']['page'] = 'cache.backend.null';
//
//$settings['extension_discovery_scan_tests'] = FALSE;


$config['nodejs.config']['service_key'] = 'wepc425ppwu4';

$config['nodejs.config']['pages'] = '*';
$config['nodejs.config']['authenticated_users_only'] = TRUE;
$config['nodejs.config']['nodejs']['scheme'] = 'http';
$config['nodejs.config']['nodejs']['host'] = 'pendeedus.mindimage.net';
$config['nodejs.config']['nodejs']['port'] = 8080;
$config['nodejs.config']['client']['scheme'] = 'http';
$config['nodejs.config']['client']['host'] = 'pendeedus.mindimage.net';
$config['nodejs.config']['client']['port'] = 8080;

$config['nodejs.config']['socket_io']['path'] = 'http://pendeedus.mindimage.net:8080/socket.io/socket.io.js';
$config['nodejs.config']['socket_io']['type'] = 'external';
