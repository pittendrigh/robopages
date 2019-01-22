<?php

class imgResizer
{

    function doIt($inBasename, $outBasename, $longestSide)
    {
        $source_properties = getimagesize($inBasename);
        $image_type = $source_properties[2];
        if ($image_type == IMAGETYPE_JPEG)
        {
            $image_resource_id = imagecreatefromjpeg($inBasename);
        }
        elseif ($image_type == IMAGETYPE_GIF)
        {
            $image_resource_id = imagecreatefromgif($inBasename);
        }
        elseif ($image_type == IMAGETYPE_PNG)
        {
            $image_resource_id = imagecreatefrompng($inBasename);
        }
        $x = imagesx($image_resource_id);
        $y = imagesy($image_resource_id);

        $sideComparitor = $x;
        if ($y > $x)
            $sideComparitor = $y;

        $knockDownParameter = $longestSide / $sideComparitor;

        $target_width = round($x * $knockDownParameter);
        $target_height = round($y * $knockDownParameter);

        $knockedImage = imagecreatetruecolor($target_width, $target_height);

        imagecopyresampled($knockedImage, $image_resource_id, 0, 0, 0, 0, $target_width, $target_height, $source_properties[0], $source_properties[1]);

        imagejpeg($knockedImage, $outBasename);
    }

}
?>
