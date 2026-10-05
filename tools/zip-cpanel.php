<?php

$workspace = dirname(__DIR__, 2);
$release = trim(file_get_contents($workspace.'/dist/latest-cpanel-path.txt'));
$zipPath = $workspace.'/dist/drc-cpanel-ready.zip';
if (!is_file($release.'/drc-app/vendor/autoload.php')) throw new RuntimeException('Install production vendor first.');
$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot create ZIP.');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($release, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($iterator as $entry) {
    if ($entry->isLink()) continue;
    $name = str_replace('\\', '/', substr($entry->getPathname(), strlen($release) + 1));
    if ($entry->isDir()) {
        $zip->addEmptyDir($name);
        $zip->setExternalAttributesName($name.'/', ZipArchive::OPSYS_UNIX, (040755 << 16));
    } else {
        $zip->addFile($entry->getPathname(), $name);
        $mode = in_array($name, ['drc-app/.env', 'AKSES-ADMIN.txt', 'database.sql']) ? 0100600 : 0100644;
        $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, ($mode << 16));
    }
}
$zip->close();
$check = new ZipArchive;
$check->open($zipPath, ZipArchive::CHECKCONS);
foreach (['drc-app/.env', 'drc-app/vendor/autoload.php', 'public_html/.htaccess', 'public_html/index.php', 'database.sql', 'PANDUAN-CPANEL.md', 'AKSES-ADMIN.txt', 'drc-app/storage/framework/sessions/', 'drc-app/storage/framework/views/'] as $required) if ($check->locateName($required) === false) throw new RuntimeException('Missing '.$required);
for ($index = 0; $index < $check->numFiles; $index++) {
    $name = $check->getNameIndex($index);
    if (str_contains($name, '.local/') || str_contains($name, 'database.sqlite') || str_contains($name, 'vendor/phpunit/') || str_contains($name, '.log') || str_contains($name, 'bootstrap/cache/config.php')) throw new RuntimeException('Unwanted release file: '.$name);
    if (str_starts_with($name, 'public_html/') && preg_match('~(?:\.env|\.sql|AKSES-ADMIN|PANDUAN|vendor/|storage/)~', $name)) throw new RuntimeException('Private file in public root: '.$name);
}
echo 'ZIP ready: '.$zipPath.' ('.round(filesize($zipPath) / 1048576, 2).' MB, '.$check->numFiles." entries)\n";
$check->close();
