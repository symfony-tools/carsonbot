#!/usr/bin/env php
<?php

if ($argc !== 3) {
    echo "./docs-invalid-use.php path-to-docs path-to-symfony\n";
    exit(1);
}

// Input
$docs = $argv[1];
$symfony = $argv[2];

$autoload = [
    __DIR__.'/vendor/autoload.php',
    $symfony.'/vendor/autoload.php',
];

foreach ($autoload as $autoloadPHP) {
    if (!file_exists($autoloadPHP)) {
        echo "File $autoloadPHP does not exist.\nMake sure to run 'composer update'\n";
        exit(1);
    }
    require $autoloadPHP;
}

use Symfony\Component\Finder\Finder;
$finder = new Finder();
$finder->in($docs)->name('*.rst');

$blocklist = getBlocklist();
$count = 0;
foreach ($finder as $file) {
    $contents = $file->getContents();
    $matches = [];
    if (preg_match_all('|^ +use (.*\\\.*); *?$|im', $contents, $matches)) {
        foreach ($matches[1] as $class) {
            if (substr($class, 0, 3) === 'App' || substr($class, 0, 4) === 'Acme') {
                continue;
            }

            if (false !== $pos = strpos($class, ' as ')) {
                $class = substr($class, 0, $pos);
            }

            if (false !== $pos = strpos($class, 'function ')) {
                continue;
            }

            if (in_array($class, $blocklist)) {
                continue;
            }

            $explode = explode('\\', $class);
            if (count($explode) === 3 && $explode[0] === 'Symfony' && $explode[1] === 'Component') {
                continue;
            }

            if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
                $count++;
                echo $file->getRelativePath().'/'.$file->getFilename(). ' - '.$class. PHP_EOL;
            }
        }
    }
}

if ($count === 0) {
    error_log("We found nothing\n");
}

exit(0);

function getBlocklist(): array
{
    return [
        'Doctrine\ORM\Mapping',
        'Symfony\Component\Validator\Constraints',
        'Simplex\StringResponseListener',
        'Calendar\Model\LeapYear',
        'Symfony\Component\Security\Core\Validator\Constraints',
        'Simplex\Framework',
        'Calendar\Controller\LeapYearController',
        'ApiPlatform\Core\Annotation\ApiResource',
        'Other\Qux',
        'Doctrine\Bundle\FixturesBundle\Fixture',
        'Vendor\DependencyClass',
        'Example\Namespace\YourAwesomeCoolClass',
        'Your\Transport\YourTransportFactory',
    ];
}
