<?php

// ── Add these two routes to your existing games route group in routes/web.php ──
// Place them alongside your existing night-vote, day-vote, etc. routes

// Heartbeat — called by JS every 10 seconds to track presence
Route::post('/games/{game}/heartbeat', [GameController::class, 'heartbeat'])
    ->name('games.heartbeat');

// Chat
Route::post('/games/{game}/chat', [GameController::class, 'sendChat'])
    ->name('games.chat');


// ── Full games route block for reference (replace your existing block) ──────────

use App\Http\Controllers\GameController;

Route::get('/games',                      [GameController::class, 'index'])->name('games.index');
Route::get('/games/create',               [GameController::class, 'create'])->name('games.create');
Route::post('/games',                     [GameController::class, 'store'])->name('games.store');
Route::get('/games/{game}',               [GameController::class, 'show'])->name('games.show');
Route::get('/games/{game}/edit',          [GameController::class, 'edit'])->name('games.edit');
Route::put('/games/{game}',               [GameController::class, 'update'])->name('games.update');
Route::delete('/games/{game}',            [GameController::class, 'destroy'])->name('games.destroy');

Route::get('/games/{game}/lobby',         [GameController::class, 'lobby'])->name('games.lobby');
Route::post('/games/{game}/join',         [GameController::class, 'join'])->name('games.join');
Route::post('/games/{game}/gm-join',      [GameController::class, 'gmJoin'])->name('games.gm-join');
Route::post('/games/{game}/add-bots',     [GameController::class, 'addBots'])->name('games.add-bots');
Route::post('/games/{game}/remove-bots',  [GameController::class, 'removeBots'])->name('games.remove-bots');
Route::post('/games/{game}/assign-roles', [GameController::class, 'assignRoles'])->name('games.assign-roles');
Route::post('/games/{game}/start',        [GameController::class, 'start'])->name('games.start');

Route::get('/games/{game}/play',          [GameController::class, 'play'])->name('games.play');
Route::get('/games/{game}/my-role',       [GameController::class, 'myRole'])->name('games.my-role');

Route::post('/games/{game}/night-vote',   [GameController::class, 'nightVote'])->name('games.night-vote');
Route::post('/games/{game}/doctor-save',  [GameController::class, 'doctorSave'])->name('games.doctor-save');
Route::post('/games/{game}/seer-peek',    [GameController::class, 'seerPeek'])->name('games.seer-peek');

Route::post('/games/{game}/day-vote',     [GameController::class, 'dayVote'])->name('games.day-vote');

Route::post('/games/{game}/heartbeat',    [GameController::class, 'heartbeat'])->name('games.heartbeat');
Route::post('/games/{game}/chat',         [GameController::class, 'sendChat'])->name('games.chat');
Route::get('/games/{game}/chat/fetch',    [GameController::class, 'fetchChat'])->name('games.chat.fetch');
Route::get('/games/{game}/votes/fetch',   [GameController::class, 'fetchVotes'])->name('games.votes.fetch');
