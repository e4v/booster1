<?php
namespace app\controllers;
use app\models\User;
use app\models\LoginForm;
use yii\rest\ActiveController;
use Yii;

use yii\filters\auth\HttpBearerAuth;
use function PHPUnit\Framework\returnArgument;

class UserController extends FunctionController
{

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'only' => ['cabinet']
        ];
        return $behaviors;
    }




    public $modelClass = 'app\models\User';

    public function actionRegister()
    {
        $request = Yii::$app->request->post();
        $user = new User($request);
        if (!$user->validate()) return $this->validation($user);
        $user->password = Yii::$app->getSecurity()->generatePasswordHash($user->password);
        $user->save();
        return $this->send(204, $user);
    }

    public function actionLogin()
    {
        $request = Yii::$app->request->post();
        $loginForm = new LoginForm($request);
        if (!$loginForm->validate()) return $this->validation($loginForm);
        $user = User::find()->where(['login' => $request['login']])->one();
        if (isset($user) && Yii::$app->getSecurity()->validatePassword($request['password'], $user->password)) {
            $user->token = Yii::$app->getSecurity()->generateRandomString();
            $user->save(false);
            return $this->send(200, ['content' => ['token' => $user->token]]);
        }
        return $this->send(401, ['content' => ['code' => 401, 'message' => 'Неверный email или пароль']]);
    }

    public function actionSubscription($id)
    {
        $request = Yii::$app->request->getBodyParams();
        $user = User::findOne($id);
        if (!$user) return $this->send(404, ['content' => ['code' => 404, 'message' => 'Пользователь не найден']]);
        $user = Yii::$app->user->identity;
        if (isset($request['subscription'])) $user->subscription = $request['subscription'];
        if (!$user->validate()) return $this->validation($user);
        $user->save();
        return $this->send(200, ['content' => ['code' => 200, 'Подписка' => 'Успешно оформлена']]);
    }


    public function actioncab()
    {
        $user = Yii::$app->user->identity;
        return $this->send(200, ['content' => ['user' => $user]]);
    }

    public function actionEditingcab($id)
    {
        $request = Yii::$app->request->getBodyParams();
        $user = User::findOne($id);
        if (!$user) return $this->send(404, ['content' => ['code' => 404, 'message' => 'Пользователь не найден']]);
        $user = Yii::$app->user->identity;
        if (isset($request['login'])) $user->login = $request['login'];
        if (isset($request['password'])) $user->password = $request['password'] = Yii::$app->getSecurity()->generatePasswordHash($user->password);
        if (isset($request['first_name'])) $user->first_name = $request['first_name'];
        if (isset($request['last_name'])) $user->last_name = $request['last_name'];
        if (isset($request['phone'])) $user->phone = $request['phone'];

        if (!$user->validate()) return $this->validation($user);
        $user->save();
        return $this->send(200, ['content' => ['code' => 200, 'message' => 'Данные обновлены']]);
    }
}
?>