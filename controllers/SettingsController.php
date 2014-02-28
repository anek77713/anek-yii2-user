<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace dektrium\user\controllers;

use dektrium\user\models\Profile;
use yii\web\AccessControl;
use yii\web\Controller;

/**
 * SettingsController manages updating user settings (e.g. profile, email and password).
 *
 * @property \dektrium\user\Module $module
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class SettingsController extends Controller
{
	/**
	 * @inheritdoc
	 */
	public $defaultAction = 'profile';

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'actions' => ['profile', 'email', 'password'],
						'roles' => ['@']
					],
				]
			],
		];
	}

	/**
	 * Shows profile settings form.
	 *
	 * @return string|\yii\web\Response
	 */
	public function actionProfile()
	{
		$model = Profile::find(['user_id' => \Yii::$app->getUser()->getIdentity()->getId()]);

		if ($model->load($_POST) && $model->save()) {
			\Yii::$app->getSession()->setFlash('settings_saved', \Yii::t('user', 'Profile updated successfully'));
			return $this->refresh();
		}

		return $this->render('profile', [
			'model' => $model
		]);
	}

	/**
	 * Shows email settings form.
	 *
	 * @return string|\yii\web\Response
	 */
	public function actionEmail()
	{
		return;
	}

	/**
	 * Shows password settings form.
	 *
	 * @return string|\yii\web\Response
	 */
	public function actionPassword()
	{
		$query = $this->module->factory->createQuery();
		$model = $query->where(['id' => \Yii::$app->getUser()->getIdentity()->getId()])->one();
		$model->scenario = 'passwordSettings';

		if ($model->load($_POST) && $model->save()) {
			\Yii::$app->getSession()->setFlash('settings_saved', \Yii::t('user', 'Password updated successfully'));
			$this->refresh();
		}

		return $this->render('password', [
			'model' => $model
		]);
	}
}