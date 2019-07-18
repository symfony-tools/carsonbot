<?php

if (getenv('github_token')) {
    $container->setParameter('github_token', getenv('github_token'));
}
