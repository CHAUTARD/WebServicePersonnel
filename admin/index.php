<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (isLoggedIn()) {
    header('Location: personnel.php');
} else {
    header('Location: login.php');
}
exit;
