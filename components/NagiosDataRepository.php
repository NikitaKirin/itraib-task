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

        preg_match_all('/[^#]typo3-version[.](critical|warning)\s=\s[0-9,x.]+/', $versions, $typoVersionStrings);
        preg_match_all('/[^#]php-version[.](critical|warning)\s=\s[0-9,x.]+/', $versions, $phpVersionStrings);


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


        $result = [];

        $dirs = array_slice(scandir('../data/sites'), 2);

        foreach ($dirs as $title) {
            $files = scandir("../data/sites/$title");
            $lastFile = $files[count($files) - 1];
            $lastFileContent = file_get_contents("../data/sites/$title/$lastFile");
            $php = substr($lastFileContent, strripos($lastFileContent, 'PHP:version-') + 12, 6);
            $typo3 = substr($lastFileContent, strripos($lastFileContent, 'TYPO3:version-') + 14, 5);
            $lastUpdate = substr($lastFileContent, strripos($lastFileContent, 'TIMESTAMP:') + 10, 10);
            $timeZone = substr($lastFileContent, strripos($lastFileContent, 'TIMESTAMP:') + 21, 4);
            //$lastUpdate = preg_match("/TIMESTAMP:[0-9]-CEST/", $lastFileContent);
            $test = new \DateTime();
            if ($timeZone !== false) {
                $test->setTimestamp($lastUpdate);
            }
            //$test->setTimezone(timezone_open($timeZone));
            $result[] =
                [
                    'title'      => $title,
                    'PHP'        => $php,
                    'TYPO3'      => $typo3,
                    "LastUpdate" => Carbon::make($test)->format('d.m.Y h:m'),
                ];
        }
        return $result;
    }
}