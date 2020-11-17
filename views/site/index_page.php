<?php

/* @var $this yii\web\View */

use app\assets\IndexAsset;
use app\models\database\Cottages;
use app\models\database\DefenceDevice;
use app\models\utils\GrammarHandler;
use nirvana\showloading\ShowLoadingAsset;

IndexAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Теперь это наш сервер :)';
?>
<div class="row">
    <?php
    if(Yii::$app->user->isGuest){
    ?>
    <div class="col-xs-12 text-center">
        <a href="/login" class="btn btn-default">
            <span class="text-success">У меня тут участок</span>
        </a>
    </div>
    <?php
        }
    else{
        $cottageInfo = Cottages::get(Yii::$app->user->identity->cottage_number);
        if($cottageInfo !== null){
            echo "<h3 class='text-center'>" . GrammarHandler::dayTimeGreetings() . ', ' . GrammarHandler::getUserIo($cottageInfo->owner_personals) . "</h3>";
            echo "<h4 class='text-center'>Номер вашего участка: {$cottageInfo->cottage_number}</h4>";
            // проверю, подключен ли участок к защите
            if($cottageInfo->binded_defence_device !== null){
                $defenceDevice = DefenceDevice::findOne($cottageInfo->binded_defence_device);
                if($defenceDevice !== null){
                    echo "<h2 class='text-center'>Статус защиты: " . ($defenceDevice->status ? '<b class="text-success">включена</b>' : '<b class="text-danger">отключена</b>') . "</h2>";
                    if($defenceDevice->status){
                        echo "<div class='text-center'><button class='btn btn-lg btn-danger activate' data-action='/alert/disable'>Отключить защиту</button></div>";
                    }
                    else{
                        echo "<div class='text-center'><button class='btn btn-lg btn-success activate' data-action='/alert/enable'>Активировать защиту</button></div>";
                    }
                }
            }
            else{
                echo "<h2 class='text-center text-danger'>Ваш участок не подключен к системе защиты</h2>";
            }
            if($cottageInfo->external_temperature !== null){
                echo "<div class='text-center'><h4>Температура на улице: " . GrammarHandler::convertTemperature($cottageInfo->external_temperature) . "</h4></div>";
            }
            if($cottageInfo->last_indication_time !== null){
                echo "<div class='text-center'><h4>Время последнего выхода счётчика на связь: <b class='text-info'>" . GrammarHandler::timestampToDate($cottageInfo->last_indication_time) . "</b></h4></div>";
            }
            if($cottageInfo->current_counter_indication !== null){
                echo "<div class='text-center'><h4>Последние показания счётчика: <b class='text-info'>" . GrammarHandler::handleCounterData($cottageInfo->current_counter_indication) . "</b></h4></div>";
            }
        }
    }
    ?>
</div>
