<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\JwtAuth;
use App\Models\Post;

class PostController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => 
        ['index', 
        'show', 
        'getImage', 
        'getPostsByCategory', 
        'getPostsByUser']
        ]);
    }

    public function index(){
        // Obtener posts
        $posts = Post::all();

        if($posts && is_object($posts)){
            $posts->load('category');
            
            $data = array(
                'status' => 'success',
                'code' => 200,
                'posts' => $posts
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No hay datos disponibles'
            );
        }

        return response()->json($data, 200);
    }

    public function show($id){
        // Obtener post
        $post = Post::find($id);

        if($post && is_object($post)){
            $post->load('category');

            $data = array(
                'status' => 'success',
                'code' => 200,
                'post' => $post
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El post no existe'
            );
        }

        return response()->json($data, 200);
    }

    public function store(Request $request){
        // Recibir post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if(!empty($params) && !empty($params_array)){
            // Limpiar datos
            $params_array = array_map('trim', $params_array);

            // Validar los datos
            $validated = \Validator::make($params_array, [
                'category_id' => 'required',
                'title' => 'required',
                'content' => 'required',
                'image' => 'required'
            ]);

            if($validated->fails()){
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El post no se ha creado. Los datos no son validos',
                    'errors' => $validated->errors()
                );
            } else {
                // Obtener usuario autenticado
                $user = $this->getIdentity($request);
                // Crear el post
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params_array['category_id'];
                $post->title = $params_array['title'];
                $post->content = $params_array['content'];
                $post->image = $params_array['image'];
                $post->save();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El post se ha creado correctamente',
                    'post' => $post
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

    public function update($id, Request $request){
        // Recibir post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if(!empty($id) && !empty($params) && !empty($params_array)){
            // Validar los datos
            $validated = \Validator::make($params_array, [
                'category_id' => 'required',
                'title' => 'required',
                'content' => 'required',
                'image' => 'required'
            ]);

            if($validated->fails()){
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El post no se ha actualizado. Los datos no son validos',
                    'errors' => $validated->errors()
                );
            } else {
                //Comprobar que existe el registro
                $postExiste = Post::find($id);
                if($postExiste){
                    // Quitar los campos que no quiero actualizar
                    unset($params_array['id']);
                    unset($params_array['user_id']);
                    unset($params_array['created_at']);
                    unset($params_array['user']);

                    // Obtener usuario autenticado
                    $user = $this->getIdentity($request);
                    // Comprobar si el usuario tiene permisos
                    $postUpdate = Post::where('id',$id)->where('user_id',$user->sub)->first();
                    if($postUpdate){
                        // Actualizar post
                        $postUpdate->update($params_array);

                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'El post se ha actualizado correctamente'
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 400,
                            'message' => 'No tiene permiso para actualizar este post'
                        );
                    }
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 404,
                        'message' => 'No existe el post que quiere actualizar'
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

    public function destroy($id, Request $request){
        //Comprobar que existe el registro
        $postExiste = Post::find($id);
        if($postExiste){
            // Obtener usuario autenticado
            $user = $this->getIdentity($request);
            // Comprobar si el usuario tiene permisos
            $postDelete = Post::where('id',$id)->where('user_id',$user->sub)->first();
            if($postDelete){
                // Eliminar el registro
                $postDelete->delete();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El post se ha eliminado correctamente'
                );
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'No tiene permiso para eliminar este post'
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'No existe el post que quiere eliminar'
            );
        }

        return response()->json($data, 200);
    }

    private function getIdentity($request){
        // Obtener usuario autenticado
        $hash = $request->header('Authorization');
        $jwtAuth = new JwtAuth();
        $user = $jwtAuth->checkToken($hash, 'true');

        return $user;
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
            \Storage::disk('images')->put($image_name, \File::get($image));

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
        $isset = \Storage::disk('images')->exists($filename);
        if($isset){
            // Obtener la imagen
            $file = \Storage::disk('images')->get($filename);
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

    public function getPostsByCategory($id){
        // Obtener Posts por categoria
        $posts = Post::where('category_id',$id)->get();

        if($posts && is_object($posts)){
            $data = array(
                'status' => 'success',
                'code' => 200,
                'posts' => $posts
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No hay datos disponibles'
            );
        }

        return response()->json($data, 200);
    }

    public function getPostsByUser($id){
        // Obtener Posts por usuario
        $posts = Post::where('user_id',$id)->get();

        if($posts && is_object($posts)){
            $data = array(
                'status' => 'success',
                'code' => 200,
                'posts' => $posts
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No hay datos disponibles'
            );
        }

        return response()->json($data, 200);
    }
}
