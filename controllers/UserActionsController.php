<?php


namespace app\controllers;


use app\models\database\Cottages;
use app\models\Telegram;
use app\models\User;
use Yii;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class UserActionsController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/error', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'alert',
                        ],
                        'roles' => [
                            'reader'
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $type
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionAlert($action): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $status = Cottages::doAlertAction($action);
        return ['status' => 1,  'header' => 'Успешно', 'message' => $status, 'reload' => true];
    }

}