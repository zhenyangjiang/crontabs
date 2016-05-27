<?php
use Landers\Framework\Core\System;
use Landers\Framework\Core\Repository;

class Message extends Repository {
    protected static $connection = 'main';
    protected static $datatable = 'ulan_messages';
    protected static $DAO;

    public static function sendTo($uid, $title, $content) {
        return parent::create([
            'type' => 'user',
            'editor' => '云盾SoC',
            'uid' => $uid,
            'title' => $title,
            'content' => $content,
        ]);
    }
}
Message::init();