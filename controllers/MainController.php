<?php

namespace app\controllers;

use app\models\Districts;
use app\models\Kinds;
use app\models\Orders;
use app\models\Pets;
use app\models\Subscriptions;
use app\models\Users;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use Yii;
use yii\filters\Cors;
use yii\rest\ActiveController;
use yii\rest\Controller;

class MainController extends ActiveController
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
        return $this->asJson('Hello World');
    }

    public function actionRegister()
    {
        $model = new Users();
        $model->scenario = Users::SCENARIO_REGISTER;

        if ($model->load(Yii::$app->request->post(), '') && $model->save())
        {
            $newToken = Yii::$app->security->generateRandomString();
            $model->token = $newToken;
            $model->remember_token = $newToken;
            $model->save();

            Yii::$app->response->statusCode = 204;
            Yii::$app->response->send();
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

    public function actionLogin()
    {
        $model = new Users();
        $model->scenario = Users::SCENARIO_LOGIN;

        if ($model->load(Yii::$app->request->post(), ''))
        {
            if ($model->validate())
            {
                if ($model = Users::findOne(['email' => $model->email, 'password' => $model->password]))
                {
                    $newToken = Yii::$app->security->generateRandomString();
                    $model->token = $newToken;
                    $model->remember_token = $newToken;
                    $model->save();

                    Yii::$app->response->statusCode = 200;
                    $answer = [
                        'data' => [
                            'token' => $model->token,
                        ],
                    ];
                } else {
                    Yii::$app->response->statusCode = 401;
                    $answer = [
                        'error' => [
                            'code' => 401,
                            'message' => 'Unauthorized',
                            'errors' => 'email or password incorrect',
                        ],
                    ];
                }
            } else {
                Yii::$app->response->statusCode = 422;
                    $answer = [
                        'error' => [
                            'code' => 422,
                            'message' => 'Validation error',
                            'errors' => [
                                $model->errors,
                            ],
                        ],
                    ];
            }
        }
        return $this->asJson($answer);
    }

    public function actionSearch()
    {
        $description = Yii::$app->request->get('description');

        if ($description) {
            $model = Orders::find()
            ->select([
                'pets.id', 'kind', 'description', 'mark', 'district', 'created_at as date'
            ])
            ->innerJoin('pets', 'pets.id = orders.pet_id')
            ->innerJoin('kinds', 'kinds.id = pets.kind_id')
            ->innerJoin('districts', 'districts.id = orders.district_id')
            ->where(['like', 'pets.description', $description]);
        } else {
            $model = Orders::find()
                ->select([
                    'orders.id', 'phone', 'name', 'email', 'kind', 'photo1 as photo' ,'description', 'mark', 'district', 'orders.created_at as date'
                ])
                ->innerJoin('pets', 'pets.id = orders.pet_id')
                ->innerJoin('kinds', 'kinds.id = pets.kind_id')
                ->innerJoin('users', 'users.id = orders.user_id')
                ->innerJoin('districts', 'districts.id = orders.district_id');

            $district = Yii::$app->request->get('district');
            $kind = Yii::$app->request->get('kind');

            if ($district) {
                $model->where(['district' => $district]);
            }

            if ($kind) {
                $model->andWhere(['like', 'kind', $kind]);
            }
        }

        $model = $model->asArray()->all();

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

    public function actionSubscription()
    {
        $model = new Subscriptions();

        if ($model->load(Yii::$app->request->post(), '') && $model->save())
        {
            Yii::$app->response->statusCode = 200;
            Yii::$app->response->send();
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

    public function actionDistricts()
    {
        $model = Districts::find()
            ->select('*')
            ->asArray()
            ->all();

        Yii::$app->response->statusCode = 200;
        return $this->asJson([
            'data' => [
                'districts' => $model,
            ]
        ]);
    }

    public function actionKinds()
    {
        $model = Kinds::find()
            ->select('*')
            ->asArray()
            ->all();

        Yii::$app->response->statusCode = 200;
        return $this->asJson([
            'data' => [
                'kinds' => $model,
            ]
        ]);
    }
}
