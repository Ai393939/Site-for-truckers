<?php
session_start();
session_destroy();
header('Location: /LoginBlockchain.html');
exit;