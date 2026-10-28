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
require dirname(__DIR__).'/plugins/content/dharmagallery/src/Helper/Shortcode.php';
use Dharma\Plugin\Content\DharmaGallery\Helper\Shortcode as S;
check('shortcode id',fn()=>S::parse('id=5')==['id'=>5,'limit'=>0,'columns'=>0]);
check('shortcode quoted values',fn()=>S::parse('id="7" limit=\'12\' columns=4')==['id'=>7,'limit'=>12,'columns'=>4]);
check('shortcode limit capped',fn()=>S::parse('id=1 limit=9999')['limit']===100);
check('shortcode invalid columns dropped',fn()=>S::parse('id=1 columns=5')['columns']===0);
check('shortcode rejects non numeric id',fn()=>S::parse('id=abc')['id']===0 && S::parse('id=1;DROP')['id']===0);
check('shortcode unknown attributes ignored',fn()=>S::parse('id=2 onclick=alert(1)')==['id'=>2,'limit'=>0,'columns'=>0]);
$render=fn(array $a)=>'[A'.$a['id'].']';
check('replace plain code',fn()=>S::replace('before {dharmagallery id=3} after',$render)==='before [A3] after');
check('replace code alone in paragraph drops wrapper',fn()=>S::replace('<p>{dharmagallery id=3}</p>',$render)==='[A3]');
check('replace paragraph with attributes and spaces',fn()=>S::replace("<p class=\"x\">\n {dharmagallery id=4 limit=8} \n</p>",$render)==='[A4]');
check('replace code at start of paragraph',fn()=>S::replace('<p>{dharmagallery id=3} tail</p>',$render)==='[A3]<p> tail</p>');
check('replace code at end of paragraph',fn()=>S::replace('<p>head {dharmagallery id=3}</p>',$render)==='<p>head </p>[A3]');
check('replace several codes',fn()=>S::replace('{dharmagallery id=1}<p>{dharmagallery id=2}</p>',$render)==='[A1][A2]');
check('replace is case insensitive',fn()=>S::replace('{DharmaGallery id=6}',$render)==='[A6]');
check('strip removes codes',fn()=>S::strip('a <p>{dharmagallery id=1}</p> b')==='a  b');
check('text without code untouched',fn()=>S::replace('<p>nothing {here}</p>',$render)==='<p>nothing {here}</p>' && !S::contains('x'));
echo "$passed passed, $failed failed\n";exit($failed?1:0);
