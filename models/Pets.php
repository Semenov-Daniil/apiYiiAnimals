<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "pets".
 *
 * @property int $id
 * @property string $mark
 * @property string $photo1
 * @property string $photo2
 * @property string $photo3
 * @property string $description
 * @property int $kind_id
 *
 * @property Kinds $kind
 * @property Orders[] $orders
 */
class Pets extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pets';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['mark', 'photo1', 'photo2', 'photo3', 'description', 'kind_id'], 'required'],
            [['description'], 'string'],
            [['kind_id'], 'integer'],
            [['mark'], 'string', 'max' => 100],
            [['photo1', 'photo2', 'photo3'], 'string', 'max' => 255],
            [['photo1', 'photo2', 'photo3'], 'default', 'value'=> ''],
            [['kind_id'], 'exist', 'skipOnError' => true, 'targetClass' => Kinds::class, 'targetAttribute' => ['kind_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mark' => 'Mark',
            'photo1' => 'Photo1',
            'photo2' => 'Photo2',
            'photo3' => 'Photo3',
            'description' => 'Description',
            'kind_id' => 'Kind ID',
        ];
    }

    /**
     * Gets query for [[Kind]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getKind()
    {
        return $this->hasOne(Kinds::class, ['id' => 'kind_id']);
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Orders::class, ['pet_id' => 'id']);
    }
}
