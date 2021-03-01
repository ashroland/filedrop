<?php

// Place secrets in separate file
include_once("secrets.php");

// or declare here:
// $reCaptchaSite = "ABC123";
// $reCaptchaSecret = "ABC123";

function renderLogin() {
    // recaptcha v3 reqs
    echo '<script src="https://www.google.com/recaptcha/api.js"></script>';
    echo '<script> function onSubmit(token) { document.getElementById("userlogin").submit(); } </script>';
    
    // build form
    echo '<div class="row">';
    echo '<div class="twelve columns">';
    echo '<p class="u-full-width" style="text-align:center">this file drop operates on the honor system. please tell me who u are.</p>';
    echo '</div>';

    echo '<div class="six columns offset-by-three" style="text-align:center">';
    echo '<form method="post" action="index.php" id="userlogin" class="u-full-width">';
    echo '<input type="text" placeholder="ex., Jane Doe" name="name" class="two-thirds column" />';

    // Embed code for recaptcha
    echo '<button class="g-recaptcha one-third column" data-sitekey="' . $GLOBALS["reCaptchaSite"] . '" data-callback=\'onSubmit\' data-action=\'submit\'>Login</button>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
}

function renderPage() {
    // left panel
    echo '<div class="row">';
    echo '<div class="four columns">';

    // logout area
    echo '<div class="u-full-width">';
    echo '<form method="post" action="index.php" class="u-full-width" style="padding-top: 12px">';
    echo '<input type="hidden" name="logout" value="true" />';
    echo '<input type="submit" class="u-full-width" value="logout" />';
    echo '<p class="u-full-width" style="text-align: justify left;"></p>';
    echo '</form>';
    echo '</div>';

    // file drop area
    echo '<div class="u-full-width" id="drop-area" style="margin-bottom: 35px">';
    echo '<form class="uploadform u-full-width" action="upload.php" method="post">';
    echo '<label class="button u-full-width button-primary" for="fileElem">Select file(s)</label>';
    echo '<p class="u-full-width"><small>tap above or drop files here to upload. don\'t upload abusive shit. assume all uploaded files are public and unencrypted. again: use the honor system.</small></p>';
    echo '<input type="file" class="u-full-width" id="fileElem" multiple onchange="handleFiles(this.files)">';
    echo '<progress id="progress-bar" class="u-full-width" max=100 value=0></progress>';
    echo '</form>';
    echo '</div>';

    // end left panel
    echo '</div>';
    
    // right panel
    echo '<div class="eight columns">';
    renderFileList();
    echo '</div>';
}

