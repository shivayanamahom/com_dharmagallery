<?php
// Build only. This command never creates a tag or GitHub release.
$root=dirname(__DIR__); $manifest=simplexml_load_file($root.'/dharmagallery.xml'); $version=(string)$manifest->version;
@mkdir($root.'/dist',0775,true); $target=$root.'/dist/com_dharmagallery-'.$version.'.zip';
$zip=new ZipArchive(); if ($zip->open($target,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) { throw new RuntimeException('Cannot create archive'); }
foreach (['dharmagallery.xml','script.php','LICENSE.txt','admin','site','media','plugins'] as $entry) {
 if (is_file($root.'/'.$entry)) { $zip->addFile($root.'/'.$entry,$entry); continue; }
 foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$entry,FilesystemIterator::SKIP_DOTS)) as $file) {
  if ($file->isFile() && !str_starts_with($file->getFilename(),'.')) { $zip->addFile($file->getPathname(),substr($file->getPathname(),strlen($root)+1)); }
 }
}
$zip->close();
$latest=$root.'/dist/com_dharmagallery.zip';
if (!copy($target,$latest)) { throw new RuntimeException('Cannot create latest archive'); }
$hash=hash_file('sha256',$target);
file_put_contents($target.'.sha256',$hash.'  '.basename($target)."\n");
file_put_contents($root.'/dist/SHA256SUMS.txt',$hash.'  '.basename($target)."\n".$hash.'  '.basename($latest)."\n");
echo $target."\n".$latest."\n".$root.'/dist/SHA256SUMS.txt'."\n";
