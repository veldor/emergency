<?php


namespace app\controllers;


use app\models\Api;
use Exception;
use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ApiController extends Controller
{
    /**
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function beforeAction($action):bool
    {
        if ($action->id === 'do'|| $action->id === 'get-file') {
            // отключу csrf для возможности запроса
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionDo(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return Api::handleRequest();
    }

    public function actionFile(){
        try{
            Api::handleFileRequest();
        }
        catch (Exception $e){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['status' => 'failed', 'message' => 'have error ' . $e->getMessage()];
        }
    }

}