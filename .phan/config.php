<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config-library.php';

$cfg['file_list'] = array_merge(
	$cfg['file_list'],
	[
		'src/defines.php'
	]
);

return $cfg;
