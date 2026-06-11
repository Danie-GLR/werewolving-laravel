@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <div class="card" style="text-align:center; padding: 3rem;">
        <h1> Werewolves amongst us</h1>
        <p style="color:#aaa; font-size:1.1rem;">
            whom to trust, only time can tell
        </p>
        <a href="{{ route('games.index') }}" class="btn">play the game brah</a>
    </div>
@endsection