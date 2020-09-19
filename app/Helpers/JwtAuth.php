<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class JwtAuth
{
    public $key;

    public function __construct(){
        $this->key = 'clave-secreta-758458934753987493';
    }
    
    public function signup($email, $password, $getToken = null){
    
        // Buscar que existe el usuario
        $user = User::where(
            array(
                'email' => $email,
                'password' => $password
            )
        )->first();

        // Comprobar si el usuario es un objeto
        $signup = false;
        if(is_object($user)){
            $signup = true;
        }

        // Generar el token del usuario con sus credenciales
        if($signup){
            $token = array(
                'sub' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'surname' => $user->surname,
                'role' => $user->role,
                'description' => $user->description,
                'image' => $user->image,
                'iat' => time(),
                'exp' => time() + (7*24*60*60)
            );

            $jwt = JWT::encode($token, $this->key, 'HS256');
            $decoded = JWT::decode($jwt, $this->key, array('HS256'));

            // Devolver el token o los datos de usuario
            if(is_null($getToken)){
                $data = $jwt;
            } else {
                $data = $decoded;
            }
        } else {
            // Devolver un error
            $data = array(
                'status' => 'error',
                'message' => 'Login incorrecto'
            );
        }

        return $data;
    }

    public function checkToken($jwt, $getIdentity = false){
        $auth = false;

        // Decodificar el token jwt
        try{
            $decoded = JWT::decode($jwt, $this->key, array('HS256'));
        } catch(\UnexpectedValuException $e){
            $auth = false;
        } catch(\DomainException $e){
            $auth = false;
        }

        // Validar autenticacion decoded
        if(isset($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        } else {
            $auth = false;
        }

        if($getIdentity){
            return $decoded;
        }

        return $auth;
    }
}