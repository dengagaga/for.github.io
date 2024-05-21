<?php

function getNewCsrfToken()
{
    $token = uniqid('', true);
    $_SESSION['CSRF'] = $token;
    return $token;
}