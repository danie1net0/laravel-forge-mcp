<?php

use Illuminate\Database\Eloquent\Model;

arch('globals')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

arch()->preset()->security()->ignoring('App\Helpers');
arch()->preset()->php();

arch('app')
    ->expect('env')->not->toBeUsed()
    ->expect('App\Http\Controllers')->toHaveSuffix('Controller')
    ->expect('App\Models')->toExtend(Model::class)->ignoring('App\Models\Casts')->ignoring('App\Models\Scopes');
