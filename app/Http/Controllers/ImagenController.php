<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Image;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ImagenController extends Controller
{
    public function store(Request $request, $productId)
    {
        /**
         * 1️⃣ Validar existencia del producto
         */
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        /**
         * 2️⃣ Validar request
         */
        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'tipo'   => 'required|in:principal,secundaria',
        ]);

        DB::beginTransaction();

        try {

            /**
             * 3️⃣ Si es imagen PRINCIPAL → eliminar la anterior
             */
            if ($request->tipo === 'principal' && $product->imagen_path) {
                $publicId = $this->getPublicIdFromUrl($product->imagen_path);
                Cloudinary::destroy($publicId, ['invalidate' => true]);
            }

            /**
             * 4️⃣ Subir imagen a Cloudinary
             */
            $upload = Cloudinary::upload(
                $request->file('imagen')->getRealPath(),
                [
                    'folder' => "productos/{$product->id}",
                    'resource_type' => 'image',
                ]
            );

            $url = $upload->getSecurePath();

            /**
             * 5️⃣ Guardar según tipo
             */
            if ($request->tipo === 'principal') {

                // 🔹 Actualizar producto
                $product->imagen_path = $url;
                $product->save();

            } else {

                // 🔹 Crear imagen secundaria
                Image::create([
                    'producto_id' => $product->id,
                    'ruta'        => $url,
                ]);
            }

            DB::commit();

            /**
             * 6️⃣ Respuesta
             */
            return response()->json([
                'message' => 'Imagen procesada correctamente',
                'url'     => $url,
                'tipo'    => $request->tipo
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Error al procesar la imagen',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🧩 Extraer public_id desde URL de Cloudinary
     */
    private function getPublicIdFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        // Eliminar todo antes de /upload/
        $path = preg_replace('#^.*/upload/#', '', $path);

        // Eliminar versión v123456789
        $path = preg_replace('#^v\d+/#', '', $path);

        // Eliminar extensión
        $path = preg_replace('/\.[^.]+$/', '', $path);

        return $path;
    }

    public function show(int $productId): JsonResponse
    {
        /**
         * 1️⃣ Buscar producto con sus imágenes secundarias
         */
        $product = Product::with('imagenes')->find($productId);

        if (!$product) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        /**
         * 2️⃣ Construir respuesta limpia para frontend
         */
        return response()->json([
            'producto_id' => $product->id,
            'nombre' => $product->nombre,
            'imagen_principal' => $product->imagen_path,
            'imagenes_secundarias' => $product->imagenes->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => $img->ruta,
                ];
            }),
        ], 200);
    }

    /**
     * Eliminar una imagen secundaria
     */
    public function destroy(int $imageId): JsonResponse
    {
        /**
         * 1️⃣ Buscar imagen secundaria
         */
        $image = Image::find($imageId);

        if (!$image) {
            return response()->json([
                'message' => 'Imagen secundaria no encontrada'
            ], 404);
        }

        DB::beginTransaction();

        try {
            /**
             * 2️⃣ Eliminar de Cloudinary
             */
            $publicId = $this->getPublicIdFromUrl($image->ruta);
            Cloudinary::destroy($publicId);

            /**
             * 3️⃣ Eliminar registro de la BD
             */
            $image->delete();

            DB::commit();

            return response()->json([
                'message' => 'Imagen secundaria eliminada correctamente'
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Error al eliminar la imagen secundaria',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
