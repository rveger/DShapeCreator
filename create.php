<?
/*
DShapeCreator 0.01 gives the user an easy way to create Dia shapes.
Copyright (C) Ruben Veger

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
Enjoy! Ruben Veger, rveger@gmail.com, Fultsemaheerd 2 9736CN Groningen, The Netherlands

Notes: In php.ini max_file_uploads should be set to the max amount of images you would like to upload. It may be a smart idea to increase the max_file_size to something like 512M
*/
// Turn off all error reporting
error_reporting(0);

// Shapename variable from index.htm
$shapename = $_POST['shapename'];

// Creates directories for Dia Shape
@mkdir('tmp', 0777);
@mkdir('tmp/img', 0777);
@mkdir('tmp/'.$shapename, 0777);
@mkdir('tmp/'.$shapename.'/sheets', 0777);
@mkdir('tmp/'.$shapename.'/shapes', 0777);
@mkdir('tmp/'.$shapename.'/shapes/'.$shapename, 0777);

// Creates the beginning of the sheet file
$fp = fopen('tmp/'.$shapename.'/sheets/'.$shapename.'.sheet', 'w');
fwrite($fp, '<?xml version="1.0" encoding="iso-8859-1"?>
<sheet xmlns="http://www.lysator.liu.se/~alla/dia/dia-sheet-ns">
<name>'.$shapename.'</name>
<created_by>shapecreator</created_by>
<description>shapecreator is a ... under the GPL... created by: Ruben Veger</description>
<contents>
');
fclose($fp);

/* Upload files and creates random file names, this is to ensure that
 an old Shape with the same images and image names keeps working */

$num_files = count($_FILES['upload']['name']);
for ($i=0; $i < $num_files; $i++){
    if (@is_uploaded_file($_FILES['upload']['tmp_name'][$i])){
move_uploaded_file($_FILES['upload']['tmp_name'][$i],'./tmp/img/' . basename($_FILES['upload']['name'][$i]));
            $str = basename($_FILES['upload']['name'][$i]);
            $len=strlen($str);
		    $random=rand(10000000, 99999999);
            $filenameNotRandom=substr($str,0,($len-4));
		    $filename=$random.$filenameNotRandom;

// Variables for names
$extension = '.jpg';
$thumbname = 'S'.$filename;
$shapefilename = $filename.'.shape';

// Content-Type for zip creation
header('Content-disposition: attachment; filename="'.addslashes($shapename).'.zip"');
header('Content-Type: application/zip');

// Get the new sizes
list($width, $height) = getimagesize('tmp/img/'.$filenameNotRandom.$extension);

// Create and load
$source = imagecreatefromjpeg('tmp/img/'.$filenameNotRandom.$extension);
$thumb = imagecreatetruecolor(22, 22);

// This code resizes the image
imagecopyresized($thumb, $source, 0, 0, 0, 0, 22, 22, $width, $height);

// Outputs the images and thumbnails
imagejpeg($source, 'tmp/'.$shapename.'/shapes/'.$shapename.'/'.$filename.$extension);
imagejpeg($thumb, 'tmp/'.$shapename.'/shapes/'.$shapename.'/'.$thumbname.$extension);

// Destroy images to free up some memory
imagedestroy($source);
imagedestroy($thumb);

// Create Shape files
$fp = fopen('tmp/'.$shapename.'/shapes/'.$shapename.'/'.$shapefilename, 'w');
fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>
<shape xmlns="http://www.daa.com.au/~james/dia-shape-ns" xmlns:svg="http://www.w3.org/2000/svg">
  <name>'.$filename.'</name>
  <icon>'.$thumbname.$extension.'</icon>
  <connections>
    <point x="0" y="0"/>
    <point x="0" y="6"/>
    <point x="0" y="12"/>
    <point x="0" y="18"/>
    <point x="0" y="24"/>
    <point x="6" y="24"/>
    <point x="6" y="0"/>
    <point x="12" y="24"/>
    <point x="12" y="0"/>
    <point x="18" y="24"/>
    <point x="18" y="0"/>
    <point x="24" y="24"/>
    <point x="24" y="18"/>
    <point x="24" y="12"/>
    <point x="24" y="6"/>
    <point x="24" y="0"/>
    <point x="15" y="15" main="yes"/>
  </connections>
  <svg:svg>
  <svg:image x="1" y="1" width="22" height="22" xlink:href="'.$filename.$extension.'"/>  
</svg:svg>
</shape>');
fclose($fp);

// Creates objects in the sheet file
$fp = fopen('tmp/'.$shapename.'/sheets/'.$shapename.'.sheet', 'a+');
fwrite($fp, '<object name="'.$filename.'">
<description>'.$filename.'</description>
</object>
');
fclose($fp);
    }
}

// Ends the sheet file
$fp = fopen('tmp/'.$shapename.'/sheets/'.$shapename.'.sheet', 'a+');
fwrite($fp, '</contents>
</sheet>');
fclose($fp);

// Creates the zip file
Zip('./tmp/', './'.$shapename.'.zip');

// Uploads the zip file to the user
readfile($shapename.'.zip');


/* This function creates a zip file and deletes the files in the tmp directory. 
It is from: http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php */
function Zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', realpath($file));

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                unlink($file);
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }
    return $zip->close();
}

// Delete directories for Dia Shape
rmdir('tmp/'.$shapename.'/shapes/'.$shapename);
rmdir('tmp/'.$shapename.'/sheets');
rmdir('tmp/'.$shapename.'/shapes');
rmdir('tmp/'.$shapename);
rmdir('tmp/img');
rmdir('tmp');
?>