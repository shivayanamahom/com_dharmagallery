<?php
$root=dirname(__DIR__); $errors=[]; $count=0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $file) {
 if (!$file->isFile() || str_contains($file->getPathname(),'/.git/')) continue;
 if ($file->getExtension()==='php') { $count++; exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()).' 2>&1',$out,$code); if ($code) $errors[]=implode("\n",$out); $out=[]; }
 if ($file->getExtension()==='xml' && simplexml_load_file($file->getPathname())===false) $errors[]='Invalid XML: '.$file;
}
$wa=json_decode(file_get_contents($root.'/media/joomla.asset.json'),true,512,JSON_THROW_ON_ERROR);
if ($wa['version']!==(string)simplexml_load_file($root.'/dharmagallery.xml')->version) $errors[]='Asset version mismatch';
$ru=parse_ini_file($root.'/admin/language/ru-RU/com_dharmagallery.ini'); $en=parse_ini_file($root.'/admin/language/en-GB/com_dharmagallery.ini');
if (array_keys($ru)!==array_keys($en)) $errors[]='Language keys differ';
if (count(simplexml_load_file($root.'/updates.xml')->update)) $errors[]='Do not advertise unreleased versions';
echo 'Checked PHP files: '.$count."\n"; foreach ($errors as $error) echo $error."\n"; echo count($errors)." errors\n"; exit($errors?1:0);
