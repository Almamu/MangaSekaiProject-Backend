<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'staff', description: 'Staff members')]
class StaffController
{
    // @phpstan-ignore-next-line
    #[OA\Get(
        path: '/api/v1/staff',
        operationId: 'listStaff',
        description: 'Full list of staff available',
        security: [
            ['Token' => []],
        ],
        tags: ['staff'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of staff members',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/StaffListReadDto',
                    collectionFormat: 'multi'
                )
            ),
        ]
    )]
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Staff>
     */
    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return Staff::all()->makeHidden(['series']);
    }

    #[OA\Get(
        path: '/api/v1/staff/{staffId}',
        operationId: 'getStaffById',
        description: 'Full info for the given staff member',
        security: [
            ['Token' => []],
        ],
        tags: ['staff'],
        parameters: [
            new OA\Parameter(name: 'staffId', description: 'Staff ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Staff member information',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/StaffReadDto'
                )
            ),
        ]
    )]
    public function get(Staff $staff): Staff
    {
        return $staff;
    }

    public function avatar(Staff $serie): \Illuminate\Http\Response
    {
        if (! $serie->hasImage()) {
            return response(status: 404);
        }

        return response($serie->image, 200, ['Content-Type' => $serie->mime_type]);
    }
}
