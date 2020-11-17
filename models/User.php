<?php

namespace app\models;

use app\models\database\Cottages;
use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 *
 * @property string $id [int(10) unsigned]
 * @property string $auth_key [varchar(255)]
 * @property string $password_hash [varchar(255)]
 * @property string $password_reset_token [varchar(255)]
 * @property int $status [smallint(6)]
 * @property int $created_at [int(11)]
 * @property int $updated_at [int(11)]
 * @property-read string $authKey
 * @property string $signup_token [varchar(255)]
 * @property string $cottage_number [varchar(10)]
 */
class User extends ActiveRecord implements IdentityInterface
{
    // имя таблицы
    public static function tableName():string
    {
        return 'person';
    }

    /**
     * Finds an identity by the given ID.
     * @param string|int $id the ID to be looked for
     * @return User the identity object that matches the given ID.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentity($id) :?User
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     * @param mixed $token the token to be looked for
     * @param mixed $type the type of the token. The value of this parameter depends on the implementation.
     * For example, [[\yii\filters\auth\HttpBearerAuth]] will set this parameter to be `yii\filters\auth\HttpBearerAuth`.
     * @return User the identity object that matches the given token.
     * Null should be returned if such an identity cannot be found
     * or the identity is not in an active state (disabled, deleted, etc.)
     */
    public static function findIdentityByAccessToken($token, $type = null) :?User
    {
        return self::find()->where(['auth_key' => $token])->one();
    }

    /**
     * @param $username
     * @return User|null
     */
    public static function findByUsername($username): ?User
    {
            return static::find()->where(['cottage_number' => $username])->one();
    }

    public static function registerNew(Cottages $newCottage)
    {
        $password = self::generateNumericPassword();
        $time = time();
        $newRecord = new self([
            'cottage_number' => $newCottage->cottage_number,
            'password_hash' => Yii::$app->getSecurity()->generatePasswordHash($password),
            'auth_key' => Yii::$app->getSecurity()->generateRandomString(255),
            'created_at' => $time,
            'updated_at' => $time
        ]);
        $newRecord->save();
        // выдам права читателя
        $auth = Yii::$app->authManager;
        if ($auth !== null) {
            $readerRole = $auth->getRole('reader');
            $auth->assign($readerRole, $newRecord->getId());
            return $password;
        }
        return $password;
    }

    public static function changePassword($id)
    {
        $existent = self::findByUsername($id);
        if($existent !== null){
            $password = self::generateNumericPassword();
            $existent->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $existent->save();
            return $password;
        }
        return null;
    }

    public static function isAdmin(int $id)
    {
        $roles = Yii::$app->authManager->getRolesByUser($id);
        if(!empty($roles)){
            return array_key_exists('manager', $roles);
        }
        return false;
    }


    public function validatePassword($password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Returns an ID that can uniquely identify a user identity.
     * @return string|int an ID that uniquely identifies a user identity.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns a key that can be used to check the validity of a given identity ID.
     *
     * The key should be unique for each individual user, and should be persistent
     * so that it can be used to check the validity of the user identity.
     *
     * The space of such keys should be big enough to defeat potential identity attacks.
     *
     * This is required if [[User::enableAutoLogin]] is enabled. The returned key will be stored on the
     * client side as a cookie and will be used to authenticate user even if PHP session has been expired.
     *
     * Make sure to invalidate earlier issued authKeys when you implement force user logout, password change and
     * other scenarios, that require forceful access revocation for old sessions.
     *
     * @return string a key that is used to check the validity of a given identity ID.
     * @see validateAuthKey()
     */
    public function getAuthKey():string
    {
        return $this->auth_key;
    }

    /**
     * Validates the given auth key.
     *
     * This is required if [[User::enableAutoLogin]] is enabled.
     * @param string $authKey the given auth key
     * @return bool whether the given auth key is valid.
     * @see getAuthKey()
     */
    public function validateAuthKey($authKey):bool
    {
        return $this->auth_key === $authKey;
    }

    public static function generateNumericPassword(){
        $chars = array_merge(range(0,9));
        shuffle($chars);
        return implode(array_slice($chars, 0,8));
    }
}
