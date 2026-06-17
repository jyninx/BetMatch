<?php
require_once __DIR__ . '/env.php';

define('FOOTBALL_API_KEY', env('FOOTBALL_API_KEY', getenv('FOOTBALL_API_KEY')));
define('FOOTBALL_API_HOST', env('FOOTBALL_API_HOST', 'sportapi7.p.rapidapi.com'));
define('FOOTBALL_API_URL', env('FOOTBALL_API_URL', 'https://sportapi7.p.rapidapi.com/api/v1/'));
?>
