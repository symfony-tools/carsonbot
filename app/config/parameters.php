<?php

if (getenv('GITHUB_TOKEN')) {
    $container->setParameter('github_token', getenv('GITHUB_TOKEN'));
}

if (getenv('SYMFONY_DOCS_SECRET')) {
    $container->setParameter('symfony_docs_secret', getenv('SYMFONY_DOCS_SECRET'));
}