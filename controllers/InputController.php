<?php


namespace app\controllers;


use app\models\Api;
use app\models\utils\InputHandler;
use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class InputController extends Controller
{
    /**
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        if ($action->id === 'do') {
            // отключу csrf для возможности запроса
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(): array
    {Yii::$app->response->format = Response::FORMAT_JSON;
        $handler = new InputHandler();
        return $handler->insertData();
    }

}