<?php

use Illuminate\Support\Facades\Route;
use Maya719\ClickUp\ClickUp;

Route::get('/', function () {

    $clickup = new ClickUp('pk_234123358_3G0OBRHNRW9ZXTNYRXHBYPBWMMOKK75Q');
    dd($clickup->folders()->all('901812596227'));
});
