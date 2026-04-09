<?php
session_start();
session_destroy();
header("Location: /inventory_system/pages/home.php?logout=1");
exit();