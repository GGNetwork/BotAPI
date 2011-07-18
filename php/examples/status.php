<?
require_once(dirname(__FILE__).'/../PushConnection.php');

$P=new PushConnection(123456, 'login', 'hasło');
$P->setStatus('test opisu', STATUS_FFC);
sleep(5);
$P->setStatus('', STATUS_BACK);
