<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "kinds".
 *
 * @property int $id
 * @property string $kind
 *
 * @property Pets[] $pets
 */
class Kinds extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'kinds';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['kind'], 'required'],
            [['kind'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'kind' => 'Kind',
        ];
    }

    /**
     * Gets query for [[Pets]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPets()
    {
        return $this->hasMany(Pets::class, ['kind_id' => 'id']);
    }
}
