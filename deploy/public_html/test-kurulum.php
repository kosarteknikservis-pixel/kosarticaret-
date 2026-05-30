<?php

header('Content-Type: text/plain; charset=UTF-8');
echo "OK - public_html calisiyor\n";
echo 'Tarih: '.date('c')."\n";
echo 'Dizin: '.__DIR__."\n";

$kosar = dirname(__DIR__).'/kosar/vendor/autoload.php';
echo 'kosar vendor: '.(is_file($kosar) ? 'VAR' : 'YOK')."\n";
