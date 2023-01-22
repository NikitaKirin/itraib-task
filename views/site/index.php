<?php

/**
 * @var \yii\data\ArrayDataProvider $dataProvider
 * @todo: implement grid view (title, PHP version, TYPO3 version with status, Extensions status, last update)
 */

use yii\helpers\Html;

echo yii\grid\GridView::widget([
    'dataProvider' => $dataProvider,
    'layout'       => "{items}",
    'columns'      => [
        [
            'attribute' => 'title',
            'label'     => 'Website',
        ],
        [
            'attribute' => 'PHP',
            'label'     => 'PHP',
            'content'   => function ($data) {
                // По статусу определяем: какой класс html-элементу необходимо добавить
                $class = "";
                if ($data['PHP']['warning'] !== 0) {
                    $class = "yellow";
                }
                elseif ($data['PHP']['critical'] !== 0){
                    $class = 'red';
                }
                return "<div class='$class'>{$data['PHP']['version']}</div>";
            },
        ],
        [
            'attribute' => 'TYPO3',
            'label'     => 'TYPO3',
            'content' => function($data){
                $class = "";
                if ($data['TYPO3']['warning'] !== 0){
                    $class = 'yellow';
                }
                elseif($data['TYPO3']['critical'] !== 0){
                    $class = 'red';
                }
                return "<div class='$class' style='margin: -8px; padding: 8px'>{$data['TYPO3']['version']}</div>";
            }
        ],
        [
            'attribute' => 'Extensions',
            'label'     => 'Extensions',
            'content' => function($data) {
                $class = "";
                $text = "";
                if ($data['Extensions']['status'] === 'ok'){
                    $text = 'OK';
                }
                elseif ($data['Extensions']['status'] === 'warning'){
                    $class = 'yellow';
                    $text = 'WARNING';
                }
                elseif($data['Extensions']['status'] === 'critical') {
                    $class = 'red';
                    $text = 'CRITICAL';
                }
                return "<div class='$class' style='margin: -8px; padding: 8px; text-align: center;'>$text</div>";
            }
        ],
        [
            'attribute' => 'LastUpdate',
            'label'     => 'LastUpdate',
        ],
    ],
]);
