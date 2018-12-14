<?php
define('DATA_PATH', __DIR__ . '/../.data');
define('UPLOAD_PATH', __DIR__ . '/../img');
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'bmp']);
define('MAX_FILE_SIZE', 500000);

session_start();

if (!array_key_exists('logged', $_SESSION))
    $_SESSION['logged'] = 0;

if (!file_exists(DATA_PATH))
    mkdir(DATA_PATH);

if (!file_exists(UPLOAD_PATH))
    mkdir(UPLOAD_PATH);

if (!file_exists(DATA_PATH . '/shadow')) {
    $str = "admin:" . password_hash("admin", PASSWORD_DEFAULT);
    file_put_contents(DATA_PATH . '/shadow', $str);
}

if (validateLogin()) {
    if (array_key_exists('logout', $_GET)) {
        session_unset();
        header("Location: .");
        flush();
        exit;
    }
    if (array_key_exists('pswchg', $_GET)) {
        if (!changePass()) {
            renderPswChange();
        } else {
            header("Location: .");
            flush();
        }
    } else if (array_key_exists('delete', $_GET)) {
        if (is_string($text = deleteImage()))
            renderDash(prepareImages(), $text);
        else {
            header("Location: .");
            flush();
        }
    } else if (array_key_exists('upload', $_GET)) {
        if (is_string($text = manageUpload()))
            renderDash(prepareImages(), $text);
        else {
            header("Location: .");
            flush();
        }
    } else {
        renderDash(prepareImages());
    }
} else {
    if (!empty($_GET)) {
        header("Location: .");
        flush();
    } else
        renderLogin();
}
exit;

function deleteImage() {
    if (array_key_exists('img', $_GET)) {
        $name = basename(base64_decode($_GET['img']));
        if (!file_exists(UPLOAD_PATH . '/' . $name))
            return "Image not found.";
        unlink(UPLOAD_PATH . '/' . $name);
        return true;
    }
    return false;
}

function prepareImages() {
    $images = [];
    foreach (scandir(UPLOAD_PATH) as $file) {
        if ($file == '.' || $file == '..') continue;
        if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ALLOWED_FILE_TYPES)) continue;
        array_push($images, $file);
    }
    return $images;
}

function manageUpload() {
    $target_file = UPLOAD_PATH . '/' . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if (!in_array($imageFileType, ALLOWED_FILE_TYPES))
        return "File does not have correct format";
    
    if (isset($_POST["submit"])) {
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check === false)
            return "File is not an image.";
    }
    
    if ($_FILES["image"]["size"] > MAX_FILE_SIZE)
        return "File is too large.";
    
    $tmpName = tempnam(UPLOAD_PATH, "");
    $newName = pathinfo($tmpName, PATHINFO_FILENAME) . '.' . $imageFileType;
    unlink($tmpName);
    if (move_uploaded_file($_FILES["image"]["tmp_name"], UPLOAD_PATH . '/' . $newName))
        return true;
    else
        return "Error while uploading file.";
}

function changePass() {
    if (
        !empty($_POST) &&
        array_key_exists("pswod", $_POST) &&
        array_key_exists("pswn1", $_POST) &&
        array_key_exists("pswn2", $_POST)
    ) {
        if ($_POST['pswn1'] != $_POST['pswn1'] || strlen($_POST['pswn1']) < 7)
            return false;
        
        $lines = explode(PHP_EOL, file_get_contents(DATA_PATH . '/shadow'));
        foreach ($lines as $id => $line) {
            $data = explode(":", $line);
            if ($data[0] == $_SESSION['uname']) {
                if (password_verify($_POST['pswod'], $data[1])) {
                    $data[1] = password_hash($_POST['pswn1'], PASSWORD_DEFAULT);
                    $lines[$id] = implode(':', $data);
                    $file = implode(PHP_EOL, $lines);
                    file_put_contents(DATA_PATH . '/shadow', $file);
                    return true;
                }
                return false;
            }
        }
        return false;
    }
}

function validateLogin() {
    if (!empty($_POST) && array_key_exists("uname", $_POST) && array_key_exists("passw", $_POST)) {
        $lines = explode(PHP_EOL, file_get_contents(DATA_PATH . '/shadow'));
        foreach ($lines as $line) {
            $data = explode(":", $line);
            if ($data[0] == $_POST['uname'] && password_verify($_POST['passw'], $data[1])) {
                $_SESSION['logged'] = time();
                $_SESSION['uname'] = $_POST['uname'];
                return true;
            } else
                $_SESSION['logged'] = 0;
        }
        sleep(2);
        return false;
    } else if ($_SESSION['logged'] > time() - 5 * 60) {
        $_SESSION['logged'] = time();
        return true;
    } else
        return false;
}

function renderLogin() { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login | Timetable Dashboard</title>
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./styles/formstyle.css" />
</head>
<body>
<div class="login-page">
  <div class="form">
    <form class="login-form" method="post">
      <input name="uname" type="text" placeholder="username"/>
      <input name="passw" type="password" placeholder="password"/>
      <button>login</button>
    </form>
  </div>
</div>
</body>
</html>
<?php }

function renderHeader($name, $stylePrefix = "") { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?=$name?> | Timetable Dashboard</title>
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./styles/<?=$stylePrefix?>style.css" />
    <link rel="stylesheet" href="./styles/style.css" />
</head>
<body>
<nav>
    <ul>
        <li><a href=".">Home</a></li>
        <li><a href="?pswchg">Change Password</a></li>
        <li><a href="?logout">Logout</a></li>
    </ul>
</nav>
<?php }

function renderFooter() { ?>
</body>
</html>
<?php }

function renderPswChange() { ?>
<?=renderHeader("Password Change", "form")?>
<div class="login-page">
  <div class="form">
    <form class="login-form" method="post">
      <input name="pswod" type="password" placeholder="Old password"/>
      <input name="pswn1" type="password" placeholder="New password (min 8 chars long)"/>
      <input name="pswn2" type="password" placeholder="New password again"/>
      <button>change password</button>
    </form>
  </div>
</div>
<?=renderFooter()?>
<?php }


function renderDash($images, $text = "") { ?>
<?=renderHeader("Dashboard", "dash")?>
<?php if (strlen($text)):?>
<h4 class="message"><?=$text?></h4>
<?php endif;?>
<div class="upload">
<form method="post" enctype="multipart/form-data" action="?upload">
    <h4>Here you can upload images that will be periodically displayed on TV alongside timetable changes</h4>
    <input type="file" name="image">
    <input type="submit">
</form>
</div>
<div class="images">
    <ul>
        <?php foreach ($images as $image):?>
        <li>
        <img src="../img/<?=$image?>" />
        <a href="?delete&img=<?=base64_encode($image)?>">X</a>
        </li>
        <?php endforeach;?>
    </ul>
</div>
<?=renderFooter()?>
<?php }