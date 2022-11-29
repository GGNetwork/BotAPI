<?php

require_once 'MessageBuilder.php';

$p = new PushConnection(123456, 'wojtek@gg.pl', 'hasło');
$m = new MessageBuilder;

switch (file_get_contents("php://input")) {
    case 'kot':
        $m
          ->addText('Oto kot:')
          ->addImage('kot.jpg');
        break;
    default:
        $m
          ->addText('A to jest GG:')
          ->addImage('gg.png');
}

$m->reply();
