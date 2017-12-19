<?php

@session_start();

class roboMimeTyper {

    protected $robobMimeTypesHash;

    function __construct() {
        $this->roboMimeTypesHash = array();
        $this->setupMimeTypes();
    }

    function getRoboMimeType($teststring) {
        $ret = 'unknown';

        $teststr = $teststring;


        if (strstr($teststr, '=')) {
            $pieces = explode("=", $teststr);
            $teststr = $pieces[1];
        }

        if (!strstr($teststring, $_SERVER['DOCUMENT_ROOT'])) {
            $teststr = $_SESSION['prgrmDocRoot'] . $teststr;
        }

        //echo "rm: ", $teststr, "<br/>";

        if (is_dir($teststr)) {
            $ret = 'dir';
        } else {
            $teststr = basename($teststr);
            $suffix = strtolower(staticRoboUtils::getSuffix($teststr));
            //echo "suffix: ", $suffix, "<br/>";
            if (isset($this->roboMimeTypesHash[$suffix])) {
                $ret = $this->roboMimeTypesHash[$suffix];
            }
        }

        //if($ret == "unknown")
        //echo " $ret on $teststring<br/>";
        return trim($ret);
    }

    function setupMimeTypes() {
        $path = getcwd() . '/conf/roboMimeTypes.ini';
        $mimeLines = file($path);
        $cnt = count($mimeLines);
        $roboMimeType = '';
        for ($i = 0; $i < $cnt; $i++) {
            $line = $mimeLines[$i];

            // bracketed lines set the current 
            // [video] 
            // followed by the 'mov' sets the roboMimeType for mov as video
            if (strstr($line, '[')) {
                $roboMimeType = preg_replace('/\[|\]/', '', $line);
                continue;
            }
            $suffix = trim($line);
            $this->roboMimeTypesHash[$suffix] = $roboMimeType;
            //echo "$suffix == $roboMimeType<br/>";
        }
    }

}

?>
