<header>
    
    <div class="header-left">
        amadeo.
    </div>

    <div class="header-center">
        <label for="menu-mobile" class="menu-mobile">
            {{ Html::image('images/MENU_BLANC.png', 'Menu', array('width' => 25, 'height' => 25)) }} amadeo.
        </label>
        <input type="checkbox" id="menu-mobile" role="button" />
        
        <ul class="menu">
            @if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
            <li>
                <a href="{{ route('page.tableaudebord') }}" class="{{ (Request::is('tableaudebord') ? 'active-menu' : '') }}">@lang('amadeo.header.dashboard')</a>
            </li>
            @endif
            <li class="has-children">
                <a href="#" class="{{ (Request::is('statistiques') || Request::is('engagements') || Request::is('objectifs') ? 'active-menu' : '') }}">@lang('amadeo.header.monitoring')</a>
                <ul class="sous-menu">
                    @if ((sizeof(Auth::user()->roles) >0) AND ("Laboratoire" != Auth::user()->roles[0]['nom']))
                    <li><a href="{{ route('page.statistiques') }}" class="{{ (Request::is('statistiques') ? 'active-sous-menu' : '') }}">@lang('amadeo.header.purchases')</a></li>
                    <!--<li><a href="{{ route('page.engagements') }}" class="{{ (Request::is('engagements') ? 'active-sous-menu' : '') }}">Engagements</a></li>-->
                    @endif
                    @if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
                    <li><a href="{{ route('page.objectifs') }}" class="{{ (Request::is('objectifs') ? 'active-sous-menu' : '') }}">@lang('amadeo.header.targets')</a></li>
                    @endif
                </ul>
            </li>
            <li class="has-children">
                <a href="#" class="{{ (Request::is('cliniques') || Request::is('produits') || Request::is('categories') ? 'active-menu' : '') }}">@lang('amadeo.header.setting')</a>
                <ul class="sous-menu">
                    @if ((sizeof(Auth::user()->roles) >0) AND ("Laboratoire" != Auth::user()->roles[0]['nom']))
                    <li><a href="{{ route('page.cliniques') }}" class="{{ (Request::is('cliniques') ? 'active-sous-menu' : '') }}">@lang('amadeo.header.clinics')</a></li>
                    @endif
                    @if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
                    <li><a href="{{ route('page.produits') }}" class="{{ (Request::is('produits') ? 'active-sous-menu' : '') }}">@lang('amadeo.header.products')</a></li>
                    <li><a href="{{ route('page.categories') }}" class="{{ (Request::is('categories') ? 'active-sous-menu' : '') }}">@lang('amadeo.header.categories')</a></li>
                    @endif
                </ul>
            </li>
            @if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']) AND Session::get('user_is_super_admin'))
            <li>
                <a href="{{ route('page.administration') }}" class="{{ (Request::is('administration') ? 'active-menu' : '') }}">@lang('amadeo.header.administration')</a>
            </li>
            @endif
            <li class="menu-mobile-user">
                <a href="{{ route('page.password.change') }}">@lang('amadeo.header.user-settings')</a>
            </li>
            <li class="menu-mobile-user">
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">@lang('amadeo.header.logout')</a>
            </li>
        </ul>
    </div>

    <div class="header-right">
        <ul class="menu">
            <li class="has-children">
                <a>
                    <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    @if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
                    {{ Auth::user()->name }} (@lang('amadeo.header.user-administrator'))
                    @elseif ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" == Auth::user()->roles[0]['nom']))
                    {{ App\Model\Clinique::find(Session::get('user_clinique_id'))->veterinaires }}
                    @elseif ((sizeof(Auth::user()->roles) >0) AND ("Laboratoire" == Auth::user()->roles[0]['nom']))
                    {{ App\Model\Laboratoire::find(Session::get('user_laboratoire_id'))->nom }} (@lang('amadeo.header.user-seller'))
                    @endif
                    </div>
                    <!-- Icone -->
                    <div>{{ Html::image('images/FLECHE_BAS.svg', 'Logout', array('width' => 12, 'height' => 12)) }}</div>
                </a>

                <ul class="sous-menu">
                    <li><a href="{{ route('page.password.change') }}">@lang('amadeo.header.user-settings-title')</a></li>
                    <li><a href="{{ route('logout') }}" onclick="sessionStorage.clear(); event.preventDefault(); document.getElementById('logout-form').submit();">@lang('amadeo.header.logout')</a></li>
                </ul>
            </li>
        </ul>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            {{ csrf_field() }}
        </form>
    </div>
</header>

<script>
   $(function() {
    $.ajaxSetup({
        error: function(jqXHR, exception) {
            if (jqXHR.status === 419 || jqXHR.status === 401) {
                const message = '<p class="question">@lang("header.session-expired-question")</p>';
                confirmBox(@lang('header.session-expired'), message, '@lang("amadeo.yes")', '@lang("amadeo.no")', () => window.location = '/',  () => { return; });
            }
        },
    });
});
</script>
