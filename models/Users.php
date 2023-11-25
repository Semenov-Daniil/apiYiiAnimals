<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;
use yii\db\Expression as DbExpression;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $token
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Orders[] $orders
 */
class Users extends ActiveRecord implements IdentityInterface
{
    public $password_confirmation;
    public $confirm;

    const SCENARIO_LOGIN = 'login';
    const SCENARIO_REGISTER = 'register';
    const SCENARIO_CHANGEEMAIL = 'changeEmail';

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                // if you're using datetime instead of UNIX timestamp:
                'value' => new DbExpression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'phone', 'email', 'password', 'password_confirmation', 'confirm'], 'required', 'on' => static::SCENARIO_REGISTER],
            [['email', 'password'], 'required', 'on' => static::SCENARIO_LOGIN],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'password'], 'string', 'max' => 255],
            [['email', 'token', 'remember_token'], 'string', 'max' => 100],
            [['phone'], 'string', 'max' => 12],
            ['email', 'email'],
            ['email', 'unique', 'on' => [static::SCENARIO_REGISTER, static::SCENARIO_CHANGEEMAIL]],
            ['name', 'match', 'pattern' => '/^[а-яА-ЯёЁ\s\-]+$/u'],
            ['phone', 'match', 'pattern' => '/^[\+]{0,1}[0-9]{11}$/u'],
            ['password', 'match', 'pattern' => '/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z]).{7,}$/'],
            ['password_confirmation', 'compare', 'compareAttribute' => 'password', 'on' => static::SCENARIO_REGISTER],
            ['confirm', 'required', 'requiredValue' => 1, 'on' => static::SCENARIO_REGISTER, 'message' => 'Нужно подтверждение согласия на обработку персональных данных'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'token' => 'Token',
            'password' => 'Password',
            'remember_token' => 'Remember Token',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Orders::class, ['user_id' => 'id']);
    }

    /**
     * IdentityInterface
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['token' => $token]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }
}
