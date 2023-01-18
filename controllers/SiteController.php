<?php

namespace app\controllers;

/***************************************************************
 *  Copyright notice
 *
 *  2016 Anton Danilov <anton@i-tribe.de>, interactive tribe GmbH
 *
 *  All rights reserved
 *
 ***************************************************************/

use yii\data\ArrayDataProvider;
use yii\web\Controller;

/**
 * Class SiteController
 * @package Itribe\Typo3Monitor\controllers
 */
class SiteController extends Controller
{

    /**
     * @return string
     */
    public function actionIndex()
    {
        $dataProvider = new ArrayDataProvider([
            'allModels' => \Yii::$app->nagiosDataRepository->getData()
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }
}