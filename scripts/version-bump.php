<?php

declare(strict_types=1);

$root   = dirname(__DIR__);
$dryRun = in_array('--dry-run', $argv, true);

function git(string $args): string
{
    $out = shell_exec('git ' . $args . ' 2>/dev/null');
    return trim((string) $out);
}

chdir($root);

// Last tag (i.e. v1.4.2 or 1.4.2), otherwise start at 0.0.0
$lastTag = git('describe --tags --abbrev=0');
$current = $lastTag !== '' ? ltrim($lastTag, 'v') : '0.0.0';
$range   = $lastTag !== '' ? escapeshellarg($lastTag) . '..HEAD' : 'HEAD';

// commit messages since last tag, separated by a split character
$log = git('log ' . $range . ' --pretty=format:%B%x1e');
$commits = array_values(array_filter(array_map('trim', explode("\x1e", $log))));

if ($commits === []) {
    fwrite(STDERR, "No new commits since $lastTag – no release needed.\n");
    exit(0); // success: build-release continues
}

// Determine bump type
$bump = 'patch';
foreach ($commits as $msg) {
    $subject = strtok($msg, "\n");
    if (preg_match('/^\w+(\(.+\))?!:/', $subject) || str_contains($msg, 'BREAKING CHANGE')) {
        $bump = 'major';
        break;
    }
    if (preg_match('/^feat(\(.+\))?:/i', $subject)) {
        $bump = 'minor';
    }
}

// Calculate new version
[$major, $minor, $patch] = array_map('intval', explode('.', $current) + [0, 0, 0]);
match ($bump) {
    'major' => [$major, $minor, $patch] = [$major + 1, 0, 0],
    'minor' => [$minor, $patch] = [$minor + 1, 0],
    'patch' => $patch++,
};
$new = "$major.$minor.$patch";

echo "Version: $current -> $new ($bump, " . count($commits) . " Commits)\n";

if ($dryRun) {
    exit(0);
}

// Modify files (Regex instead of json_encode, so formatting is preserved)
foreach (['composer.json', 'package.json'] as $file) {
    $path = "$root/$file";
    if (!is_file($path)) {
        continue;
    }
    $content = file_get_contents($path);
    $count = 0;
    $content = preg_replace(
        '/^(\s*"version"\s*:\s*")[^"]*(")/m',
        '${1}' . $new . '${2}',
        $content,
        1,
        $count
    );
    if ($count === 0) {
        echo "Warning: no \"version\" field in $file – skipped.\n";
        continue;
    }
    file_put_contents($path, $content);
    echo "$file updated.\n";
}
