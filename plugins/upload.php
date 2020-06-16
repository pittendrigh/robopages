<?php
namespace Verot\Upload;

error_reporting(E_ALL);

include('class.upload.php');

// set variables
$dir_dest = (isset($_GET['dir']) ? $_GET['dir'] : '/var/www/html/temp/');
$dir_pics = (isset($_GET['pics']) ? $_GET['pics'] : $dir_dest);

$log = '';

    $handle = new Upload($_FILES['my_field']);

    // then we check if the file has been uploaded properly
    // in its *temporary* location in the server (often, it is /tmp)
    if ($handle->uploaded) 
    {
        $handle->process($dir_dest);

        // we check if everything went OK
        if ($handle->processed) {
            // everything was fine !
            echo '<p class="result">';
            echo '  <b>File uploaded with success</b><br />';
            echo '  File: <a href="'.$dir_pics.'/' . $handle->file_dst_name . '">' . $handle->file_dst_name . '</a>';
            echo '   (' . round(filesize($handle->file_dst_pathname)/256)/4 . 'KB)';
            echo '</p>';
        } 
        else 
        {
            // one error occured
            echo '<p class="result">';
            echo '  <b>File not uploaded to the wanted location</b><br />';
            echo '  Error: ' . $handle->error . '';
            echo '</p>';
        }

        // we delete the temporary files
        $handle->clean();

    } 
    else 
    {
        // if we're here, the upload file failed for some reasons
        // i.e. the server didn't receive the file
        echo '<p class="result">';
        echo '  <b>File not uploaded on the server</b><br />';
        echo '  Error: ' . $handle->error . '';
        echo '</p>';
    }

    $log .= $handle->log . '<br />';



if ($log) echo '<pre>' . $log . '</pre>';

?>

