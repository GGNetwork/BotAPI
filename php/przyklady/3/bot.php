<?php

require_once 'MessageBuilder.php';

$m = new MessageBuilder;

switch (rand(1, 3)) {
    case 1:
        $m->addText("Pierwsza linia\nDruga linia");
        break;
    case 2:
        $m
          ->addText('Tekst wysłany do innych')
          ->setRecipients([123, 456]);
        break;
    case 3:
        $m
          ->addText('Tekst wysłany do Ciebie i innych')
          ->setRecipients([123, 456, $_GET['from']]);
        break;
}

$m->reply();
