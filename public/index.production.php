<?php

/**
 * SchoolPortal - Production Entry Point for Shared Hosting
 *
 * This file goes in public_html/ on Namecheap shared hosting.
 * It points to the Laravel app located one directory above public_html.
 *
 * Directory structure on server:
 *   /home/username/schoolportal/       ← Laravel app (above web root)
 *   /home/username/dexta.website/      ← Web root (this file + build assets)
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Point to the Laravel app directory (one level above public_html)
$appPath = dirname(__DIR__).'/schoolportal';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

// Override the public path to point to public_html
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
