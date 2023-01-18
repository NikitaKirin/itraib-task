<?php

/**
 * @var \yii\data\ArrayDataProvider $dataProvider
 * @todo: implement grid view (title, PHP version, TYPO3 version with status, Extensions status, last update)
 */

echo yii\grid\GridView::widget([
    'dataProvider' => $dataProvider,
    'layout'=>"{items}",
    'columns' => [
        [
            'attribute' => 'title',
            'label' => 'Website'
        ]
    ]
]);
