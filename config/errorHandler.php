<?php
/**
 * Centralized Error & Exception Handler for P3KU Platform
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/../');
}

define('LOG_DIR', ROOT_PATH . 'logs/');
define('LOG_FILE', LOG_DIR . 'application.log');

// Ensure logs directory exists
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0777, true);
}

function log_error_details($type, $message, $file, $line, $trace = '')
{
    $logEntry = "[" . date('Y-m-d H:i:s') . "] "
              . "$type: $message in $file on line $line\n"
              . "TRACE: $trace\n"
              . str_repeat("-", 70) . "\n";

    $logFile = __DIR__ . '/../logs/application.log';

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function p3ku_exception_handler($exception)
{
    $message = $exception->getMessage();
    $file    = $exception->getFile();
    $line    = $exception->getLine();
    $trace   = $exception->getTraceAsString();

    log_error_details("UNCAUGHT EXCEPTION", $message, $file, $line, $trace);

    if (is_api_request()) {
        http_response_code(500);
        echo json_encode([
            "error"   => true,
            "message" => "Server Error",
            "details" => $message
        ]);
        exit();
    }

    error_page("Exception Occurred", $message, $file, $line);
    exit();
}

function p3ku_error_handler($severity, $message, $file, $line)
{
    $severityMap = [
        E_ERROR             => "ERROR",
        E_WARNING           => "WARNING",
        E_PARSE             => "PARSE ERROR",
        E_NOTICE            => "NOTICE",
        E_CORE_ERROR        => "CORE ERROR",
        E_CORE_WARNING      => "CORE WARNING",
        E_COMPILE_ERROR     => "COMPILE ERROR",
        E_COMPILE_WARNING   => "COMPILE WARNING",
        E_USER_ERROR        => "USER ERROR",
        E_USER_WARNING      => "USER WARNING",
        E_USER_NOTICE       => "USER NOTICE",
        E_STRICT            => "STRICT WARNING",
        E_RECOVERABLE_ERROR => "RECOVERABLE ERROR",
        E_DEPRECATED        => "DEPRECATED WARNING",
        E_USER_DEPRECATED   => "USER DEPRECATED WARNING",
    ];

    $type = $severityMap[$severity] ?? "UNKNOWN ERROR";

    log_error_details($type, $message, $file, $line);

    if (in_array($severity, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_page($type, $message, $file, $line);
        exit();
    }

    return true;
}

function is_api_request()
{
    if (!empty($_SERVER["HTTP_ACCEPT"]) && strpos($_SERVER["HTTP_ACCEPT"], "application/json") !== false) {
        return true;
    }
    if (strpos($_SERVER["REQUEST_URI"], "/api/") !== false) {
        return true;
    }
    return false;
}

function error_page($title, $message, $file, $line)
{
    echo "
    <html>
    <head>
        <title>Error - $title</title>
        <style>
            body { font-family: Arial; background: #fafafa; padding: 40px; }
            .box { background: white; padding: 25px; border-radius: 8px; 
                   box-shadow: 0 0 10px rgba(0,0,0,.1); }
            h2 { margin-top: 0; }
            .code { background: #f2f2f2; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='box'>
            <h2>$title</h2>
            <p><strong>Message:</strong> $message</p>
            <p><strong>Location:</strong> $file on line $line</p>
            <div class='code'>Please check the logs under /logs/application.log</div>
        </div>
    </body>
    </html>
    ";
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

set_exception_handler('p3ku_exception_handler');
set_error_handler('p3ku_error_handler');

ini_set('display_errors', 'Off');
error_reporting(E_ALL);

?>
