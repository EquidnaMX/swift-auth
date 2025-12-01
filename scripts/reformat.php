<?php
// Simple repo reformatter for import grouping and spacing
// Usage: php scripts/reformat.php

$root = __DIR__ . '/../';
$paths = [
    $root . 'src/',
    $root . 'tests/Unit/',
];

$frameworkPrefixes = [
    'Illuminate\\', 'Psr\\', 'Symfony\\', 'Laravel\\', 'Carbon\\', 'Monolog\\', 'GuzzleHttp\\', 'Nyholm\\', 'Egulias\\', 'Doctrine\\', 'Laminas\\', 'Ramsey\\', 'PhpParser\\', 'League\\', 'TijsVerkoyen\\', 'Aws\\', 'Mailgun\\', 'Vonage\\', 'Twilio\\', 'SendGrid\\'
];

function isFrameworkImport(string $imp) : bool {
    global $frameworkPrefixes;
    foreach ($frameworkPrefixes as $p) {
        if (strpos($imp, $p) === 0) return true;
    }
    return false;
}

function classifyImport(string $imp, string $fileNamespaceRoot) : string {
    $impTrim = ltrim($imp);
    $impTrim = preg_replace('#^use\s+#', '', $impTrim);
    $impTrim = preg_replace('#;\s*$#', '', $impTrim);
    $impTrim = trim($impTrim);
    if (strpos($impTrim, '\\') === false) return 'tail';
    if (isFrameworkImport($impTrim)) return 'framework';
    if ($fileNamespaceRoot && strpos($impTrim, $fileNamespaceRoot . '\\') === 0) return 'first';
    if (strpos($impTrim, 'App\\') === 0) return 'first';
    return 'third';
}

$changedFiles = [];

foreach ($paths as $path) {
    if (!is_dir($path)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        if ($file->getExtension() !== 'php') continue;
        $filePath = $file->getRealPath();
        $content = file_get_contents($filePath);
        $orig = $content;
        $content = str_replace("\r\n", "\n", $content);
        $lines = explode("\n", $content);
        $fileNamespaceRoot = '';
        foreach ($lines as $line) {
            if (preg_match('#^namespace\s+([^;]+);#', trim($line), $m)) {
                $ns = trim($m[1]);
                $parts = explode('\\', $ns);
                $fileNamespaceRoot = $parts[0] ?? '';
                break;
            }
        }
        $newContent = $content;
        if (preg_match('#(namespace[^;]+;\n)(.*?)\n(\n|class|interface|trait|abstract\s+class)#s', $content, $m)) {
            $prefix = $m[1];
            $block = $m[2];
            $suffixFirst = $m[3];
            $useLines = [];
            $others = [];
            foreach (explode("\n", $block) as $ln) {
                $trim = trim($ln);
                if ($trim === '') continue;
                if (strpos($trim, 'use ') === 0) {
                    $useLines[] = $trim;
                } else {
                    $others[] = $ln;
                }
            }
            if (count($useLines) > 0) {
                $groups = ['framework'=>[], 'third'=>[], 'first'=>[], 'tail'=>[]];
                foreach ($useLines as $u) {
                    $cls = classifyImport($u, $fileNamespaceRoot);
                    $groups[$cls][] = $u;
                }
                foreach ($groups as $k => &$g) {
                    sort($g, SORT_STRING);
                }
                $ordered = array_merge($groups['framework'], $groups['third'], $groups['first'], $groups['tail']);
                if (!empty($ordered)) {
                    $seq = [];
                    if (!empty($groups['framework'])) $seq[] = $groups['framework'];
                    if (!empty($groups['third'])) $seq[] = $groups['third'];
                    if (!empty($groups['first'])) $seq[] = $groups['first'];
                    if (!empty($groups['tail'])) $seq[] = $groups['tail'];
                    $partsOut = [];
                    foreach ($seq as $gpart) {
                        $partsOut[] = implode("\n", $gpart);
                    }
                    $newBlock = implode("\n\n", $partsOut);
                    $newBlock .= "\n";
                }
                $replacement = $prefix . ($newBlock ?? '') . ($others ? implode("\n", $others) . "\n" : '') . $suffixFirst;
                $newContent = preg_replace('#' . preg_quote($m[0], '#') . '#s', $replacement, $content, 1);
            }
        }
        $newContent = preg_replace('#(\n\s*(public|protected|private)\s+[^;\n]+;\n)(\s*(public|protected|private)\s+function)#', "$1\n$3", $newContent);
        if (substr($newContent, -1) !== "\n") $newContent .= "\n";
        if ($newContent !== $orig) {
            file_put_contents($filePath, $newContent);
            $changedFiles[] = str_replace($root, '', $filePath);
        }
    }
}

if (!empty($changedFiles)) {
    echo "Modified files:\n" . implode("\n", $changedFiles) . "\n";
} else {
    echo "No changes made.\n";
}

return 0;
