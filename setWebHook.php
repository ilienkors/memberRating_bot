<?php
require __DIR__ . '/vendor/autoload.php';

use Formapro\TelegramBot\Bot;
use Formapro\TelegramBot\SetWebhook;
use function GuzzleHttp\Psr7\str;

$bot = new Bot($_ENV['member_rating_bot_token']);

$setWebhook = new SetWebhook($_ENV['member_rating_bot_webhookurl']);

$response = $bot->setWebhook($setWebhook);

echo str($response);
