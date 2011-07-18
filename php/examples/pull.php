<?
require_once(dirname(__FILE__).'/../MessageBuilder.php');

$M=new MessageBuilder();

switch (rand(1, 7)) {
	case 1: $M->addText('1. Zwykły tekst bez formatowania w kolorze pomarańczowym', FORMAT_NONE, 255, 165, 0); break;
	case 2: $M->addText('2. Tekst pogrubiony, pochylony i podkreślony', FORMAT_BOLD_TEXT | FORMAT_ITALIC_TEXT | FORMAT_UNDERLINE_TEXT); break;
	case 3: $M->addText('3. Tekst podkreślony w kolorze czerwonym z obrazkiem', FORMAT_UNDERLINE_TEXT, 255, 0, 0)->addImage(dirname(__FILE__).'/gg.jpg'); break;
	case 4: $M->addText("4. Pierwsza linia\nDruga linia"); break;
	case 5: $M->addText('5. Tekst wysłany do innych')->setRecipients(array(123,456)); break;
	case 6: $M->addBBcode('6. ab[b]cd[u]ef[/u][i]gh[/i][/b]ij[br]Druga linia'); break;
	case 7: $M->addRawHtml('7. Tekst <b>pogrubiony</b> oraz <i>pochylony</i> oraz <u>podkreślony</u>'); break;
}

$M->reply();
