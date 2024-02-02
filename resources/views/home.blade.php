@extends('app')
@section('content')
@auth
<p>Bem vindo(a), <b>{{ Auth::user()->name }}</b></p>
<a class="btn btn-info" href="{{ route('etapa') }}">Iniciar Teste</a>
<a class="btn btn-primary" href="{{ route('password') }}">Alterar a Senha</a>
<a class="btn btn-danger" href="{{ route('logout') }}">Deslogar</a>
@endauth
@guest
<a class="btn btn-primary" href="{{ route('login') }}">Logar</a>
<a class="btn btn-info" href="{{ route('register') }}">Registrar</a>
@endguest
@endsection