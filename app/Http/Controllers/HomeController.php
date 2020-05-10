<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Commun\HeaderController;
use App\Repositories\ParametrageRepository;
use App\Repositories\RoleRepository;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(HeaderController $controller, RoleRepository $roleRepository, ParametrageRepository $settingRepository)
    {   
        $controller->getUserInformations($roleRepository, $settingRepository);

        // Redirection vers les engagements pour les vétérinaires
        if ((sizeof(Auth::user()->roles) >0) && ("Vétérinaire" == Auth::user()->roles[0]['nom']))
        {
            return redirect()->route('page.tableaudebord');
        }

        return redirect()->route('page.statistiques');
    }

    public function showChangePasswordForm()
    {
        return view('auth.passwords.change');
    }

    public function changePassword(Request $request){
 
        if (!(Hash::check($request->get('current-password'), Auth::user()->password))) {
            // The passwords matches
            return redirect()->back()->with("error","Votre mot de passe actuel ne correspond pas au mot de passe que vous avez fourni. Veuillez réessayer.");
        }
 
        if(strcmp($request->get('current-password'), $request->get('new-password')) == 0){
            //Current password and new password are same
            return redirect()->back()->with("error","Le nouveau mot de passe ne peut pas être le même que votre mot de passe actuel. Veuillez choisir un mot de passe différent.");
        }
 
        $validatedData = $request->validate([
            'current-password' => 'required',
            'new-password' => 'required|string|min:6|confirmed',
        ]);
 
        //Change Password
        $user = Auth::user();
        $user->password = \Hash::make($request->get('new-password'));
        $user->save();
 
        return redirect()->back()->with("success","Le mot de passe a été changé avec succès !");
 
    }
}
