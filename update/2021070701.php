<?php

// Set range for per post stats
if (self::$settings->getGlobalOption('perpost_stats')) {
    self::$settings->setGlobalOption('perpost_stats', "last30");
} else {
    self::$settings->setGlobalOption('perpost_stats', "disabled");
}

self::$settings->save ();