<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function (Request $request) {
            $apiKey = $request->get('apiKey');
            $LIST_APIKEY = getenv('LIST_APIKEY');
            $apiKeys = explode(';', $LIST_APIKEY);
            if ($apiKey && in_array($apiKey, $apiKeys)) {
                $id = random_int(1,10);
                return new GenericUser(['id' => $id, 'name' => "user_$id"]);
            }
            return null;
        });
    }
}
