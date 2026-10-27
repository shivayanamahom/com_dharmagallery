<?php
// Content validation tests do not require a running Joomla application.
define('_JEXEC',1);
$fixture=sys_get_temp_dir().'/dharmagallery-tests-'.bin2hex(random_bytes(4)); mkdir($fixture); mkdir($fixture.'/images'); define('JPATH_ROOT',$fixture);
require dirname(__DIR__).'/admin/src/Helper/MediaHelper.php';
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper as M;
$passed=0; $failed=0;
function check($name,$fn) { global $passed,$failed; try { if (!$fn()) throw new RuntimeException('Assertion failed'); $passed++; } catch (Throwable $e) { $failed++; echo 'FAIL '.$name.': '.$e->getMessage()."\n"; } }
function rejects($fn) { try { $fn(); return false; } catch (InvalidArgumentException $e) { return true; } }
foreach (['../secret.pdf','images/../secret.pdf','https://example.com/a.jpg','images/.hidden/a.pdf','images//a.pdf','images/a.php','images/a.svg','images/a.html','images/a%2ephp.jpg','images/a.pdf?x=1','images/a.zip'] as $path) check('reject '.$path,fn()=>rejects(fn()=>M::path($path)));
check('Joomla media fragment',fn()=>M::path('images/test.jpg#joomlaImage://local-images/test.jpg?width=1')==='images/test.jpg');
check('Joomla local adapter',fn()=>M::path('local-images:/test.pdf')==='images/test.pdf');
check('Cyrillic file path',fn()=>M::path('images/Документы/Лицензия.pdf')==='images/Документы/Лицензия.pdf');
file_put_contents($fixture.'/images/fake.jpg','<?php echo "bad";');
check('MIME mismatch',fn()=>rejects(fn()=>M::metadata('images/fake.jpg')));
file_put_contents($fixture.'/images/test.txt','Dharma Gallery test');
check('plain document metadata',fn()=>M::metadata('images/test.txt')['type']==='file');
check('missing file',fn()=>rejects(fn()=>M::metadata('images/missing.pdf')));
file_put_contents($fixture.'/outside.pdf',"%PDF-1.4\n%%EOF"); symlink($fixture.'/outside.pdf',$fixture.'/images/link.pdf');
check('symlink outside images',fn()=>rejects(fn()=>M::absolute('images/link.pdf')));
$z=new ZipArchive();$z->open($fixture.'/images/fake.docx',ZipArchive::CREATE);$z->addFromString('unrelated.txt','test');$z->close();
check('unrelated ZIP renamed DOCX',fn()=>rejects(fn()=>M::metadata('images/fake.docx')));
$z=new ZipArchive();$z->open($fixture.'/images/valid.docx',ZipArchive::CREATE);$z->addFromString('word/document.xml','<document/>');$z->close();
check('DOCX structure',fn()=>M::metadata('images/valid.docx')['type']==='file');
$z=new ZipArchive();$z->open($fixture.'/images/fake.odt',ZipArchive::CREATE);$z->addFromString('mimetype','text/html');$z->close();
check('ODT mime mismatch',fn()=>rejects(fn()=>M::metadata('images/fake.odt')));
if (extension_loaded('gd')) { $im=imagecreatetruecolor(20,10);imagejpeg($im,$fixture.'/images/test.jpg');check('image metadata',fn()=>M::metadata('images/test.jpg')['type']==='image'); }
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixture,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file) { if ($file->isDir()&&!$file->isLink()) rmdir($file); else unlink($file); } rmdir($fixture);
echo "$passed passed, $failed failed\n";exit($failed?1:0);
