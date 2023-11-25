<?php

namespace app\controllers;

use app\models\Orders;
use app\models\Users;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\ActiveController;
use yii\rest\Controller as RestController;

class UserController extends ActiveController
{
    public $modelClass = '';
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => [
                    (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://' . $_SERVER['REMOTE_ADDR'])
                ],
                'Access-Control-Request-Headers' => ['content-type', 'Authorization'],
                'Access-Control-Request-Method' => ['*'],
            ],
        ];

        $auth = [
            'class' => HttpBearerAuth::class,
        ];

        $behaviors['authenticator'] = $auth;
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['delete'], $actions['create'], $actions['index'], $actions['view'], $actions['update']);
        return $actions;
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionProfile()
    {
        $identity = Yii::$app->user->identity;

        $ordersCount = Orders::find()
            ->where(['user_id' => $identity->id])
            ->count();
        
        $petsCount = Orders::find()
            ->where(['user_id' => $identity->id])
            ->andWhere(['status_id' => 4])
            ->count();

        $model = Users::find()
            ->select([
                'id', 'email', 'phone', 'name', 'created_at'
            ])
            ->where(['id' => $identity->id])
            ->asArray()
            ->one();

        $model['ordersCount'] = $ordersCount;
        $model['petsCount'] = $petsCount;

        Yii::$app->response->statusCode = 200;
        return $this->asJson([
            'data' => [
                'user' => $model,
            ],
        ]);
    }

    public function actionChangePhone()
    {
        $identity = Yii::$app->user->identity;
        $postData = Yii::$app->request->post();
        $model = Users::findOne(['id' => $identity->id]);
        $model->phone = $postData['phone'];

        if ($model->save())
        {
            Yii::$app->response->statusCode = 200;
            $answer = [
                'data' => [
                    'status' => 'ok',
                ],
            ];
        } else {
            Yii::$app->response->statusCode = 422;
            $answer = [
                'error' => [
                    "code" => 422,
                    "message" => "Validation error",
                    "error" => "The phone should not be empty."
                ],
            ];
        }
        return $this->asJson($answer);
    }

    public function actionChangeEmail()
    {
        $identity = Yii::$app->user->identity;
        $postData = Yii::$app->request->post();
        $model = Users::findOne(['id' => $identity->id]);
        $model->email = $postData['email'];
        $model->scenario = Users::SCENARIO_CHANGEEMAIL;

        if ($model->save())
        {
            Yii::$app->response->statusCode = 200;
            $answer = [
                'data' => [
                    'status' => 'ok',
                ],
            ];
        } else {
            Yii::$app->response->statusCode = 422;
            $answer = [
                'error' => [
                    "code" => 422,
                    "message" => "Validation error",
                    "error" => "The email should not be empty."
                ],
            ];
        }
        return $this->asJson($answer);
    }

    public function actionOrders()
    {
        $identity = Yii::$app->user->identity;

        $model = Orders::find()
            ->select([
                'orders.id', 'kind', 'description', 'mark', 'district', 'orders.created_at as date',
            ])
            ->innerJoin('pets', 'pets.id = orders.pet_id')
            ->innerJoin('kinds', 'kinds.id = pets.kind_id')
            ->innerJoin('districts', 'districts.id = orders.district_id')
            ->innerJoin('users', 'orders.user_id = users.id')
            ->where(['orders.user_id' => $identity->id]);

        if (!empty($model->asArray()->all()))
        {
            $modelActive = (clone $model)
                ->andWhere(['status_id' => 1])
                ->asArray()
                ->all();

            $modelWasFound = (clone $model)
                ->andWhere(['status_id' => 2])
                ->asArray()
                ->all();

            $modelOnModeration = (clone $model)
                ->andWhere(['status_id' => 3])
                ->asArray()
                ->all();

            $modelArchive = (clone $model)
                ->andWhere(['status_id' => 4])
                ->asArray()
                ->all();

            $model = [
                'active' => $modelActive,
                'wasFound' => $modelWasFound,
                'onModeration' => $modelOnModeration,
                'archive' => $modelArchive,
            ];

            foreach($model as $keyItem => $item)
            {
                if (!empty($item))
                {
                    foreach($item as $keyOrder => $order)
                    {
                        $photos = Orders::find()
                            ->select([
                                'pets.photo1', 'pets.photo2', 'pets.photo3',
                            ])
                            ->innerJoin('pets', 'pets.id = orders.pet_id')
                            ->where(['orders.id' => $order['id']])
                            ->asArray()
                            ->one();
                        
                        $model[$keyItem][$keyOrder]['photos'] = $photos;
                    }
                }
            }

            Yii::$app->response->statusCode = 200;
            return $this->asJson([
                'data' => [
                    'orders' => $model,
                ],
            ]);
        } else {
            Yii::$app->response->statusCode = 204;
            Yii::$app->response->send();
        }
    }

    public function actionDeleteOrder($id = null)
    {
        $identity = Yii::$app->user->identity;
        $checkStatus = Orders::findOne(['id' => $id]);

        if (!empty($checkStatus))
        {
            if ((($checkStatus->status_id == 1) || ($checkStatus->status_id == 3)) && $checkStatus->user_id == $identity->id)
            {
                $checkStatus->delete();
                Yii::$app->response->statusCode = 200;
                $answer = [
                    'data' => [
                        'status' => 200,
                    ],
                ];
            } else {
                Yii::$app->response->statusCode = 403;
                $answer = [
                    'error' => [
                        'code' => 403,
                        'message' => 'Access delete'
                    ],
                ];
            }
            return $this->asJson($answer);
        } else {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->send();
        }
    }
}
