<?php
return array(
    '^index/logout$' => 'index/logout',
    'index/?(.+)' => 'index',
    'register' => 'register',
    '^$' => 'index',
    '^index$' => 'index',
    '^tournament/$' => 'tournament',
    '^tournament/add$' => 'tournament/add',
    '^tournament/show/([-_a-z0-9]+)$' => 'tournament/show/$1',
    '^tournament/edit/([\d]+)$' => 'tournament/edit/$1',

    'about' => 'page/show/about',
);
