<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <title>Paramètres</title>

        <!-- Font -->
        
        <!-- Styles -->
        <!-- {{ Html::style('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.min.css') }} -->
        {{ Html::style('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css') }}
        {{ Html::style('https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css') }}
        {{ Html::style(asset('css/commun/button.css')) }}
        {{ Html::style(asset('css/commun/commun.css')) }}
        {{ Html::style(asset('css/commun/form.css')) }}
        {{ Html::style(asset('css/commun/header.css')) }}

        <!-- JS Script -->
        {{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js') }}
        {{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js') }}
        {{ Html::script(asset('js/commun.js')) }}
        {{ Html::script(asset('js/header.js')) }}
    </head>
    <body>
        @include('commun/header')

        {!! Form::open(['class' => 'ajax-form-stats']) !!}
        <div class="layout_withoutParams form-style">
            <div class="zone_title">
                <div class="title">Changement de mot de passe</div>
            </div>

            <div class="flex-center flex-column position-rel">
                <div class="form-accueil-style ">
                    <div class="panel-body">
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif
                            @if (session('success'))
                                <div class="alert alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif
                        <form class="form-horizontal" method="POST" action="{{ route('password.change') }}">
                            {{ csrf_field() }}

                            <div class="form-group{{ $errors->has('current-password') ? ' has-error' : '' }}">
                                <label>Mot de passe actuel <input id="current-password" type="password" class="form-control" name="current-password" required autofocus></label>
                                @if ($errors->has('current-password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('current-password') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group{{ $errors->has('new-password') ? ' has-error' : '' }}">
                                <label>Nouveau mot de passe <input id="new-password" type="password" class="form-control" name="new-password" required></label>
                                @if ($errors->has('new-password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('new-password') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="form-group{{ $errors->has('new-password-confirm') ? ' has-error' : '' }}">
                                <label>Confirmation du mot de passe <input id="new-password-confirm" type="password" class="form-control" name="new-password_confirmation" required></label>
                                @if ($errors->has('new-password-confirm'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('new-password-confirm') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="button-section">
                                {!! Form::submit('ENREGISTRER') !!}
                                {!! Form::button('RETOUR', ['onclick' => 'document.location.href="/"']) !!}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </body>
</html>