<?php


namespace app\models\utils;


use app\models\TelegramService;
use Yii;

class PingChecker
{
    /**
     * @return string
     */
    public function getPing(): string
    {
        $file = Yii::$app->basePath . '/last_ping.time';
        if (is_file($file)) {
            return file_get_contents($file);
        }
        TelegramService::notify("ping empty, create it");
        $this->setPing();
        return time();
    }

    public function setPing(): void
    {
        $file = Yii::$app->basePath . '/last_ping.time';
        if ($this->isNotified()) {
            // сервер долго был не в сети, оповещу, что он на месте
            TelegramService::notify("Сервер снова в сети");
            $this->setNotified(false);
        }
        file_put_contents($file, time());
    }
    public function setLastReceivedData(): void
    {
        $file = Yii::$app->basePath . '/last_data_received.time';
        file_put_contents($file, time());
    }

    public function checkServerPing(): void
    {
        $ping = $this->getPing();
        if ((int)$ping + 600 < time() && !$this->isNotified()) {
            // оповещу в сервисный канал и отмечу, что было отправлено уведомление
            $this->setNotified(true);
            TelegramService::notify("Сервер долго не выходит на связь. Последний раз: " . TimeHandle::timestampToDate($this->getPing()) . ", сейчас: " . TimeHandle::timestampToDate(time()));
        }
    }

    private function isNotified(): bool
    {
        $file = Yii::$app->basePath . '/notified.info';
        if (is_file($file)) {
            return (bool)file_get_contents($file);
        }
        file_put_contents($file, 0);
        return 0;
    }

    private function setNotified(bool $state): void
    {
        $file = Yii::$app->basePath . '/notified.info';
        file_put_contents($file, $state ? 1 : 0);
    }

    /**
     * @return string
     */
    public function getLastReceivedDataTime(): string
    {
        $file = Yii::$app->basePath . '/last_data_received.time';
        if (is_file($file)) {
            return file_get_contents($file);
        }
        return '0';
    }
}