function renderFileList() {

    echo '<table class="u-full-width">';
    echo '<tbody>';

    foreach (glob("files/*.txt") as $filename) {
        echo '<tr>';
        echo '<form action="index.php" method="post">';
        $filehandler = fopen($filename, "r");
        $filedata = unserialize(fgets($filehandler));
        fclose($filehandler);

        $filedata['friendlyname'] = basename($filedata['filename']);
        $filedata['friendlyname'] = explode("_", $filedata['friendlyname'], 2)[1];

        echo '<th>';
        echo '<a href="'. $filedata['filename'] . '" class="button button-primary u-full-width">';
        echo $filedata['friendlyname'];
        echo '</a>';
        echo '</th>';

        echo '<th>';

        echo '<input type="hidden" name="delete" value="' . $filedata['filename'] . '" />';
        echo '<input type="submit" value="del" style="margin-top:5px" />';
        echo '</form>';
        echo '</th>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}

function init() {

    // Main logic. Logged in? Not logged in?

    if (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
    }


    if ($_SESSION['verified']){
        // Verified session means we can upload and delete things

        if (isset($_FILES['file'])) {
            // Write files to disk
            
            $uuid = uniqid();
            $tmp_fn =  "files/" . $uuid . "_" . $_FILES['file']['name'];
            
            $fileobj = array(
                "user" => $_SESSION['name'],
                "uuid" => $uuid,
                "timedate" => date("Ymd"),
                "filename" => $tmp_fn,
            );
    
            move_uploaded_file($_FILES['file']['tmp_name'], $fileobj['filename']);
    
            // write metadata file
            $stringData = serialize($fileobj);
            file_put_contents( $tmp_fn . ".txt", $stringData);
        }
        
        if (isset($_POST['delete'])) {
            unlink($_POST['delete']);
            unlink($_POST['delete'] . ".txt");
        }

        renderPage();
    } else {
        if (isset($_POST['name'])) {
            // Did user pass captcha?
            if (isset($_POST['g-recaptcha-response'])) {
                $reCaptchaResponse = $_POST['g-recaptcha-response'];
    
                $url = 'https://www.google.com/recaptcha/api/siteverify';
                $data = array(
                    'secret' => $GLOBALS["reCaptchaSecret"], 
                    'response' => $reCaptchaResponse
                );
                
                $options = array(
                    'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => http_build_query($data)
                    )
                );
                $context  = stream_context_create($options);
                $result = file_get_contents($url, false, $context);
                $result = json_decode($result, $assoc = false);
                if ($result === FALSE) { /* Handle error */ };
    
                if ($result->success) {
                    // assign session id
                    $_SESSION["name"] = $_POST['name'];
                    $_SESSION["verified"] = true;
    
                    // render page
                    renderPage();
                } else {
                    // take back to login
                    $_SESSION["verified"] = false;
                    echo '<p style="color:#F00; font-weight:bold; text-align:center" class="u-full-width">please retry captcha. redirecting in 2s</p>';
                    echo '<meta http-equiv="refresh" content="5;url=/" />';
                }
            }
        }
    
        else {
            renderLogin();
        }
    }
}

?>

<?php session_start() ?>
<!DOCTYPE html>
<html lang="en">
<head>

  <!-- Basic Page Needs   -->
  <meta charset="utf-8">
  <title>filedrop</title>
  <meta name="description" content="a one-file semi-public psedonymous file manager">
  <meta name="author" content="ashley ona bott">

  <!-- Mobile Specific Metas   -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- FONT   -->
  <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

  <!-- CSS  -->
  <link rel="stylesheet" href="css/normalize.css">
  <link rel="stylesheet" href="css/skeleton.css">

  <!-- Favicon  -->
  <link rel="icon" type="image/png" href="images/favicon.png">
  
  <style type="text/css">
    #drop-area {
        border: 2px dashed #ccc;
        border-radius: 20px;
        padding: 20px;
    }
    #drop-area.highlight {
        border-color: purple;
    }
    #fileElem {
        display: none;
    }
  </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="twelve columns">
                <center><h2 style="margin-top: 15%">filedrop.1460estro.work</h2></center>
            </div>
        </div>
        <?php init(); ?>
    </div>

    <script language="javascript">
  
  // ************************ Drag and drop ***************** //
    let dropArea = document.getElementById("drop-area");
    let totalUploads = 0;
    let uploadSuccess = 0;

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false)
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });

    // Handle dropped files
    dropArea.addEventListener('drop', handleDrop, false);

    function preventDefaults (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight(e) {
        dropArea.classList.add('highlight');
    }

    function unhighlight(e) {
        dropArea.classList.remove('active');
    }

    function handleDrop(e) {
        var dt = e.dataTransfer;
        var files = dt.files;

        handleFiles(files);
    }

    let uploadProgress = [];
    let progressBar = document.getElementById('progress-bar');

    function initializeProgress(numFiles) {
    progressBar.value = 0;
    uploadProgress = [];

    for(let i = numFiles; i > 0; i--) {
        uploadProgress.push(0);
    }
    }

    function updateProgress(fileNumber, percent) {
        uploadProgress[fileNumber] = percent;
        let total = uploadProgress.reduce((tot, curr) => tot + curr, 0) / uploadProgress.length;
        console.debug('update', fileNumber, percent, total);
        progressBar.value = total;

        if (total == 100) {
            location.reload();
        }
    }

    function handleFiles(files) {
        files = [...files];
        initializeProgress(files.length);
        files.forEach(uploadFile);
        files.forEach(trackFile);
    }

    function trackFile(file, i) {
        totalUploads++;
    }

    function uploadFile(file, i) {
    var url = 'index.php';
    var xhr = new XMLHttpRequest();
    var formData = new FormData();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    // Update progress (can be used to show progress indicator)
    xhr.upload.addEventListener("progress", function(e) {
        updateProgress(i, (e.loaded * 100.0 / e.total) || 100);
    });

    xhr.addEventListener('readystatechange', function(e) {
        if (xhr.readyState == 4 && xhr.status == 200) {
            updateProgress(i, 100);
            uploadSuccess++;

            if (uploadSuccess == totalUploads) {
                location.reload();
            }
        }
        else if (xhr.readyState == 4 && xhr.status != 200) {
        // Error. Inform the user
        }
    });

  formData.append('file', file);
  xhr.send(formData);
}
  
  </script>

</body>