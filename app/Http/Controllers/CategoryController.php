<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Category;

class CategoryController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    }
    
    public function index(){
        // Obtener categorias
        $categories = Category::all();

        if($categories && is_object($categories)){
            $data = array(
                'status' => 'success',
                'code' => 200,
                'categories' => $categories
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
        // Obtener categoria
        $category = Category::find($id);

        if($category && is_object($category)){
            $data = array(
                'status' => 'success',
                'code' => 200,
                'category' => $category
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'La categoria no existe'
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
                'name' => 'required'
            ]);

            if($validated->fails()){
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'La categoria no se ha creado. Los datos no son validos',
                    'errors' => $validated->errors()
                );
            } else {
                // Validado correctamente se crea la categoria
                $category = new Category();
                $category->name = $params_array['name'];
                $category->save();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'La categoria se ha creado correctamente',
                    'category' => $category
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
                'name' => 'required'
            ]);

            if($validated->fails()){
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'La categoria no se ha actualizado. Los datos no son validos',
                    'errors' => $validated->errors()
                );
            } else {
                //Comprobar que existe el registro
                $categoryOld = Category::find($id);
                if($categoryOld){
                    // Quitar los campos que no quiero actualizar
                    unset($params_array['id']);
                    unset($params_array['created_at']);

                    // Actualizar categoria
                    $categoryUpdate = Category::where('id',$id)->update($params_array);

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'La categoria se ha actualizado correctamente'
                    );
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 404,
                        'message' => 'No existe la categoria que quiere actualizar'
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

    public function destroy($id){
        //Comprobar que existe el registro
        $category = Category::find($id);
        if($category){
            // Eliminar el registro
            $category->delete();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'La categoria se ha eliminado correctamente'
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'No existe la categoria que quiere eliminar'
            );
        }

        return response()->json($data, 200);
    }
}
