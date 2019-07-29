<?php
return array(
    'about' => 'page/show/about',
    '' => 'index',
    'register' => 'register',
    'page/([-_a-z0-9]+)' => 'page/show/$1',
    'users/([-_a-z0-9]+)' => 'users/show/$1',
);
