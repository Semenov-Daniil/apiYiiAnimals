<?php

namespace app\controllers;

use app\models\Districts;
use app\models\Kinds;
use app\models\Orders;
use app\models\Orders\Orders as OrdersOrders;
use app\models\Pets;
use app\models\Users;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\ActiveController;

class PetController extends ActiveController
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

    public function actionSlider()
    {
        $model = Orders::find()
            ->select([
                'pets.id', 'kind', 'description', 'photo1 as image'
            ])
            ->innerJoin('pets', 'pets.id = orders.pet_id')
            ->innerJoin('kinds', 'kinds.id = pets.kind_id')
            ->where(['status_id' => 2])
            ->asArray()
            ->all();

        if (!empty($model)) {
            Yii::$app->response->statusCode = 200;
            return $this->asJson([
                'data' => [
                    'pets' => $model,
                ]
            ]);
        } else {
            Yii::$app->response->statusCode = 204;
            Yii::$app->response->send();
        }
    }

    public function actionCards()
    {
        $model = Orders::find()
            ->select([
                'orders.id', 'phone', 'name', 'email', 'kind', 'photo1', 'description', 'mark', 'district', 'orders.created_at as date', "if(users.password <> '', true, false) as register"
            ])
            ->innerJoin('pets', 'pets.id = orders.pet_id')
            ->innerJoin('kinds', 'kinds.id = pets.kind_id')
            ->innerJoin('users', 'users.id = orders.user_id')
            ->innerJoin('districts', 'districts.id = orders.district_id')
            ->innerJoin('statuses', 'statuses.id = orders.status_id')
            ->where(['status' => 'active'])
            ->orderBy(['orders.created_at' => SORT_DESC])
            ->limit(6)
            ->asArray()
            ->all();

        if (!empty($model)) {
            Yii::$app->response->statusCode = 200;
            return $this->asJson([
                'data' => [
                    'orders' => $model,
                ]
            ]);
        } else {
            Yii::$app->response->statusCode = 204;
            Yii::$app->response->send();
        }
    }

    public function actionCardOne($id = null)
    {
        $model = Orders::find()
            ->select([
                'orders.id as id', 'users.name', 'users.email', 'users.phone', 'kinds.kind', 'pets.description', 'pets.mark', 'districts.district', 'orders.created_at as date'
            ])
            ->innerJoin('pets', 'pets.id = orders.pet_id')
            ->innerJoin('kinds', 'kinds.id = pets.kind_id')
            ->innerJoin('users', 'users.id = orders.user_id')
            ->innerJoin('districts', 'districts.id = orders.district_id')
            ->where(['orders.id' => $id])
            ->asArray()
            ->one();

        if (!empty($model))
        {
            $photos = Orders::find()
                ->select([
                    'pets.photo1', 'pets.photo2', 'pets.photo3',
                ])
                ->innerJoin('pets', 'pets.id = orders.pet_id')
                ->where(['orders.id' => $id])
                ->asArray()
                ->one();
            $model['photos'] = $photos;
            return $this->asJson([
                'data' => [
                    'pet' => $model,
                ]
            ]);
        } else {
            Yii::$app->response->statusCode = 404;
            Yii::$app->response->send();
        }
    }

    public function actionCreateOrder() 
    {
        $dataPost = Yii::$app->request->post();
        $kind = Kinds::findOne(['kind' => $dataPost['kind']]);
        $dataPost['kind_id'] = $kind->id;
        $district = Districts::findOne(['district' => $dataPost['district']]);
        $dataPost['district_id'] = $district->id;
        $dataPost['user_id'] = '';
        $dataPost['status_id'] = 3;

        if (!empty($dataPost['password'])) {
            $model = new Users();
            $model->scenario = Users::SCENARIO_REGISTER;

            if ($model->load(Yii::$app->request->post(), '') && $model->save())
            {
                $newToken = Yii::$app->security->generateRandomString();
                $model->token = $newToken;
                $model->remember_token = $newToken;
                $model->save();

                $dataPost['user_id'] = $model->id;
            } else {
                Yii::$app->response->statusCode = 422;
                return $this->asJson([
                    'error' => [
                        'code' => 422,
                        'message' => 'Validation error',
                        'errors' => [
                            $model->errors,
                        ],
                    ],
                ]);
            }
        } 

        if (!empty($dataPost['token'])) {
            $user = Users::findOne(['token' => $dataPost['token']]);
            $dataPost['user_id'] = $user->id;
        }

        $modelPet = new Pets();
        if ($modelPet->load($dataPost, '') && $modelPet->save())
        {
            $dataPost['pet_id'] = $modelPet->id;
            $modelOrder = new Orders();
            if ($modelOrder->load($dataPost, '') && $modelOrder->save()) {
                Yii::$app->response->statusCode = 200;
                return $this->asJson([
                    'data' => [
                        'status' => 'ok',
                        'id' => $modelOrder->id,
                    ],
                ]);
            } else {
                Yii::$app->response->statusCode = 422;
                return $this->asJson([
                    'error' => [
                        'code' => 422,
                        'message' => 'Validation error order',
                        'errors' => [
                            $modelOrder->errors,
                        ],
                    ],
                ]);
            }
        } else {
            Yii::$app->response->statusCode = 422;
            return $this->asJson([
                'error' => [
                    'code' => 422,
                    'message' => 'Validation error pets',
                    'errors' => [
                        $modelPet->errors,
                    ],
                ],
            ]);
        }
    }
}
