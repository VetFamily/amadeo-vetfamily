@extends('layouts.app')

@section('content')
<div class="flex-center">
    <div class="form-accueil-style ">
        <div class="logo-circle"></div>
        <div class="form-accueil-titre">
            <h1>amadeo.</h1>
            <h3>Outil de suivi des achats</h3>
        </div>
        
        <div class="form-accueil-corps">
            <form method="POST" action="{{ route('login') }}">
                {{ csrf_field() }}

                <div class="{{ $errors->has('name') ? ' has-error' : '' }}">
                    <label class="label_text">Identifiant <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus></label>
                    @if ($errors->has('name'))
                        <span class="help-block">
                            <strong>{{ $errors->first('name') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="{{ $errors->has('password') ? ' has-error' : '' }}">
                    <label class="label_text">Mot de passe <input id="password" type="password" class="form-control" name="password" required></label>
                    @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>

                <label class="label_checkbox">
                    <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}> Se souvenir de moi
                </label>

                <div class="button-section">
                    {!! Form::submit('CONNEXION') !!}
                    <a href="{{ route('password.request') }}">J'ai oublié mon mot de passe</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
