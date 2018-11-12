<?php

namespace setup {
if (!file_exists('../.data'))
    mkdir('../.data');

if (!file_exists('../.data/auth')):
    if (
        !array_key_exists("pass1", $_POST)
        || !array_key_exists("pass2", $_POST)
    ) null;
    elseif ($_POST['pass1'] !== $_POST['pass2']) $error = "Hesla se neshodují";
    elseif (strlen($_POST['pass1']) < 8) $error = "Heslo je příliš krátké";
    else {
        $cost = 8;
        do {
            $start = microtime(true);
            password_hash("test", PASSWORD_BCRYPT, ["cost" => ++$cost]);
        } while ((microtime(true) - $start) < 0.1);

        $auth = password_hash($_POST['pass1'], PASSWORD_BCRYPT, ["cost" => $cost]);
        file_put_contents('../.data/auth', $auth);
        goto setup_end;
    }
?>
<h1>První spuštění admin konzole</h1>
<h2>Zde nastavte heslo správce</h2>
<?=is_string(@$error)?"<h3>$error</h3>":""?>
<form method="post">
    <input type="password" placeholder="Heslo" name="pass1" /><br />
    <input type="password" placeholder="Heslo zvovu" name="pass2" /><br />
    <input type="submit" value="Nastavit" />
</form>
<?php
exit;
endif;
setup_end:;
}

namespace auth {
session_start();
if (@$_SESSION['authed'] !== true):
    if (array_key_exists('pass', $_POST)) {
        if (password_verify($_POST['pass'], file_get_contents('../.data/auth'))) {
            $_SESSION['authed'] = true;
            goto auth_end;
        } else $error = true;
    }
?>
<h1>Správa zprávy</h1>
<?=@$error?"<h3>Chyba přihlášení</h3>":""?>
<form method="post">
<input type="password" placeholder="Heslo" name="pass" /><br />
<input type="submit" value="Přihlásit" />
</form>
<?php
exit;
endif;
auth_end:;
}

namespace edit {
    echo "hola";
}