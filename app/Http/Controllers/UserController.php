<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\JwtAuth;
use App\Models\User;

class UserController extends Controller
{
    public function register(Request $request){
        // Recibir post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if(!empty($params) && !empty($params_array)){
            // Limpiar datos
            $params_array = array_map('trim', $params_array);

            // Validar los datos
            $validated = \Validator::make($params_array, [
                'name' => 'required',
                'surname' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required'
            ]);

            if($validated->fails()){
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha creado. Los datos no son validos',
                    'errors' => $validated->errors()
                );
            } else {
                // Validado correctamente se crea al usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->role = 'ROLE_USER';

                // Cifrar la contraseña
                $pwd = hash('sha256', $params->password);
                $user->password = $pwd;

                // Guardar usuario
                $user->save();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado correctamente',
                    'user' => $user
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            );
        }

        return response()->json($data, 200);
    }

    public function login(Request $request){
        $jwtAuth = new JwtAuth();

        // Recibir post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if(!empty($params) && !empty($params_array)){
            // Limpiar datos
            $params_array = array_map('trim', $params_array);

            // Validar los datos
            $validated = \Validator::make($params_array, [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if($validated->fails()){
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha identificado. Los datos no son validos',
                    'errors' => $validated->errors()
                );
            } else {
                // Validado correctamente se loguea el usuario
                $email = $params->email;
                $getToken = (isset($params->getToken)) ? $params->getToken : null;

                // Cifrar la contraseña
                $pwd = hash('sha256', $params->password);

                // Devolver el token o los datos de usuario
                if($getToken==null || $getToken=='false'){
                    $data = $jwtAuth->signup($email,$pwd);
                } else {
                    $data = $jwtAuth->signup($email,$pwd,$getToken);
                }
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            );
        }

        return response()->json($data, 200);
    }

    public function update(Request $request){
        // Recibir post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if(!empty($params) && !empty($params_array)){
            // Obtener usuario autenticado
            $hash = $request->header('Authorization');
            $jwtAuth = new JwtAuth();
            $user = $jwtAuth->checkToken($hash, 'true');

            // Validar los datos
            $validated = \Validator::make($params_array, [
                'name' => 'required',
                'surname' => 'required',
                'email' => 'required|email'
            ]);

            if($validated->fails()){
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha actualizado. Los datos no son validos',
                    'errors' => $validated->errors()
                );
            } else {
                //Comprobar que existe el registro
                $userOld = User::find($user->sub);
                if($userOld){
                    // Quitar los campos que no quiero actualizar
                    unset($params_array['id']);
                    unset($params_array['role']);
                    unset($params_array['password']);
                    unset($params_array['created_at']);
                    unset($params_array['remenber_token']);

                    // Actualizar usuario
                    $userUpdate = User::where('id',$user->sub)->update($params_array);

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'El usuario se ha actualizado correctamente'
                    );
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 404,
                        'message' => 'No existe el usuario que quiere actualizar'
                    );
                }
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            );
        }

        return response()->json($data, 200);
    }

    public function upload(Request $request){
        // Recoger los datos
        $image = $request->file('file');

        // Validar los datos
        $validated = \Validator::make($request->all(), [
            'file' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        // Guardar imagen
        if(!$image || $validated->fails()){
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Error al subir la imagen'
            );
        } else {
            $image_name = time().'-'.$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'La imagen se subio correctamente',
                'image' => $image_name
            );
        }

        return response()->json($data, 200);
    }

    public function getImage($filename){
        $isset = \Storage::disk('users')->exists($filename);
        if($isset){
            // Obtener la imagen
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'La imagen no existe',
            );

            return response()->json($data, 200);
        }
    }

    public function getUser($id){
        // Obtener el usuario
        $user = User::find($id);

        if($user && is_object($user)){
            $data = array(
                'status' => 'success',
                'code' => 200,
                'user' => $user
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El usuario no existe',
            );
        }

        return response()->json($data, 200);
    }
}