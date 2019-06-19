<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/rating.php';

use Formapro\TelegramBot\Bot;
use Formapro\TelegramBot\Update;
use Formapro\TelegramBot\SendMessage;

$bot = new Bot($_ENV['member_rating_bot_token']);

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

$update = Update::create($data);

$isReply = $update->getMessage()->getReplyToMessage();
$messageText = $update->getMessage()->getText();

$chatId = strval($update->getMessage()->getChat()->getId());
$rating = new Rating($chatId);

if ($isReply && ($messageText === '+' || $messageText === '-')) {

    $userName = $update->getMessage()->getFrom()->getUsername();
    $whoWasLiked = $isReply->getFrom()->getUsername();
    $userLikesInfo = $rating->get($whoWasLiked);

    if ($messageText === '+') {
        if (!in_array($userName, $userLikesInfo['likes'])) {
            foreach (array_keys($userLikesInfo['dislikes'], $userName) as $key) {
                unset($userLikesInfo['dislikes'][$key]);
            }
            $userLikesInfo['likes'][] = $userName;
            $rating->insert([
                $whoWasLiked => $userLikesInfo
            ]);

            $message = '@' . $userName . ' has liked @' . $whoWasLiked;
        } else {
            $message = '@' . $userName . ' has already liked @' . $whoWasLiked;
        }
    } elseif ($messageText === '-') {
        if (!in_array($userName, $userLikesInfo['dislikes'])) {
            foreach (array_keys($userLikesInfo['likes'], $userName) as $key) {
                unset($userLikesInfo['likes'][$key]);
            }
            $userLikesInfo['dislikes'][] = $userName;
            $rating->insert([
                $whoWasLiked => $userLikesInfo
            ]);

            $message = '@' . $userName . ' has disliked @' . $whoWasLiked;
        } else {
            $message = '@' . $userName . ' has already disliked @' . $whoWasLiked;
        }
    }

    $bot->sendMessage(new SendMessage(
        $update->getMessage()->getChat()->getId(),
        $message
    ));
}

if ($messageText === '/statistics' || $messageText === '/statistics@memberRating_bot') {
    $statistics = $rating->getStatistics();;

    if ($statistics) {
        foreach ($statistics as $member => $info) {
            $likes = count($info['likes']) - count($info['dislikes']);
            if ($likes < 0) {
                $message .= "\n@" . $member . ' has '. abs($likes) . " 👎";
            } else {
                $message .= "\n@" . $member . ' has '. $likes . " 👍";
            }
        }
    } else {
        $message = "Statistics is empty";
    }
    

    $bot->sendMessage(new SendMessage(
        $update->getMessage()->getChat()->getId(),
        $message
    ));
}
