<?php
if (self::$settings->getGlobalOption ( 'track_compress' ))
	self::$settings->setGlobalOption ( 'track_mode', 1 );
else
	self::$settings->setGlobalOption ( 'track_mode', 0 );