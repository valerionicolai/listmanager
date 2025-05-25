<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class SilentLoginController extends Controller
{
    private $isLoginParamName = 'selda_silent_auth';
    private $tokenParamName = 'access_token';
    private $delimiter = '@_@';
    private $expirationTime = 20; //seconds

    private $tokenDesign = [
        0 => 'nome',
        1 => 'cognome',
        2 => 'email',
        3 => 'timestamp'
    ];


    public function handle(Request $request)
    {
       
        if(!$this->checkIsLoggingIn($request))
        {
            $returnstatus = "Error";
            $returnmessage = "Check login failed";
            $returncode = 500;
        
        }
        else 
        {
            $login_params = $this->getRequestToken($request);
            if(!$login_params || !$this->checkExpiration($login_params['timestamp']))
            {
                $returnstatus = "Error";
                $returnmessage = "Check Token failed";
                $returncode = 500;
            }
            else
            {
                $returnstatus = "Success";
                $returnmessage = "Check OK";
                $returncode = 200;
            }
        }

        return response()->json([
            'status' => $returnstatus,
            'message' => $returnmessage, // Add your custom message
            'data' => [ // Optional additional data
                'timestamp' => now()->toDateTimeString()
            ]
        ], $returncode);
    }

    protected function checkIsLoggingIn($req = [])
    {
        $login = isset($req[$this->isLoginParamName]) ? $req[$this->isLoginParamName] : null;

        return (int)$login === 1;
    }

    protected function getRequestToken($req)
    {
        $token = $req[$this->tokenParamName];

        if(!isset($token)) {
            error_log('Silent Login Error: No Token in request');
            return null;
        }

        return $this->decodeToken($token);
    }

protected function decodeToken($token = '')
    {
        $result = [];

        //$token = str_rot13($token);

        $params = explode($this->delimiter, $token);

        if($params != false) :

            foreach($params as $key => $param)
            {
                $result[$this->tokenDesign[$key]] = $param;
            }

            //$result = $this->checkEmailContent($result);

            return $result;

        endif;

        return false;
    }

    protected function checkExpiration($time = 0)
    {
        if($time > (time() + $this->expirationTime))
            return false;

        return true;
    }


    public function checkEmailContent($params = [])
    {

        if($params['email'] == null || $params['email'] == '') :
            $params['email'] = $this->generateEmail($params);
            return $params;
        endif;

        return $params;
    }

    public function generateEmail($params = [])
    {
        return $params['nome'] . '_' . $params['cognome'] . '@temporaryfedermanagermail.it';
    }

    public function handleOLD(Request $request)
    {
        
        // TODO: Replace with your actual validation logic
        $validated = $request->validate([
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'timestamp' => 'required|string',
            // Add other parameters and rules as needed
        ]);
        Log::info('Webhook POST Request validated data:', $validated);
        // Check if user exists
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            // Create new user with "operatore" role
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt(str()->random(16)), // random password, since login is silent
            ]);
            $user->assignRole('Operatore');
        }

        // Log in the user
        Auth::login($user);

        // Redirect to Filament home (adjust route as needed)
        return redirect()->route('filament.admin.pages.dashboard');
    }
}