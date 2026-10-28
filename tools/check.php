<?php
$root=dirname(__DIR__); $errors=[]; $count=0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $file) {
 if (!$file->isFile() || str_contains($file->getPathname(),'/.git/')) continue;
 if ($file->getExtension()==='php') { $count++; exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()).' 2>&1',$out,$code); if ($code) $errors[]=implode("\n",$out); $out=[]; }
 if ($file->getExtension()==='xml' && simplexml_load_file($file->getPathname())===false) $errors[]='Invalid XML: '.$file;
}
$manifest=simplexml_load_file($root.'/dharmagallery.xml');
$wa=json_decode(file_get_contents($root.'/media/joomla.asset.json'),true,512,JSON_THROW_ON_ERROR);
if ($wa['version']!==(string)$manifest->version) $errors[]='Asset version mismatch';
foreach (['com_dharmagallery.ini','com_dharmagallery.sys.ini'] as $file) {
 $ru=parse_ini_file($root.'/admin/language/ru-RU/'.$file); $en=parse_ini_file($root.'/admin/language/en-GB/'.$file);
 if (!$ru || !$en || array_keys($ru)!==array_keys($en)) $errors[]='Language keys differ: '.$file;
}
foreach (['content','editors-xtd'] as $group) {
 $plugin=$root.'/plugins/'.$group.'/dharmagallery'; $pm=@simplexml_load_file($plugin.'/dharmagallery.xml');
 if (!$pm) { $errors[]='Missing plugin manifest: '.$group; continue; }
 if ((string)$pm->version!==(string)$manifest->version) $errors[]='Plugin version mismatch: '.$group;
 if ((string)$pm['group']!==$group || (string)$pm->files->folder[0]['plugin']!=='dharmagallery') $errors[]='Invalid plugin manifest: '.$group;
 foreach (['.ini','.sys.ini'] as $suffix) {
  $name='plg_'.str_replace('-','-',$group).'_dharmagallery'.$suffix;
  $ru=@parse_ini_file($plugin.'/language/ru-RU/'.$name); $en=@parse_ini_file($plugin.'/language/en-GB/'.$name);
  if (!$ru || !$en || array_keys($ru)!==array_keys($en)) $errors[]='Plugin language keys differ: '.$name;
 }
}
$feed=simplexml_load_file($root.'/updates.xml');
foreach ($feed->update as $update) {
 $version=(string)$update->version;
 $url='https://github.com/shivayanamahom/com_dharmagallery/releases/download/v'.$version.'/com_dharmagallery-'.$version.'.zip';
 if ((string)$update->element!=='com_dharmagallery' || (string)$update->type!=='component') $errors[]='Invalid update extension';
 if (!preg_match('/^\d+\.\d+\.\d+$/',$version) || version_compare($version,(string)$manifest->version,'>')) $errors[]='Invalid update version';
 if ((string)$update->downloads->downloadurl!==$url) $errors[]='Invalid update download URL';
 if (!preg_match('/^[a-f0-9]{64}$/',(string)$update->sha256)) $errors[]='Invalid SHA-256';
 if ((string)$update->targetplatform['name']!=='joomla' || (string)$update->php_minimum!=='8.3.0') $errors[]='Missing update platform requirements';
 $archive=$root.'/dist/com_dharmagallery-'.$version.'.zip';
 if (is_file($archive) && hash_file('sha256',$archive)!==(string)$update->sha256) $errors[]='Archive/feed SHA-256 mismatch';
}
echo 'Checked PHP files: '.$count."\n"; foreach ($errors as $error) echo $error."\n"; echo count($errors)." errors\n"; exit($errors?1:0);
