<?php

namespace App\Modules\Sqs\Http\Controllers;

use App\Modules\Sqs\Http\Requests\PublishSqsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags SQS
 */
class SqsTestController extends Controller
{
    /**
     * Publicar mensagem na fila SQS
     *
     * Publica uma mensagem na fila SQS para ser processada de forma assíncrona.
     * Use este endpoint para simular o envio de atualizações de produto vindas
     * de sistemas externos.
     *
     * ### Tipos de job disponíveis
     *
     * | job_type              | Campos obrigatórios em `data`         |
     * |-----------------------|---------------------------------------|
     * | `update_stock`        | `stock` (integer)                     |
     * | `update_price`        | `price` (numeric)                     |
     * | `update_description`  | `description` (string)                |
     * | `update_images`       | `images` (array de URLs)              |
     * | `update_tags`         | `tags` (array de strings)             |
     *
     */
    public function publish(PublishSqsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        Queue::pushRaw(
            json_encode([
                'job'  => 'App\Infrastructure\Sqs\SqsJobDispatcher',
                'data' => $validated,
            ]),
            config('queue.connections.sqs.queue')
        );

        return response()->json([
            'message' => 'Mensagem publicada na fila SQS',
            'payload' => $validated,
        ], Response::HTTP_CREATED);
    }
}
