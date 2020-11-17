<?php


namespace app\controllers;


use app\models\Viber;
use JsonException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

class ViberController extends Controller
{
    /**
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function beforeAction($action):bool
    {
        if ($action->id === 'connect') {
            // отключу csrf для возможности запроса
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }


    /**
     * настройка хука, выполняется только один раз при регистрации бота
     */
    public function actionSetup(): void
    {
        Viber::setup();
    }

    public function actionConnect(): void
    {
        try {
            Viber::handleRequest();
        } catch (JsonException $e) {
        }
    }
}