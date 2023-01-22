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
        $versions = file_get_contents('../data/signature.txt');
        $versionsData = [];
        $versionsData['typo3']['warning'] = [];
        $versionsData['typo3']['critical'] = [];
        $versionsData['php']['warning'] = [];
        $versionsData['php']['critical'] = [];
        $versionsData['extensions'] = [];


        preg_match_all('/[^#]typo3-version[.](critical|warning)\s=\s[0-9,x.]+/', $versions, $typoVersionStrings);
        preg_match_all('/[^#]php-version[.](critical|warning)\s=\s[0-9,x.]+/', $versions, $phpVersionStrings);
        preg_match_all(
            '/[^#]extension[.]([a-z0-9_]+)[.](critical|warning)\s=\s([0-9,.]+)/i',
            $versions,
            $extensionsVersionStrings
        );


        $extensionsNames = $extensionsVersionStrings[1];
        $extensionsStatuses = $extensionsVersionStrings[2];
        $extensionsVersions = $extensionsVersionStrings[3];

        // Формируем массив с информацией о расширениях
        for ($i = 0; $i < count($extensionsNames); $i++) {
            $versionsData['extensions'] += [
                $extensionsNames[$i] => [
                    $extensionsVersions[$i],
                    $extensionsStatuses[$i],
                ],
            ];
        }

        //var_dump($versionsData['extensions']);


        foreach ($typoVersionStrings[0] as $typoVersionString) {
            preg_match("/\s[0-9,x.]+/", $typoVersionString, $typoVersion);
            preg_match("/(warning|critical)/", $typoVersionString, $typoVersionStatus);
            $versionsData['typo3'][$typoVersionStatus[0]][] = $typoVersion[0];
        }

        foreach ($phpVersionStrings[0] as $phpVersionString) {
            preg_match("/\s[0-9,x.]+/", $phpVersionString, $phpVersion);
            preg_match("/(warning|critical)/", $phpVersionString, $phpVersionStatus);
            $versionsData['php'][$phpVersionStatus[0]][] = $phpVersion[0];
        }

        $versionsData['typo3']['warning'] = implode(',', $versionsData['typo3']['warning']);
        $versionsData['typo3']['critical'] = implode(',', $versionsData['typo3']['critical']);
        $versionsData['php']['warning'] = implode(',', $versionsData['php']['warning']);
        $versionsData['php']['critical'] = implode(',', $versionsData['php']['critical']);

        //var_dump($versionsData['typo3']);

        $result = [];

        $dirs = array_slice(scandir('../data/sites'), 2);

        foreach ($dirs as $title) {

            $files = scandir("../data/sites/$title");
            $lastFile = $files[count($files) - 1];
            $lastFileContent = file_get_contents("../data/sites/$title/$lastFile");
            if (strlen($lastFileContent) < 1) {
                $lastFile = $files[count($files) - 2];
                $lastFileContent = file_get_contents("../data/sites/$title/$lastFile");
            }

            $php = rtrim(substr($lastFileContent, strripos($lastFileContent, 'PHP:version-') + 12, 6));
            $typo3 = rtrim(substr($lastFileContent, strripos($lastFileContent, 'TYPO3:version-') + 14, 5));
            $lastUpdate = substr($lastFileContent, strripos($lastFileContent, 'TIMESTAMP:') + 10, 10);
            $timeZone = substr($lastFileContent, strripos($lastFileContent, 'TIMESTAMP:') + 21, 4);
            preg_match_all("/EXT:([a-z0-9_]+)-([0-9.]+)/", $lastFileContent, $siteExtensionsVersionStrings);

            $siteExtensionsNames = $siteExtensionsVersionStrings[1];
            $siteExtensionsVersions = $siteExtensionsVersionStrings[2];

            $siteExtensionStatus = "ok";
            for ($i = 0; $i < count($siteExtensionsNames); $i++) {
                // Проверка на существование extension в базе данных
                if (array_key_exists($siteExtensionsNames[$i], $versionsData['extensions'])) {
                    $dataExtensionVersions = $versionsData['extensions'][$siteExtensionsNames[$i]][0];
                    $flag = preg_match("/($siteExtensionsVersions[$i])/", $dataExtensionVersions);
                    if ($flag > 0) {
                        $siteExtensionStatus = $versionsData['extensions'][$siteExtensionsNames[$i]][1];
                    }
                }
            }


            $test = new \DateTime();
            if ($timeZone !== false) {
                $test->setTimestamp($lastUpdate);
            }


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


            $result[] =
                [
                    'title'      => $title,
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
                    "LastUpdate" => Carbon::make($test)->format('d.m.Y h:m'),
                ];
        }

        //var_dump($result);
        return $result;
    }
}