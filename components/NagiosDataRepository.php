<?php

namespace app\components;

/***************************************************************
 *  Copyright notice
 *
 *  2016 Anton Danilov <anton@i-tribe.de>, interactive tribe GmbH
 *
 *  All rights reserved
 *
 ***************************************************************/

use Carbon\Carbon;
use yii\base\Component;
use yii\helpers\VarDumper;

/**
 * Class NagiosDataRepository
 * @package app\components
 */
class NagiosDataRepository extends Component
{

    /**
     * Sites log data directory path
     *
     * @var string
     */
    public $filesPath;

    /**
     * Signature file path
     *
     * @var string
     */
    public $signature;

    /**
     * @return array
     * TODO: Implement getData() method.
     */
    public function getData()
    {
        // Забираем данные об актуальных версиях PHP, TYPO3 и extensions
        $versions = file_get_contents('../data/signature.txt');

        // Инициализируем массив и структуру с информацией о версиях
        $versionsData = [];
        $versionsData['typo3']['warning'] = [];
        $versionsData['typo3']['critical'] = [];
        $versionsData['php']['warning'] = [];
        $versionsData['php']['critical'] = [];
        $versionsData['extensions'] = [];


        // Парсим данные из файла signature.txt
        preg_match_all(
            '/[^#]typo3-version[.](critical|warning)\s=\s[0-9,x.]+/',
            $versions,
            $typoVersionStrings
        ); // Информация о версиях TYPO3
        preg_match_all(
            '/[^#]php-version[.](critical|warning)\s=\s[0-9,x.]+/',
            $versions,
            $phpVersionStrings
        ); // Информация о версиях PHP
        preg_match_all(
            '/[^#]extension[.]([a-z0-9_]+)[.](critical|warning)\s=\s([0-9,.]+)/i',
            $versions,
            $extensionsVersionStrings
        ); // Информация о версиях extensions


        // Формируем массивы с информацией об extensions: names, versions, status (critical or warning)
        $extensionsNames = $extensionsVersionStrings[1];
        $extensionsStatuses = $extensionsVersionStrings[2];
        $extensionsVersions = $extensionsVersionStrings[3];

        // Формируем итоговый массив с информацией о расширениях
        for ($i = 0; $i < count($extensionsNames); $i++) {
            $versionsData['extensions'] += [
                $extensionsNames[$i] => [
                    explode(',',$extensionsVersions[$i]),
                    $extensionsStatuses[$i],
                ],
            ];
        }


        // Формируем итоговый массив с информацией о версиях TYPO3
        foreach ($typoVersionStrings[0] as $typoVersionString) {
            preg_match("/\s[0-9,x.]+/", $typoVersionString, $typoVersion); // Парсим версии
            preg_match("/(warning|critical)/", $typoVersionString, $typoVersionStatus); // Парсим статус
            $versionsData['typo3'][$typoVersionStatus[0]][] = $typoVersion[0];
        }


        // Формируем итоговый массив с информацией о версиях PHP
        foreach ($phpVersionStrings[0] as $phpVersionString) {
            preg_match("/\s[0-9,x.]+/", $phpVersionString, $phpVersion); // Парсим версии
            preg_match("/(warning|critical)/", $phpVersionString, $phpVersionStatus); // Парсим статус
            $versionsData['php'][$phpVersionStatus[0]][] = $phpVersion[0];
        }

        $versionsData['typo3']['warning'] = implode(
            ',',
            $versionsData['typo3']['warning']
        ); // Соединяем версии TYPO3 со статусом warning в одну строку – ниже аналогичные действия
        $versionsData['typo3']['critical'] = implode(',', $versionsData['typo3']['critical']);
        $versionsData['php']['warning'] = implode(',', $versionsData['php']['warning']);
        $versionsData['php']['critical'] = implode(',', $versionsData['php']['critical']);

        $result = []; // Инициализируем итоговый массив

        $dirs = array_slice(scandir('../data/sites'), 2); // Сканируем список директорий с сайтами

        // Пробегаем по директории каждого сайта
        foreach ($dirs as $title) {
            $files = scandir("../data/sites/$title"); // Получаем список всех файлов с инфой текущего сайта
            $fileIndex = count($files) - 1;
            $lastFile = $files[$fileIndex];
            $lastFileContent = file_get_contents("../data/sites/$title/$lastFile");

            // Проверка на пустой файл с нужной информацией
            while (strlen($lastFileContent) < 1){
                $lastFile = $files[$fileIndex--];
                $lastFileContent = file_get_contents("../data/sites/$title/$lastFile");
            }

            // Получаем название сайта
            preg_match("/SITENAME:([a-z-+]+)/i", $lastFileContent, $siteTitleStrings);
            $siteTitle = str_replace("+", " ", $siteTitleStrings[1]);


            // Получаем все данные о версиях модулей текущего сайта
            preg_match("/PHP:version-([0-9.]+)/", $lastFileContent, $phpVersionStrings);
            $php = $phpVersionStrings[1];
            preg_match("/TYPO3:version-([0-9.]+)/", $lastFileContent, $typoVersionStrings);
            $typo3 = $typoVersionStrings[1];
            preg_match("/TIMESTAMP:(\d+)-\w+/i", $lastFileContent, $siteDateTime);
            preg_match_all("/EXT:([a-z0-9_]+)(-version)?-([0-9.]+)/", $lastFileContent, $siteExtensionsVersionStrings);

            // Работаем с меткой времени текущего сайта
            $siteTimestamp = $siteDateTime[1];
            $finalSiteTimestamp = new \DateTime();
            $finalSiteTimestamp->setTimestamp($siteTimestamp);

            // Работаем с extensions текущего сайта
            $siteExtensionsNames = $siteExtensionsVersionStrings[1]; // Формируем массив с именами extensions текущего сайта
            $siteExtensionsVersions = $siteExtensionsVersionStrings[3]; // Формируем массив с версиями extensions текущего сайта
            $siteExtensionStatus = "ok"; // Статус расширений текущего сайта

            // Проверяем все extensions текущего сайта с данными из файла signatures.txt
            for ($i = 0; $i < count($siteExtensionsNames); $i++) {
                // Проверка на существование extension в файле signatures.txt
                if (array_key_exists($siteExtensionsNames[$i], $versionsData['extensions'])) {
                    $dataExtensionVersions = $versionsData['extensions'][$siteExtensionsNames[$i]][0];
                    if (in_array($siteExtensionsVersions[$i], $dataExtensionVersions)) {
                        $siteExtensionStatus = $versionsData['extensions'][$siteExtensionsNames[$i]][1]; // Ставим статус расширения critical или warning
                    }
                }
            }


            // Сверяем версию PHP с файлом signature.txt
            if (strlen($php) > 1) {
                $count = strlen($php) - 3;
                $phpWarning = preg_match(
                    "/($php([,\s]))|($php[0][.]$php[2]([.x0-9]{".$count."}))/",
                    $versionsData['php']['warning']
                );
                $phpCritical = preg_match(
                    "/($php([,\s]))|($php[0][.]$php[2]([.x0-9]{".$count."}))/",
                    $versionsData['php']['critical']
                );
            } else {
                $phpWarning = preg_match("/$php([,\s])/", $versionsData['php']['warning']);
                $phpCritical = preg_match("/$php([,\s])/", $versionsData['php']['critical']);
            }

            // Сверяем версию TYPO3 с файлом signature.txt
            if (strlen($typo3) > 1) {
                $count = strlen($typo3) - 3;
                $typo3Warning = preg_match(
                    "/($typo3([,\s]))|($typo3[0][.]$typo3[2]([.x0-9]{".$count."}))/",
                    $versionsData['typo3']['warning']
                );
                $typo3Critical = preg_match(
                    "/($typo3([,\s]))|($typo3[0][.]$typo3[2]([.x0-9]{".$count."}))/",
                    $versionsData['typo3']['critical']
                );
            } else {
                $typo3Warning = preg_match("/$typo3([,\s])/", $versionsData['typo3']['warning']);
                $typo3Critical = preg_match("/$typo3([,\s])/", $versionsData['typo3']['critical']);
            }


            // Формируем результирующий массив
            $result[] =
                [
                    'title'      => $siteTitle,
                    'PHP'        => [
                        'version'  => $php,
                        'warning'  => $phpWarning,
                        'critical' => $phpCritical,
                    ],
                    'TYPO3'      => [
                        'version'  => $typo3,
                        'warning'  => $typo3Warning,
                        'critical' => $typo3Critical,
                    ],
                    'Extensions' => [
                        'status' => $siteExtensionStatus,
                    ],
                    "LastUpdate" => Carbon::make($finalSiteTimestamp)->format('d.m.Y h:m'),
                ];
        }

        return $result;
    }
}