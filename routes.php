<?php
return array(
    '^index/logout$' => 'index/logout',
    'index/?(.+)' => 'index',
    'register' => 'register',
    '^$' => 'index',
    '^index$' => 'index',
    '^tournament$' => 'tournament',
    '^tournament/add$' => 'tournament/add',
    '^tournament/?(.+)' => 'tournament',


    'about' => 'page/show/about',
    'page/([-_a-z0-9]+)' => 'page/show/$1',
    'users/([-_a-z0-9]+)' => 'users/show/$1',
);
