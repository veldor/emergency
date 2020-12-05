<?php

namespace app\models;

use app\models\database\Blacklist_cottages;
use app\models\database\Blacklist_ips;
use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
    public string $username = '';
    public string $password = '';
    public bool $rememberMe = true;

    private ?User $_user = null;


    /**
     * @return array the validation rules.
     */
    public function rules():array
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels():array
    {
        return [
            'username' => 'Логин',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить меня',
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     */
    public function validatePassword(string $attribute): void
    {
        if (!$this->hasErrors()) {
            // проверю, что пользователь не заблокирован
            $cottageLoginError = Blacklist_cottages::isLoginError($this->username);
            $ipLoginError = Blacklist_ips::isLoginError();
            if($cottageLoginError !== null){
                $this->addError($attribute, $cottageLoginError);
            }
            else if($ipLoginError){
                $this->addError($attribute, $ipLoginError);
            }
            else{
                $user = $this->getUser();
                if (!$user || !$user->validatePassword($this->password)) {
                    // запишу в базу данных сведения об ошибочном входе
                    Blacklist_cottages::registerWrongTry($this->username);
                    Blacklist_ips::registerWrongTry();
                    $this->addError($attribute, 'Неверное имя пользователя или пароль');
                }
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login(): bool
    {
        if ($this->validate()) {
            Blacklist_ips::clearTry();
            Blacklist_cottages::clearTry($this->getUser()->cottage_number);
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }
        return $this->_user;
    }
}
