<?php

$workspace = dirname(__DIR__, 2);
$source = trim(file_get_contents($workspace.'/dist/latest-cpanel-path.txt'));
$release = $workspace.'/dist/cpanel-public-html-'.date('Ymd-His');
$root = $release.'/public_html';
function copyTree(string $from, string $to): void {
    if (!is_dir($to)) mkdir($to, 0755, true);
    foreach (new DirectoryIterator($from) as $entry) {
        if ($entry->isDot() || $entry->isLink()) continue;
        $target = $to.'/'.$entry->getFilename();
        if ($entry->isDir()) copyTree($entry->getPathname(), $target);
        elseif (!copy($entry->getPathname(), $target)) throw new RuntimeException('Copy failed.');
    }
}
if (!is_file($source.'/drc-app/vendor/autoload.php')) throw new RuntimeException('Production package missing.');
copyTree($source.'/public_html', $root);
copyTree($source.'/drc-app', $root.'/drc-app');
$index = file_get_contents(__DIR__.'/../deploy/cpanel/index.php');
$index = str_replace("dirname(__DIR__).'/drc-app'", "__DIR__.'/drc-app'", $index);
$index = preg_replace('~// Default cPanel layout:.*?\$appPath~s', "// Laravel is inside the protected drc-app folder.\n\$appPath", $index);
file_put_contents($root.'/index.php', $index);
$htaccess = file_get_contents($root.'/.htaccess');
$htaccess = str_replace('RewriteEngine On', "RewriteEngine On\n\n    # Block application source, credentials and deployment files.\n    RewriteRule ^drc-app(?:/|$) - [F,L,NC]", $htaccess);
$deny = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order Allow,Deny\n    Deny from all\n</IfModule>\n";
$htaccess .= "\n<FilesMatch \"(?i)^(?:\\.env(?:\\..*)?|composer\\.(?:json|lock)|artisan|.*\\.(?:sql|log|bak)|AKSES-ADMIN\\.txt)$\">\n".$deny."</FilesMatch>\n";
file_put_contents($root.'/.htaccess', $htaccess);
file_put_contents($root.'/drc-app/.htaccess', $deny);
mkdir($root.'/drc-app/deployment', 0755, true);
foreach (['database.sql', 'AKSES-ADMIN.txt', 'MANIFEST.json'] as $name) copy($source.'/'.$name, $root.'/drc-app/deployment/'.$name);
copy(__DIR__.'/../deploy/cpanel/PANDUAN-PUBLIC-HTML.md', $root.'/drc-app/deployment/PANDUAN-PUBLIC-HTML.md');
$zipPath = $workspace.'/dist/drc-public-html-ready.zip';
$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('ZIP failed.');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($iterator as $entry) {
    $name = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
    if ($entry->isDir()) {
        $zip->addEmptyDir($name);
        $zip->setExternalAttributesName($name.'/', ZipArchive::OPSYS_UNIX, 040755 << 16);
    } else {
        $zip->addFile($entry->getPathname(), $name);
        $private = $name === 'drc-app/.env' || str_starts_with($name, 'drc-app/deployment/');
        $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, ($private ? 0100600 : 0100644) << 16);
    }
}
$zip->close();
$check = new ZipArchive;
if ($check->open($zipPath, ZipArchive::CHECKCONS) !== true) throw new RuntimeException('ZIP verification failed.');
foreach (['index.php', '.htaccess', 'drc-app/.htaccess', 'drc-app/.env', 'drc-app/vendor/autoload.php', 'drc-app/deployment/database.sql', 'drc-app/deployment/AKSES-ADMIN.txt', 'drc-app/storage/framework/sessions/'] as $required) {
    if ($check->locateName($required) === false) throw new RuntimeException('Missing '.$required);
}
for ($i = 0; $i < $check->numFiles; $i++) {
    $name = $check->getNameIndex($i);
    if (str_starts_with($name, 'public_html/') || str_contains($name, 'database.sqlite') || str_contains($name, '.local/') || str_contains($name, 'bootstrap/cache/config.php')) throw new RuntimeException('Unexpected '.$name);
}
file_put_contents($workspace.'/dist/latest-public-html-path.txt', $root);
echo 'Ready: '.$zipPath.' ('.round(filesize($zipPath)/1048576, 2).' MB, '.$check->numFiles." entries)\n";
$check->close();
