<?php
require __DIR__ . '/vendor/autoload.php';

use Formapro\TelegramBot\Bot;
use function GuzzleHttp\Psr7\str;

$bot = new Bot($_ENV['member_rating_bot_token']);

$response = $bot->getWebhookInfo();

echo str($response);
