<?php namespace app\models\Entity;

use app\models\Events\UserNotifyEvents;
use app\models\Events\UserUpdateEvent;
use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * Class User
 * @package backend\modules\base\models\Auth
 * @property integer $id
 * @property string  $username
 * @property string  $email
 * @property string  $verification_token
 * @property string  $password
 * @property integer $created_at
 * @property integer $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    const EVENT_PASSWORD_UPDATE = 'password_update';
    const EVENT_EMAIL_UPDATE = 'email_update';

    public function init()
    {
        $this->on(self::EVENT_PASSWORD_UPDATE,[Yii::$app->notifier,'sendNotify']);
        $this->on(self::EVENT_EMAIL_UPDATE,[Yii::$app->notifier,'sendNotify']);
        parent::init();
    }

    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * генерация ключа аутентификации
     * @param bool $insert
     * @return bool
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $now = new \DateTime();
            if ($this->isNewRecord) {
                $this->auth_key = \Yii::$app->security->generateRandomString();
                $this->created_at = $now->getTimestamp();
            }
            $this->updated_at = $now->getTimestamp();

            return true;
        }
        return false;
    }

    /**
     * поиск пользователя по id
     * @param int|string $id
     * @return User|IdentityInterface|null
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @param mixed $token
     * @param null $type
     * @return User|IdentityInterface|null
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token]);
    }

    /**
     * Поиск по email
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Поиск по username
     * @param string $username
     * @return static|null
     */
    public static function findByUserName($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * возвращает id пользователя
     * @return int|mixed|string
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * получение ключа
     * @return mixed|string
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * проверка  ключа на валидность
     * @param string $authKey
     * @return bool
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * проверка пароля
     * @param $password
     * @return bool
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    /**
     * генерирует пароль
     * @param $password
     * @throws \yii\base\Exception
     */
    public function setPassword($password)
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates new token for email verification
     */
    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Finds user by verification email token
     */
    public static function findByVerificationToken($token) {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE
        ]);
    }

    public function updateEmail($email)
    {
        $this->email = $email;
        if($this->save()){
            $event = new UserNotifyEvents();
            $event->user_id = $this->id;
            $event->title = 'Обновление E-mail адреса на сайте:'.Yii::$app->name;
            $event->body = 'Ваш E-mail был изменен на '.$this->email;
            $this->trigger(self::EVENT_EMAIL_UPDATE,$event);
        }
    }

    public function updatePassword($password)
    {
        $this->setPassword($password);
        if($this->save()){
            $event = new UserNotifyEvents();
            $event->user_id = $this->id;
            $event->title = 'Обновление пароля на сайте:'.Yii::$app->name;
            $event->body = 'Ваш пароль был изменен';
            $this->trigger(self::EVENT_PASSWORD_UPDATE,$event);
        }
    }

    public static function all()
    {
        return self::find()->all();
    }

}

