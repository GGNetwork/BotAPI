<?php

require_once 'MessageBuilder.php';

$m = new MessageBuilder;

switch (file_get_contents('php://input')) {
    case 'cześć':
        $m->addText('Twój numer to ' . $_GET['from']);
        break;
    case 'kim jesteś?':
        $m->addText('Jestem botem.');
        break;
    default:
        $m->addText('Nie rozumiem...');
}

$m->reply();
