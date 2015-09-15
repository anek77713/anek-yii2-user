<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/anek77713/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace anek77713\user;

use Yii;
use yii\authclient\Collection;
use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApplication;
use yii\i18n\PhpMessageSource;
use yii\web\GroupUrlRule;

/**
 * Bootstrap class registers module and user application component. It also creates some url rules which will be applied
 * when UrlManager.enablePrettyUrl is enabled.
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class Bootstrap implements BootstrapInterface
{
    /** @var array Model's map */
    private $_modelMap = [
        'User'             => 'anek77713\user\models\User',
        'Account'          => 'anek77713\user\models\Account',
        'Profile'          => 'anek77713\user\models\Profile',
        'Token'            => 'anek77713\user\models\Token',
        'RegistrationForm' => 'anek77713\user\models\RegistrationForm',
        'ResendForm'       => 'anek77713\user\models\ResendForm',
        'LoginForm'        => 'anek77713\user\models\LoginForm',
        'SettingsForm'     => 'anek77713\user\models\SettingsForm',
        'RecoveryForm'     => 'anek77713\user\models\RecoveryForm',
        'UserSearch'       => 'anek77713\user\models\UserSearch',
    ];

    /** @inheritdoc */
    public function bootstrap($app)
    {
        /** @var Module $module */
        /** @var \yii\db\ActiveRecord $modelName */
        if ($app->hasModule('user') && ($module = $app->getModule('user')) instanceof Module) {
            $this->_modelMap = array_merge($this->_modelMap, $module->modelMap);
            foreach ($this->_modelMap as $name => $definition) {
                $class = "anek77713\\user\\models\\" . $name;
                Yii::$container->set($class, $definition);
                $modelName = is_array($definition) ? $definition['class'] : $definition;
                $module->modelMap[$name] = $modelName;
                if (in_array($name, ['User', 'Profile', 'Token', 'Account'])) {
                    Yii::$container->set($name . 'Query', function () use ($modelName) {
                        return $modelName::find();
                    });
                }
            }
            Yii::$container->setSingleton(Finder::className(), [
                'userQuery'    => Yii::$container->get('UserQuery'),
                'profileQuery' => Yii::$container->get('ProfileQuery'),
                'tokenQuery'   => Yii::$container->get('TokenQuery'),
                'accountQuery' => Yii::$container->get('AccountQuery'),
            ]);

            if ($app instanceof ConsoleApplication) {
                $module->controllerNamespace = 'anek77713\user\commands';
            } else {
                Yii::$container->set('yii\web\User', [
                    'enableAutoLogin' => true,
                    'loginUrl'        => ['/user/security/login'],
                    'identityClass'   => $module->modelMap['User'],
                ]);

                $configUrlRule = [
                    'prefix' => $module->urlPrefix,
                    'rules'  => $module->urlRules,
                ];

                if ($module->urlPrefix != 'user') {
                    $configUrlRule['routePrefix'] = 'user';
                }

                $app->urlManager->addRules([new GroupUrlRule($configUrlRule)], false);

                if (!$app->has('authClientCollection')) {
                    $app->set('authClientCollection', [
                        'class' => Collection::className(),
                    ]);
                }
            }

            if (!isset($app->get('i18n')->translations['user*'])) {
                $app->get('i18n')->translations['user*'] = [
                    'class'    => PhpMessageSource::className(),
                    'basePath' => __DIR__ . '/messages',
                ];
            }

            $defaults = [
                'welcomeSubject'        => Yii::t('user', 'Welcome to {0}', Yii::$app->name),
                'confirmationSubject'   => Yii::t('user', 'Confirm account on {0}', Yii::$app->name),
                'reconfirmationSubject' => Yii::t('user', 'Confirm email change on {0}', Yii::$app->name),
                'recoverySubject'       => Yii::t('user', 'Complete password reset on {0}', Yii::$app->name),
            ];

            Yii::$container->set('anek77713\user\Mailer', array_merge($defaults, $module->mailer));
        }
    }
}
