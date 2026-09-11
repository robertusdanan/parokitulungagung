<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/StoriesManager.php';
header('Content-Type: application/json');
echo json_encode([
    'stories_local_cache' => json_decode(@file_get_contents(StoriesManager::getCacheFilePath()), true),
    'stories_manager'     => StoriesManager::getStories(true)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
