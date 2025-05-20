<?php

namespace BattleSnake\Utils;

use Psr\Http\Message\ResponseInterface as Response;

class ResponseFormatter
{
    /**
     * Format a JSON response
     *
     * @param Response $response The response object
     * @param array $data The data to encode as JSON
     * @return Response
     */
    public static function formatJsonResponse(Response $response, array $data): Response
    {
        $payload = json_encode($data);
        $response->getBody()->write($payload);
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
