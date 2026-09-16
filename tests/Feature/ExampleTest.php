<?php

test('the root sends visitors to the admin panel', function () {
    $this->get('/')->assertRedirect('/admin');
});
