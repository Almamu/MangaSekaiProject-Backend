<?php

namespace App\Http\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0', description: 'Backend for accessing and managing the Manga data hosted in a Manga Sekai Project instance', title: 'Manga Sekai Project Backend')]
#[OA\Components(securitySchemes: [
    new OA\SecurityScheme(securityScheme: 'Token', type: 'http', bearerFormat: 'JWT', scheme: 'bearer')]
)]
#[OA\Tag(name: 'admin', description: 'Admin-only endpoints')]
class OpenApiSpec
{
    const RECORDS_PER_PAGE = 20;
}
