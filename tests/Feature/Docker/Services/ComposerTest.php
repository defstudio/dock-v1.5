<?php

use App\Docker\Services\Composer;
use Illuminate\Support\Facades\Storage;

beforeEach(fn() => Storage::fake('cwd')->put('.env', 'test'));

it('set its service name', function(){
   $composer = new Composer();

   expect($composer)->name()->toBe('composer');
})->only();

it('clears its dependencies', function(){

});
