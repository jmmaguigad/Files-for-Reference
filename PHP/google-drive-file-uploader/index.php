<?php
if (isset($_POST['submit'])) {

    /**
     * Define all needed variables
     */

    // Where the temporary file will be stored
    $temp_folder = 'temp';

    // Extension of the application
    $ext_name = 'pdf';

    // Auth configuration, this is the json file that was downloaded through the Google Cloud Console
    $auth_config = '<SERVICE JSON>';

    // Scope of the Google Drive API
    $scope = 'https://www.googleapis.com/auth/drive';

    // ID of the Google Folder Shared
    $google_folder_id = '<GOOGLE FOLDER ID>';
    
    // Sanitized POST variables
    $province = (isset($_POST['province'])) ? trim(html_entity_decode($_POST['province'])) : '' ;
    $municipality = (isset($_POST['municipality'])) ? trim(html_entity_decode($_POST['municipality'])) : '' ;
    $firstname = (isset($_POST['firstname'])) ? trim(htmlentities($_POST['firstname'])) : '' ;
    $lastname = (isset($_POST['lastname'])) ? trim(htmlentities($_POST['lastname'])) : '' ;
    $extname = (isset($_POST['extname'])) ? trim(htmlentities($_POST['extname'])) : '' ;

    /**
     * Upload the file from the temporary folder in the server first
     * Rename the uploaded file by following this filenaming: Province_Municipality_[Firstname_Lastname_Extensionname].pdf
     */

    // Replace all instance of space in the file name and make it lower case 
    $filename = str_replace(' ','',strtolower($province.$municipality.'_'.$firstname.$lastname.$extname));
    
    // Get the uploaded file type and throw exception if not pdf
    $temp = explode(".", $_FILES["file"]["name"]);
    $x_file_name = end($temp);
    if ($x_file_name != 'pdf') {
        throw new InvalidArgumentException("Invalid File Type");
    }

    // New file name
    $newfilename = $filename.'.'.$ext_name;

    // Upload file to the temp folder
    move_uploaded_file($_FILES["file"]["tmp_name"], "temp/" . $newfilename);

    /**
     * Upload the file from the temporary folder to the google drive then delete it to the temp folder after
     */
    require 'vendor/autoload.php';

    $fileforupload = 'temp/'.$newfilename;

    $client = new Google_Client();
    $client->setAuthConfig($auth_config);
    $client->addScope($scope);

    $service = new Google_Service_Drive($client);

    //Insert a file
    $fileMetadata = new Google_Service_Drive_DriveFile(
        array(
            'name' => $newfilename,
            'parents' => array($google_folder_id)
        )
    );

    try {
        $content = file_get_contents($fileforupload);
        $file = $service->files->create(
            $fileMetadata,
            array(
                'data' => $content,
                'mimeType' => 'application/pdf',
                'uploadType' => 'multipart',
                'fields' => 'id'
            )
        );
        // If successfully uploaded then it should have a file id
        if ($file->id) unlink($fileforupload);
    } catch (Exception $e) {
        echo 'An error ocurred : ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form method="POST" action="" enctype='multipart/form-data'>
        <input type="text" name="province" id="province" placeholder="province">
        <input type="text" name="municipality" id="municipality" placeholder="municipality">
        <input type="text" name="firstname" id="firstname" placeholder="firstname">
        <input type="text" name="lastname" id="lastname" placeholder="lastname">
        <input type="text" name="extname" id="extname" placeholder="extname">
        <input type="file" name="file" id="file">
        <input type="submit" value="Submit" name="submit">
    </form>
</body>

</html>