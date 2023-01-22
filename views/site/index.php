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
                $class = "";
                if ($data['PHP']['warning'] !== 0) {
                    $class = "yellow";
                }
                elseif ($data['PHP']['critical'] !== 0){
                    $class = 'red';
                }
                return "<div class='$class'>{$data['PHP']['version']}</div>";
                //return $data['PHP']['version'] . ' ' . $data['PHP']['warning'] . ' ' . $data['PHP']['critical'] ;
            },
            //'class'     => 'test',
        ],
        [
            'attribute' => 'TYPO3',
            'label'     => 'TYPO3',
            /*'value'     => function ($data) {
                // if ($data['PHP'])
                return $data['TYPO3']['version']." ".$data["TYPO3"]['warning']." ".$data["TYPO3"]['critical'];
            },*/
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
        ],
        [
            'attribute' => 'LastUpdate',
            'label'     => 'LastUpdate',
        ],
    ],
]);
