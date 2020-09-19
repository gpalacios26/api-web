<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\JwtAuth;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $checkToken = false;
        // Comprobar si el usuario esta autenticado
        $hash = $request->header('Authorization');
        if($hash){
            $jwtAuth = new JwtAuth();
            $checkToken = $jwtAuth->checkToken($hash);
        }
        // Si esta autenticado continua la ejecución
        if($checkToken){
            return $next($request);
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El usuario no esta autenticado'
            );

            return response()->json($data, 200);
        }
    }
}
