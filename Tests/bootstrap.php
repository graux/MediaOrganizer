<?php

require_once '../Utils.php';
require_once '../MediaItem.php';
require_once '../MovieDbMetadataManager.php';
require_once '../TvDbMetadataManager.php';
require_once '../SubDbMetadataManager.php';
require_once '../MediaItemMovie.php';
require_once '../MediaItemSeries.php';

require_once '../config.php';

date_default_timezone_set($GLOBALS['TIMEZONE']);