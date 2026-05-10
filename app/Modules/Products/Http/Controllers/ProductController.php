<?php

namespace App\Modules\Products\Http\Controllers;

use App\Domain\Products\Actions\CreateProductAction;
use App\Modules\Products\Http\Requests\CreateProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Produtos
 */
class ProductController extends Controller
{
    /**
     * Cadastrar produto
     *
     * Cria um novo produto no sistema. O SKU deve ser único.
     */
    public function store(CreateProductRequest $request, CreateProductAction $createProductAction): JsonResponse
    {
        try {
            $product = $createProductAction->execute($request->validated());

            return response()->json([
                'message' => 'Produto cadastrado com sucesso',
                'payload' => $product,
            ], Response::HTTP_CREATED);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_CONFLICT);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro interno ao cadastrar produto.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
