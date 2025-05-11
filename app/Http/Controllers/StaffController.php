<?php

namespace App\Http\Controllers;

use App\Http\OpenApi\OpenApiSpec;
use App\Http\Responses\PaginatedResponse;
use App\Http\Responses\PaginatedResponseTrait;
use App\Models\Staff;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'staff', description: 'Staff members')]
class StaffController
{
    use PaginatedResponseTrait;

    public function __construct(
        private \Illuminate\Contracts\Routing\ResponseFactory $responseFactory,
    ) {
    }

    /**
     * @return PaginatedResponse<Staff>
     */
    #[OA\Get(
        path: '/api/v1/staff',
        operationId: 'listStaff',
        description: 'Full list of staff available',
        security: OpenApiSpec::SECURITY,
        tags: ['staff'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'perPage',
                description: 'Number of items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of staff members',
                content: new OA\JsonContent(ref: '#/components/schemas/StaffListPaginated'),
            ),
        ],
    )]
    public function list(): PaginatedResponse
    {
        return $this->paginate(Staff::query());
    }

    #[OA\Get(
        path: '/api/v1/staff/{staffId}',
        operationId: 'getStaffById',
        description: 'Full info for the given staff member',
        security: OpenApiSpec::SECURITY,
        tags: ['staff'],
        parameters: [
            new OA\Parameter(
                name: 'staffId',
                description: 'Staff ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Staff member information',
                content: new OA\JsonContent(ref: '#/components/schemas/Staff'),
            ),
        ],
    )]
    public function get(Staff $staff): Staff
    {
        return $staff;
    }

    public function avatar(Staff $staff): \Illuminate\Http\Response
    {
        if (!$staff->hasImage()) {
            return $this->responseFactory->make(status: 404);
        }

        // @phpstan-ignore-next-line this one is not a real issue because hasImage already checks for nulls before, so this can only be string
        return $this->responseFactory->make($staff->image, 200, ['Content-Type' => $staff->mime_type]);
    }
}
