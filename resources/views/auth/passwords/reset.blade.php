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
            <h2>Changement de mot de passe</h2>

            <form method="POST" action="{{ route('password.request') }}">
                {{ csrf_field() }}

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                    <label class="label_text">Identifiant <input id="name" type="text" class="form-control" name="name" value="{{ $name or old('name') }}" required autofocus></label>
                    @if ($errors->has('name'))
                        <span class="help-block">
                            <strong>{{ $errors->first('name') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                    <label class="label_text">Mot de passe <input id="password" type="password" class="form-control" name="password" required></label>
                    @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                    <label class="label_text">Confirmation mot de passe <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required></label>
                    @if ($errors->has('password_confirmation'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password_confirmation') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="button-section">
                    {!! Form::submit('RÉINITIALISER') !!}
                    {!! Form::button('RETOUR', ['onclick' => 'history.go(-1)']) !!}
                </div>

            </form>
        </div>
    
    </div>
</div>
@endsection
