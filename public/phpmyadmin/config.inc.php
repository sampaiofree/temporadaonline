<?php
declare(strict_types=1);

$cfg = [];
$cfg['blowfish_secret'] = 'xL9dX6Uz3eY5qB7kA2mP8sW4vC0fH1rJ';

$i = 0;
$i++;
$cfg['Servers'][$i]['host'] = '127.0.0.1';
$cfg['Servers'][$i]['port'] = '3306';
$cfg['Servers'][$i]['socket'] = '';
$cfg['Servers'][$i]['user'] = 'sampaio.free@gmail.com';
$cfg['Servers'][$i]['password'] = 'admin123*';
$cfg['Servers'][$i]['auth_type'] = 'config';
$cfg['Servers'][$i]['extension'] = 'mysqli';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = false;
$cfg['Servers'][$i]['AllowRoot'] = true;

$cfg['TempDir'] = __DIR__ . '/tmp';
$cfg['DefaultLang'] = 'pt-utf-8';
$cfg['Servers'][$i]['controluser'] = '';
$cfg['Servers'][$i]['controlpass'] = '';
