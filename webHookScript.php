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

if ($update->getMessage()) {
    $isReply = $update->getMessage()->getReplyToMessage();

    $messageText = $update->getMessage()->getText();

    $chatId = strval($update->getMessage()->getChat()->getId());
    $rating = new Rating($chatId);

    $likeSymbol = $rating->get('likeSymbol');
    $dislikeSymbol = $rating->get('dislikeSymbol');

    if ($isReply && ($messageText == $likeSymbol || $messageText == $dislikeSymbol) && $update->getMessage()->getFrom()->getUsername() != $isReply->getFrom()->getUsername() && $isReply->getFrom()->getUsername() != 'memberRating_bot') {

        $replyMessageId = strval($isReply->getMessageId());
        $userName = $update->getMessage()->getFrom()->getUsername();
        $whoWasLiked = $isReply->getFrom()->getUsername();
        $userLikesInfo = $rating->get($whoWasLiked)[$replyMessageId];

        if ($rating->get($whoWasLiked)['likesCount']) {
            $userLikesCount = $rating->get($whoWasLiked)['likesCount'];
        } else {
            $userLikesCount = 0;
        }

        if ($messageText == $likeSymbol) {
            if (!in_array($userName, $userLikesInfo['likes'])) {
                foreach (array_keys($userLikesInfo['dislikes'], $userName) as $key) {
                    unset($userLikesInfo['dislikes'][$key]);
                }
                $userLikesInfo['likes'][] = $userName;
                $userLikesCount++;
                $rating->insertMessageInfo($userLikesInfo, $whoWasLiked, $replyMessageId);
                $rating->insertBig(['likesCount' => $userLikesCount], $whoWasLiked);

                $message = $userName . ' has liked ' . $whoWasLiked;
            } else {
                $message = $userName . ' has already liked ' . $whoWasLiked;
            }
        } elseif ($messageText == $dislikeSymbol) {
            if (!in_array($userName, $userLikesInfo['dislikes'])) {
                foreach (array_keys($userLikesInfo['likes'], $userName) as $key) {
                    unset($userLikesInfo['likes'][$key]);
                }
                $userLikesInfo['dislikes'][] = $userName;
                $userLikesCount--;
                $rating->insertMessageInfo($userLikesInfo, $whoWasLiked, $replyMessageId);
                $rating->insertBig(['likesCount' => $userLikesCount], $whoWasLiked);

                $message = $userName . ' has disliked ' . $whoWasLiked;
            } else {
                $message = $userName . ' has already disliked ' . $whoWasLiked;
            }
        }

        $bot->sendMessage(new SendMessage(
            $update->getMessage()->getChat()->getId(),
            $message
        ));
    }

    if ($messageText === '/statistics@memberRating_bot') {
        $statistics = $rating->getStatistics();;

        if ($statistics) {
            $membersWithLikes = 0;
            foreach ($statistics as $member => $info) {
                $likes = $info['likesCount'];
                if ($likes === 0 || $member === 'dislikeSymbol' || $member === 'likeSymbol') continue;
                if ($likes < 0) {
                    $message .= "\n" . $member . ' has ' . abs($likes) . " 👎";
                } else {
                    $message .= "\n" . $member . ' has ' . $likes . " 👍";
                }
                $membersWithLikes++;
            }
            if ($membersWithLikes === 0) $message = "Statistics is empty";
        } else {
            $message = "Statistics is empty";
        }

        $bot->sendMessage(new SendMessage(
            $update->getMessage()->getChat()->getId(),
            $message
        ));
    }

    $command = explode(" ", $messageText);
    if (count($command) === 2 && $command[0] === '/changeLike@memberRating_bot') {

        if ($dislikeSymbol != $command[1]) {
            $rating->changeSymbol('likeSymbol', $command[1]);
            $message = 'Like symbol has been changed for ' . $command[1];
        } else {
            $message = 'Like symbol should be different from dislike';
        }

        $bot->sendMessage(new SendMessage(
            $update->getMessage()->getChat()->getId(),
            $message
        ));
    }

    if (count($command) === 2 && $command[0] === '/changeDislike@memberRating_bot') {

        if ($likeSymbol != $command[1]) {
            $rating->changeSymbol('dislikeSymbol', $command[1]);
            $message = 'Dislike symbol has been changed for ' . $command[1];
        } else {
            $message = 'Like symbol should be different from dislike';
        }
        $bot->sendMessage(new SendMessage(
            $update->getMessage()->getChat()->getId(),
            $message
        ));
    }

    if ($messageText === '/help@memberRating_bot') {

        $bot->sendMessage(new SendMessage(
            $update->getMessage()->getChat()->getId(),
            'To like comment write ' . $likeSymbol . "\n"
                . 'To dislike comment write ' . $dislikeSymbol . "\n"
                . 'You can change default commands with:' . "\n"
                . '/changeLike@memberRating_bot [newLikeSymbol]' . "\n"
                . '/changeDislike@memberRating_bot [newDislikeSymbol]' . "\n"
                . 'Made by @romusk'
        ));
    }
}
