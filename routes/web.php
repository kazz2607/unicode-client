<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('unicode/redirect', function (Request $request){
    $request->session()->put('state', $state = Str::random(40));

    $query = http_build_query([
        'client_id' => config('services.unicode.client_id'),
        'redirect_uri' => url(config('services.unicode.callback_url')),
        'response_type' => 'code',
        'scope' => '',
        'state' => $state,
        // 'prompt' => '', // "none", "consent", or "login"
    ]);

    return redirect('http://unicode.dev:8000/oauth/authorize?'.$query);

});

Route::get('unicode/callback', function(Request $request){
    if ($request->code){
        $code = $request->code;
        $state = $request->session()->pull('state');

        $response = Http::asForm()->post('http://unicode.dev:8000/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.unicode.client_id'),
            'client_secret' => config('services.unicode.client_secret'),
            'redirect_uri' => url(config('services.unicode.callback_url')),
            'code' => $code,
        ]);
     
        $response = $response->json();
        if (!empty($response['access_token'])){
            $accessToken = $response['access_token'];

            $user = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ])->get('http://unicode.dev:8000/api/user');
        }
    }
});
