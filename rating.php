<?php

require_once './vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class Rating
{
    protected $database;
    protected $chatId;

    public function __construct($chatId)
    {
        $this->chatId = $chatId;
        $acc = ServiceAccount::fromJsonFile(__DIR__ . $_ENV['member_rating_bot_secretpath']);
        $firebase = (new Factory)->withServiceAccount($acc)->create();

        $this->database = $firebase->getDatabase();
    }

    public function getStatistics()
    {
        return $this->database->getReference($this->chatId)->getValue();
    }

    public function get($userName = NULL)
    {
        if (empty($userName) || !isset($userName)) {
            return FALSE;
        }

        if ($this->database->getReference($this->chatId)->getSnapshot()->hasChild($userName)) {
            return $this->database->getReference($this->chatId)->getChild($userName)->getValue();
        } else {
            return FALSE;
        }
    }

    public function insert(array $data)
    {
        if (empty($data) || !isset($data)) {
            return FALSE;
        }

        foreach ($data as $key => $value) {
            $this->database->getReference()->getChild($this->chatId)->getChild($key)->set($value);
        }

        return TRUE;
    }

    public function insertBig(array $data, $userName)
    {
        if (empty($data) || !isset($data)) {
            return FALSE;
        }

        foreach ($data as $key => $value) {
            $this->database->getReference()->getChild($this->chatId)->getChild($userName)->getChild($key)->set($value);
        }

        return TRUE;
    }
}
