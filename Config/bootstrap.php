<?php
$userMap = true;

Configure::write('Session', Hash::merge(
	Configure::read('Session'),
	array(
		'defaults' => 'php',
		'handler' => array(
			'engine' => 'DbSession.DbSession',
			'userMap' => $userMap,
		),
	)
));

if ($userMap) {
	if (extension_loaded('wddx')) {
		ini_set('session.serialize_handler', 'wddx');
	} else {
		CakeLog::critical('wddx not available. user map not enabled');
	}
}
