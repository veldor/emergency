<?php


namespace app\models\database;


use yii\base\BaseObject;
use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $billId [int(10) unsigned]
 * @property int $cottageId [int(10) unsigned]
 * @property int $year [int(10) unsigned]
 * @property string $summ [float unsigned]
 * @property int $paymentDate [int(10) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */

class PayedTarget extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'payed_target';
    }

    public static function get($id): PayedTarget
    {
        $existent = self::findOne($id);
        return $existent ?? new PayedTarget();
    }

    public static function getPayed(string $cottage_number, string $year)
    {
        $payed = 0;
        $pays = self::findAll(['year' => $year, 'cottageId' => $cottage_number]);
        if(!empty($pays)){
            foreach ($pays as $pay) {
                $payed += ($pay->summ * 100);
            }
        }
        return $payed;
    }
}