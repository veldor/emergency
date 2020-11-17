<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\daemons\WebSocketServer;
use Workerman\Worker;
use yii\console\Controller;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class WebsocketController extends Controller
{
    public function actionIndex()
    {
        $ws_worker = new Worker("websocket://0.0.0.0:8000");
        $ws_worker->count = 4;
        $ws_worker->onConnect = function($connection)
        {
            echo "New connection\n";
        };
        // Emitted when data received
        $ws_worker->onMessage = function($connection, $data)
        {
            // Send hello $data
            $connection->send('hello ' . $data);
        };

// Emitted when connection closed
        $ws_worker->onClose = function($connection)
        {
            echo "Connection closed\n";
        };

// Run worker
        Worker::runAll();
    }
}
