<?php


namespace app\models\database;


use app\models\User;
use phpDocumentor\Reflection\Types\Null_;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $counter_number [varchar(10) unsigned]  Номер участка
 * @property string $value [varchar(10) unsigned]  Новое стартовое значение
 */

class StartValueChange extends ActiveRecord
{


    public static function tableName(): string
    {
        return 'start_value_changes';
    }

    /**
     * @param $counterNumber
     * @throws StaleObjectException
     * @throws \Throwable
     */
    public static function acceptChanges($counterNumber): ?string
    {
        $lastValue = null;
        $waitingChanges = self::find()->where(['counter_number' => $counterNumber])->all();
        if(!empty($waitingChanges)){
            /** @var StartValueChange $waitingChange */
            foreach ($waitingChanges as $waitingChange) {
                $lastValue = $waitingChange->value;
                $waitingChange->delete();
            }
        }
        return $lastValue;
    }

}