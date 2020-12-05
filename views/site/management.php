<?php

use app\assets\ManagementAsset;
use app\models\database\Cottages;
use app\models\User;
use app\models\utils\GrammarHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;

/* @var $this View */

ManagementAsset::register($this);
ShowLoadingAsset::register($this);

?>

<ul class="nav nav-tabs">
    <li id="bank_set_li" class="active"><a href="#global_actions" data-toggle="tab" class="active">Обшие действия</a>
    </li>
    <li><a href="#test_send" data-toggle="tab">Тест оповещения</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="global_actions">
        <div class="row">
            <?php
            // выведу список зарегистрированных участков

            echo '<div class="col-sm-12 text-center"><button class="btn btn-default activate margin-bottom" data-action="/get/form/register-new-cottage"><span class="text-success">Добавить участок</span></button></div>';

            $usersList = User::getAll();
            if (!empty($usersList)) {
                echo '<table class="table table-striped"><thead><tr><th>№</th><th>ФИО владельца</th><th>Защита</th><th>Действия</th><th>Данные получены</th><th>Темп.</th><th>Показания</th></tr></thead><tbody>';
                foreach ($usersList as $user) {
                    $cottage = Cottages::get($user->cottage_number);
                    if ($cottage !== null) {
                        $receiveDataTime = $cottage->data_receive_time ? GrammarHandler::timestampToDate($cottage->data_receive_time) : '--';
                        $temp = $cottage->external_temperature ? GrammarHandler::convertTemperature($cottage->external_temperature) : '--';
                        $indication = $cottage->current_counter_indication ? GrammarHandler::handleCounterData($cottage->current_counter_indication) : '--';
                        echo "<tr><td>{$cottage->cottage_number}</td><td>{$cottage->owner_personals}</td><td>" . ($cottage->alert_status === 1 ? '<span class="glyphicon glyphicon-off text-success"></span>' : '<span class="glyphicon glyphicon-off text-danger"></span>') . "</td><td><div class='btn-block'><button class='btn btn-default'><span class='glyphicon glyphicon-trash text-danger activate' data-action='/delete-cottage/{$cottage->cottage_number}'></span></button><button class='btn btn-default'><span class='glyphicon glyphicon-refresh text-info activate' data-action='/change-password/{$cottage->cottage_number}'></span></button></div></td><td>$receiveDataTime</td><td>$temp</td><td>$indication</td></tr>";
                    }
                }
                echo '</tbody></table>';
            }
            ?>
        </div>
    </div>
    <div class="tab-pane margin-bottom" id="test_send">
        <div class="row">
            <div class="col-sm-12">
                <form id="testSendForm">
                    <div class="col-sm-12">
                        <textarea name="messageText" id="messageText">
                            Введите сюда сообщение
                        </textarea>
                    </div>
                    <div class="col-sm-12">
                        <button class="btn btn-default"><span class="text-info">Отправить</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

