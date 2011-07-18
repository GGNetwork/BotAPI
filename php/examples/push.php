<?
require_once(dirname(__FILE__).'/../PushConnection.php');

$M=new MessageBuilder();
$M->setRecipients(array(12345,23456,34567,45678));

switch (rand(1, 4)) {
case 1: $M->addText('1. Zwykły tekst bez formatowania w kolorze pomarańczowym', FORMAT_NONE, 255, 165, 0); break;
case 2: $M->addText('2. Tekst pogrubiony, pochylony i podkreślony', FORMAT_BOLD_TEXT | FORMAT_ITALIC_TEXT | FORMAT_UNDERLINE_TEXT); break;
case 3: $M->addText('3. Tekst podkreślony w kolorze czerwonym z obrazkiem', FORMAT_UNDERLINE_TEXT, 255, 0, 0)->addImage(dirname(__FILE__).'/gg.jpg'); break;
case 4: $M->addText('4. Zwykły tekst bez formatowania'); break;
}

$P=new PushConnection(1234567, 'login', 'hasło');
$P->push($M);
