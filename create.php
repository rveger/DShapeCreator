<?
/*
DShapeCreator gives the user an easy way to create Dia shapes.
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

Notes: In php.ini max_file_uploads should be set to the max amount of images you would like to upload. It may be a smart idea to increase the max_file_size to something like 256M
*/

// Sanity checks
if( !isset($_POST['shapename']) || !isset($_FILES['upload']['name'])
	|| !is_array($_FILES['upload']['name']) || (0 == count($_FILES['upload']['name'])) ||
	('' == $_POST['shapename']) || !extension_loaded('zip')) {
	header('Location: index.htm');
	?>
	<!DOCTYPE html>
	<html>
	    <head>
		<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
		    <title>
		        DShapeCreator
		    </title>
	    </head>

	    <body>
		<p>Missing data. Please fill the form completely.</p>
	    </body>
	</html><?
	exit();
}

// Shapename variable from index.htm
$shapename = basename($_POST['shapename']);

// Creates directories for Dia Shape
$tmp = 'tmp';
// OpenShift support
if(isset($_ENV['OPENSHIFT_TMP_DIR']))
	$tmp = $_ENV['OPENSHIFT_TMP_DIR'];
else
	@mkdir($tmp, 0777);
$job = tempnam($tmp, 'job');
unlink($job);
mkdir($job);
mkdir($job.'/img', 0777);
mkdir($job.'/'.$shapename, 0777);
mkdir($job.'/'.$shapename.'/sheets', 0777);
mkdir($job.'/'.$shapename.'/shapes', 0777);
mkdir($job.'/'.$shapename.'/shapes/'.$shapename, 0777);

// Creates the beginning of the sheet file
$fp = fopen($job.'/'.$shapename.'/sheets/'.$shapename.'.sheet', 'w');
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

for ($i=0; $i < $num_files; $i++) {
	if (@is_uploaded_file($_FILES['upload']['tmp_name'][$i])) {
		move_uploaded_file($_FILES['upload']['tmp_name'][$i], 
			$job.'/img/'.basename($_FILES['upload']['name'][$i]));
        	$str = basename($_FILES['upload']['name'][$i]);
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		if((substr($str, -4) != '.jpg')||('image/jpeg' != finfo_file($finfo, $job.'/img/'.basename($_FILES['upload']['name'][$i]))))
			die('No .jpg: '.finfo_file($finfo, $job.'/img/'.basename($_FILES['upload']['name'][$i])));
            	$len = strlen($str);
	    	$random = rand(10000000, 99999999);
            	$filenameNotRandom = substr($str,0,($len-4));
		$filename = $random.$filenameNotRandom;

		// Variables for names
		$extension = '.jpg';
		$thumbname = 'S'.$filename;
		$shapefilename = $filename.'.shape';

		// Get the new sizes
		list($width, $height) = getimagesize($job.'/img/'.$filenameNotRandom.$extension);

		// Create and load
		$source = imagecreatefromjpeg($job.'/img/'.$filenameNotRandom.$extension);
		$thumb = imagecreatetruecolor(22, 22);

		// This code resizes the image
		imagecopyresized($thumb, $source, 0, 0, 0, 0, 22, 22, $width, $height);

		// Outputs the images and thumbnails
		imagejpeg($source, $job.'/'.$shapename.'/shapes/'.$shapename.'/'.$filename.$extension);
		imagejpeg($thumb, $job.'/'.$shapename.'/shapes/'.$shapename.'/'.$thumbname.$extension);

		// Destroy images to free up some memory
		imagedestroy($source);
		imagedestroy($thumb);

		// Create Shape files
		$fp = fopen($job.'/'.$shapename.'/shapes/'.$shapename.'/'.$shapefilename, 'w');
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
		$fp = fopen($job.'/'.$shapename.'/sheets/'.$shapename.'.sheet', 'a+');
		fwrite($fp, '<object name="'.$filename.'">
		<description>'.$filename.'</description>
		</object>
		');
		fclose($fp);
	}
}

// Ends the sheet file
$fp = fopen($job.'/'.$shapename.'/sheets/'.$shapename.'.sheet', 'a+');
fwrite($fp, '</contents>
</sheet>');
fclose($fp);

// Creates the zip file
$zip = new ZipArchive();
if (!$zip->open($job.'.zip', ZIPARCHIVE::CREATE))
	die('Could not open job.zip');

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($job),
	RecursiveIteratorIterator::SELF_FIRST);

foreach($files as $file) {
	$file = str_replace('\\', '/', realpath($file));
	if(true === is_dir($file)) {
        	$zip->addEmptyDir(str_replace($job . '/', '', $file . '/'));
	}
        elseif(true === is_file($file)) {
		$zip->addFromString(str_replace($job . '/', '', $file), file_get_contents($file));
		unlink($file);
	}
}
$zip->close();

// Content-Type for zip creation
header('Content-disposition: attachment; filename="'.addslashes($shapename).'.zip"');
header('Content-Type: application/zip');
// Uploads the zip file to the user
readfile($job.'.zip');
unlink($job.'.zip');
// Delete directories for Dia Shape
rmdir($job.'/'.$shapename.'/shapes/'.$shapename);
rmdir($job.'/'.$shapename.'/sheets');
rmdir($job.'/'.$shapename.'/shapes');
rmdir($job.'/'.$shapename);
rmdir($job.'/img');
rmdir($job);
