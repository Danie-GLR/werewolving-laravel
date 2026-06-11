<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Game list + creation
Route::get('/games',        [GameController::class, 'index'])->name('games.index');
Route::get('/games/create', [GameController::class, 'create'])->name('games.create');
Route::post('/games',       [GameController::class, 'store'])->name('games.store');

// Lobby — shared join page, everyone visits this URL
Route::get('/games/{game}/lobby',  [GameController::class, 'lobby'])->name('games.lobby');
Route::post('/games/{game}/join',  [GameController::class, 'join'])->name('games.join');

// Game master controls (session-protected inside controller)
Route::post('/games/{game}/assign-roles', [GameController::class, 'assignRoles'])->name('games.assign-roles');
Route::post('/games/{game}/start',        [GameController::class, 'start'])->name('games.start');

// Each device's secret role page (no token in URL — session only)
Route::get('/games/{game}/my-role', [GameController::class, 'myRole'])->name('games.my-role');

// Live game board
Route::get('/games/{game}/play',  [GameController::class, 'play'])->name('games.play');

// Night actions
Route::post('/games/{game}/night-vote',  [GameController::class, 'nightVote'])->name('games.night-vote');
Route::post('/games/{game}/doctor-save', [GameController::class, 'doctorSave'])->name('games.doctor-save');
Route::post('/games/{game}/seer-peek',   [GameController::class, 'seerPeek'])->name('games.seer-peek');

// Phase transitions
Route::post('/games/{game}/resolve-night', [GameController::class, 'resolveNight'])->name('games.resolve-night');
Route::post('/games/{game}/day-vote',      [GameController::class, 'dayVote'])->name('games.day-vote');
Route::post('/games/{game}/resolve-day',   [GameController::class, 'resolveDayManual'])->name('games.resolve-day');

// Legacy redirect
Route::get('/games/{game}', [GameController::class, 'show'])->name('games.show');
