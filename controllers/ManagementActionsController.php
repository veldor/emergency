<?php


namespace app\controllers;


use app\models\database\Cottages;
use app\models\Notifier;
use app\models\Telegram;
use app\models\User;
use Yii;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ManagementActionsController extends Controller
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
                            'get-form',
                            'register-cottage',
                            'delete-cottage',
                            'test-send',
                            'change-password',
                        ],
                        'roles' => [
                            'manager'
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
    public function actionGetForm($type): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if($type === 'register-new-cottage'){
            $model = new Cottages();
            $view = $this->renderAjax('register_new_cottage_form', ['matrix' => $model]);
            return ['status' => 1,
                'header' => 'Добавление участка',
                'message' => $view,
            ];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array
     */
    public function actionRegisterCottage(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new Cottages();
        $model->load(Yii::$app->request->post());
        return $model->register();
    }

    /**
     * @param $id
     * @return array
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionDeleteCottage($id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return Cottages::deleteCottage($id);
    }
    /**
     * @param $id
     * @return array
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function actionChangePassword($id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $newPassword =  User::changePassword($id);
        if($newPassword !== null){
            return ['status' => 1,  'header' => 'Успешно', 'message' => 'Пароль изменён. Новый: ' . $newPassword, 'reload' => true];
        }
        return ['status' => 2, 'message' => 'Не удалось изменить пароль'];
    }
    public function actionTestSend(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $notifier = new Notifier();
        $notifier->sendBroadcast(Yii::$app->request->post('messageText'));
        return ['status' => 1,  'header' => 'Успешно', 'message' => 'Тестовое сообщение отправлено всем подписавшимся'];
    }
}