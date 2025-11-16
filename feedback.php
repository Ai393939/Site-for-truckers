<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

$filename = 'feedback.json';

if (file_exists($filename)) {
    $file = file_get_contents('feedback.json');
} else {
    $file = fopen("feedback.json", "a+");
}

$arrayFeedback = json_decode($file, true);
$arrayFeedback[] = array($data);

file_put_contents('data.json', json_encode($arrayFeedback))

unset($file);
unset($arrayFeedback);