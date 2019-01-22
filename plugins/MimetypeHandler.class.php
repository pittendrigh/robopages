<?php
@session_start();

/**
 * Mimetype handler
 *
 * @author Tom Reitsma <treitsma@rse.nl>
 * @version 0.5
 */
Class MimetypeHandler
{
    /**
     * @var array $mimeTypes
     */
    private $mimeTypes = array();
    /**
     * @var string $mime_ini_location
     * @desc The location of the ini file that contains the mimetypes
     */
    private $mime_ini_location = "conf/mime_types.ini";

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->mimeTypes = parse_ini_file($this->mime_ini_location, FALSE);
    }

    /**
     * Loads another mime type file
     * 
     * @var string $mime_ini_location
     * @return void
     */
    public function loadIni($mime_ini_location)
    {
        if (!file_exists($mime_ini_location))
        {
            throw new Exception("File {$mime_ini_location} not found.");
        }

        $newEntries = parse_ini_file($mime_ini_location, FALSE);

        foreach ($newEntries as $key => $value)
        {
            $this->mimeTypes[$key] = $value;
        }
    }

    /**
     * Gets the mimetype of the string given in the constructor, or the string given as the first parameter
     *
     * @param string $filepath
     * @return string mimetype
     */
    public function getMimetype($filepath = FALSE)
    {

        if (count($this->mimeTypes) == 0)
        {
            $this->__construct();
        }

        if ($filepath == FALSE || !is_string($filepath))
        {
            throw new Exception("No input specified.");
            return "unknown";
        }

        if (preg_match(":^\#.*lbl:", $filepath))
            return "lbl";
        if (substr($filepath, 0, 7) == 'http://')
            return "url";
        if (is_dir($filepath))
            return "dir";


        $ext = $this->getSuffix($filepath);
        if ($ext != null && $this->mimetypeExists($ext))
        {
            $rawvalue = $this->mimeTypes[$ext];

            $secondExplosion = explode(":", $rawvalue);
            $ret = $secondExplosion[0];
            return $ret;
        }
    }

    public function getLinkTargetType($filepath = FALSE)
    {
        // this needs concept cleanup badly
        $ret = 'unknown';
        if (count($this->mimeTypes) == 0)
        {
            $this->__construct();
        }

        //echo "mimer getLinkType filepath: ", $filepath, "<br/>";
        if ($filepath == FALSE || !is_string($filepath))
        {
            return $ret;
        }

        /*
          if (preg_match(":^\#.*lbl:", $filepath))
          {
          return "lbl";
          }
         */
        //       else 
        if (substr($filepath, 0, 7) == 'http://')
            return "url";
        else if (@is_dir(preg_replace(":.*robopage=:", '', $filepath)))
        {
            return "dir";
        }
        else if (!strstr(basename($filepath), '.'))
            return "unknown";

        $exploded = explode(".", $filepath);
        $ext = $exploded[count($exploded) - 1];
        if (!$this->mimetypeExists($ext))
        {
            return "unknown";
        }
        else
        {
            $rawvalue = $this->mimeTypes[$ext];
            if (strstr($rawvalue, "image"))
                return "image";

            //$ret = "link";
            $ret = $rawvalue;
            if (strstr($rawvalue, ":"))
            {

                $secondExplosion = explode(":", $rawvalue);
                $ret = $secondExplosion[1];
            }
            //echo $filepath, " ", $ext, " ", $ret, "<br/>";
            return $ret;
        }
    }

    /**
     * Adds a mimetype to the array
     * 
     * @param string $ext 		Mime extension
     * @param string $fileType	Filetype (Example: text/plain)
     * @param bool $writeToFile	Determines wether to add the mimetype to the ini file
     * @return bool
     */
    public function addMimetype($ext, $fileType, $writeToFile = FALSE)
    {
        if ($writeToFile == TRUE)
        {
            if (!$this->mimetypeExists($ext))
            {
                $fp = fopen($this->mime_ini_location, "a+");
                fwrite($fp, sprintf("\n\n; Custom mimetype\n%s = %s", $ext, $fileType));
                fclose($fp);
            }
        }

        $this->mimeTypes[$ext] = $fileType;

        return TRUE;
    }

    /**
     * Checks wether a mimetype exists
     * 
     * @param string $ext	Extension
     * @return bool
     */
    public function mimetypeExists($ext)
    {
        return isset($this->mimeTypes[$ext]) ? TRUE : FALSE;
    }

    /**
     * Gets all the subly loaded mimetypes
     * 
     * @return array mimetypes
     */
    public function getMimetypes()
    {
        return $this->mimeTypes;
    }

    public function getSuffix($file)
    {
        $suffix = '';
        $dotpos = strrpos($file, '.');
        if ($dotpos)
        {
            $suffix = substr($file, $dotpos + 1);
        }
        return $suffix;
    }

}
?>
