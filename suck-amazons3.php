<?php

namespace Foodgeeks;
require_once('./vendor/autoload.php');
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/** 
 * After the recipe has been sucked in
 * 1. Download image into a tmp directory
 * 2. Turn the image into a .jpg
 * 3. Rename the image {recipe_id}.jpg
 * 4. Upload to amazon s3 in the /foodgeeks/recipes/publishers bucket
 */

const NEW_FILE_DIR = "/tmp/";
const S3_KEY = '';
const S3_SECRET = '';
	
// Download the image into a tmp directory and sve
function save_image($photo_url, $recipe_id) {
    // New file
    $new_file_name = $recipe_id . ".jpg";
    $new_file_full_name = NEW_FILE_DIR . $new_file_name;
    
    if ($content = file_get_contents($photo_url)) {
        $fp = fopen($new_file_full_name, "w");
        fwrite($fp, $content);
        fclose($fp);    
        
        echo "\r\n\r\nDownloaded file " . $new_file_full_name . "\r\n\r\n";    
        return $new_file_name; // {recipe_id}.jpg
    }
    return false;
}

// Convert the downloaded file to jpeg if it ain't already
function convert_image_to_jpeg($file_name) {
    $filepath = NEW_FILE_DIR . $file_name;
    $type = exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize() 
    $allowedTypes = array( 
        1, // [] gif 
        2, // [] jpg 
        3, // [] png 
    ); 
    if (!in_array($type, $allowedTypes)) { 
        return false; 
    } 
    switch ($type) { 
        case 1 : 
            $im = imageCreateFromGif($filepath); 
        break; 
        case 2 : 
            // $im = imageCreateFromJpeg($filepath); // SKIP - already Jpeg
        break; 
        case 3 : 
            $im = imageCreateFromPng($filepath); 
        break; 
        case 6 : 
            // $im = imageCreateFromBmp($filepath); // SKIP - not a valid method it seems
        break; 
    }    
    if (isset($im) && !empty($im)) {
        imagejpeg($im, $filepath, 80); // Save image at file path at 80% resolution
    }
}

// Upload the new image file to S3
function upload_to_s3($file_name) {
    if (isset($file_name) && !empty($file_name)) {
        $file = NEW_FILE_DIR . $file_name;
        
        // Instantiate an S3 client
        $s3 = S3Client::factory(array(
            'credentials' => array(
                'key'    => S3_KEY,
                'secret' => S3_SECRET,
            )
        ));

        // Upload a publicly accessible file. The file size, file type, and MD5 hash are automatically calculated by the SDK.
        try {
            $s3->putObject(array(
                'Bucket' => '/foodgeeks/recipes/publishers',
                'Key'    => $file_name,
                'Body'   => fopen($file, 'r'),
                'ACL'    => 'public-read',
            ));
            echo "\r\n\r\n Recipe photo uploaded to amazon s3.";
            return true;
        } catch (S3Exception $e) {
            echo "\r\n\r\n There was an error uploading the file. \r\n\r\n";
        }        
    }
    return false;
}

// the recipe image is set, let's save this in the db
function fetch_and_save_photo($recipe) {
    if (isset($recipe['id']) && !empty($recipe['id']) && isset($recipe['photo_url']) && !empty($recipe['photo_url'])) {
        $file_name = save_image($recipe['photo_url'], $recipe['id']);
        convert_image_to_jpeg($file_name);
        upload_to_s3($file_name);
        return true;
    } else {
        echo "\r\n\r\n No recipe photo. Not uploading to Amazon S3. \r\n\r\n";
    }
    return false;
}
