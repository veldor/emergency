<?php


namespace app\models\utils;

use Yii;
use yii\base\Model;

class MailHandler extends Model
{
    public static function getMailText($text): string
    {
        return Yii::$app->controller->renderPartial('/site/mail-template', ['text' => $text]);
    }

    public static function getLightMailText($text): string
    {
        return Yii::$app->controller->renderPartial('/site/mail-template-light', ['text' => $text]);
    }
}