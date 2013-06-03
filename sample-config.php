<?php

$GLOBALS['MOVIES_FOLDER'] = '/Media/Volumes/Movies';
$GLOBALS['SERIES_FOLDER'] = '/Media/Volumes/Series';
$GLOBALS['FOLDER_EXCLUDES'] = array('Torrents', '_config');
$GLOBALS['THEMOVIEDB_KEY'] = '[THEMOVIEDB_KEY]';
$GLOBALS['THETVDB_KEY'] = '[THETVDB_KEY]';

$GLOBALS['DEBUG'] = true;
$GLOBALS['DRY_RUN'] = true;
$GLOBALS['TARGET_FOLDER'] = '/Media/Volumes/Torrents';
$GLOBALS['SKIP_IF_METADATA_PRESENT'] = false;
$GLOBALS['MOVE_FILES'] = true;
$GLOBALS['CREATE_MOVIE_DIRECTORY'] = false;
$GLOBALS['CREATE_SERIES_DIRECTORY'] = true;
$GLOBALS['CREATE_SEASON_DIRECTORY'] = true;
$GLOBALS['SEASON_DIRECTORY_PATERN'] = 'Season {N}';
$GLOBALS['THUMB_EXTENSION'] = 'metathumb';
$GLOBALS['WDLIVETV_FOLDERS'] = true;
$GLOBALS['NUM_BACKDROPS'] = 5;
$GLOBALS['DOWNLOAD_BACKDROPS'] = true;
$GLOBALS['TIMEZONE'] = 'Europe/Madrid';
$GLOBALS['DOWNLOAD_SUBTITLES'] = true;
$GLOBALS['SUBTITLES_LANGUAGE'] = 'en';

$GLOBALS['NAME_PATCHING'] = array(
    'Shameless US' => 'Shameless',
    'Avatar The Legend Of Korra' => 'The Legend Of Korra',
    'Wilfred US' => 'Wilfred'
);