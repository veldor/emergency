<?php


namespace app\daemons;


use consik\yii2websocket\events\WSClientMessageEvent;

class WebSocketServer extends \consik\yii2websocket\WebSocketServer
{
    public function init()
    {
        parent::init();

        $this->on(self::EVENT_CLIENT_MESSAGE, function (WSClientMessageEvent $e) {
            $e->client->send( $e->message );
        });
    }
}