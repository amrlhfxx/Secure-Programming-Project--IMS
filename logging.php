<?php

class Logger {
    public static function logFailedLogin($email, $ip, $userAgent, $timestamp) {
        // Log failed login attempt
        $logFile = 'logs/log.txt';
        $formattedTimestamp = date('Y-m-d H:i:s', strtotime($timestamp));
        $logMessage = "[$formattedTimestamp] Failed login attempt: Email: $email, IP: $ip, User Agent: $userAgent" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    public static function logLoginAttempt($email, $ip, $userAgent, $timestamp) {
        // Log login attempt (both successful and failed)
        $logFile = 'logs/log.txt';
        $formattedTimestamp = date('Y-m-d H:i:s', strtotime($timestamp));
        $logMessage = "[$formattedTimestamp] User Login attempt: Email: $email, IP: $ip, User Agent: $userAgent" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    public static function logLogout($email, $ip, $timestamp) {
        // Log logout event
        $logFile = 'logs/log.txt';
        $formattedTimestamp = date('Y-m-d H:i:s');
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        $logMessage = "[$formattedTimestamp] User Logout: Email: $email, IP: $ip, User Agent: $userAgent" . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
