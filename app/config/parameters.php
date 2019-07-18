<?php

if (getenv('github_token')) {
    $container->setParameter('github_token', getenv('github_token'));
}

if (getenv('symfony_docs_secret')) {
    $container->setParameter('symfony_docs_secret', getenv('symfony_docs_secret'));
}