<?php
echo 'DOCUMENT_ROOT: ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . PHP_EOL;
echo 'SCRIPT_FILENAME: ' . ($_SERVER['SCRIPT_FILENAME'] ?? 'unknown') . PHP_EOL;
echo 'REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL;
echo 'SCRIPT_NAME: ' . ($_SERVER['SCRIPT_NAME'] ?? 'unknown') . PHP_EOL;
