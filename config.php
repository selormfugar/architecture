<?php

if (!function_exists('loadEnvFile')) {
    function loadEnvFile($path)
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "\"'");

            if ($name === '') {
                continue;
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

loadEnvFile(__DIR__ . '/.env');

