<?php


namespace app\models\database;


use app\models\TargetItem;
use app\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property string $cottage_number [varchar(10)]
 * @property string $year [char(4)]
 * @property string $fixed_part [float unsigned]
 * @property string $square_part [float unsigned]
 * @property int $counted_square [int(10) unsigned]
 * @property string $payed_outside [float unsigned]
 */

class AccrualsTarget extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'accruals_target';
    }


    public static function get($id): AccrualsTarget
    {
        $existent = self::findOne($id);
        return $existent ?? new AccrualsTarget();
    }

    public static function getBalance(User $user)
    {
        $balance = 0;
        $data = self::find()->where(['cottage_number' => $user->cottage_number])->all();
        if (!empty($data)) {
            foreach ($data as $item) {
                $accrued = ($item->fixed_part * 100 - $item->payed_outside * 100);
                // get payed sum
                $payed = PayedTarget::getPayed($item->cottage_number, $item->year);
                $balance += $accrued - $payed;
            }
        }
        return $balance;
    }

    public static function getSlice(?User $user, $limit, $offset): array
    {
        if ($user !== null) {
            $result = [];
            $data = self::find()->where(['cottage_number' => $user->cottage_number])->orderBy('year DESC')->limit($limit)->offset($offset)->all();
            if (!empty($data)) {
                /** @var AccrualsTarget $item */
                foreach ($data as $item) {
                    $newItem = new TargetItem();
                    foreach ($item->attributes as $key => $value) {
                        $newItem->$key = $value;
                    }
                    $newItem->payed = PayedTarget::getPayed($user->cottage_number, $item->year);
                    $result[] = $newItem;
                }
            }
            return ['status' => 'success', 'list' => $result, 'count' => self::find()->where(['cottage_number' => $user->cottage_number])->count()];
        }
        return [];
    }
}