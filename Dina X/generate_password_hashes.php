<?php
$passwords = ['0000', '1111'];

foreach ($passwords as $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Password: $password\nHash: $hash\n\n";
}
?>
