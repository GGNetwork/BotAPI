<?php

require_once 'PushConnection.php';

$p = new PushConnection(123456, 'wojtek@gg.pl', 'hasło'); // autoryzacja
$p->setStatus('Mój nowy opis', PushConnection::STATUS_AWAY);
