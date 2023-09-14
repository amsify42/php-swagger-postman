<?php

namespace Amsify42\TestSP\Annotations;

use Amsify42\TestSP\Annotations\Base;

/**
 * @OA\Post(
 *     path="/users",
 *     tags={"Users"},
 *     summary="Users - Create",
 *     description="Users - Create",
 *     operationId="usersCreate",
 *      @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *               @OA\Property(
 *                     description="First name of the user",
 *                     property="first_name",
 *                     type="string"
 *                 ),
 *               @OA\Property(
 *                     description="Last name of the user",
 *                     property="last_name",
 *                     type="string"
 *                 ),
 *                 @OA\Property(
 *                     description="Email address",
 *                     property="email",
 *                     type="string"
 *                 ),
 *                 @OA\Property(
 *                    property="is_active",
 *                    description="Is Active",
 *                    type="string",
 *                    enum={"1", "0"},
 *                    example="1"
 *                 ),
 *                 required={"first_name","email"}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response="400",
 *         description="Failed"
 *     )
 * )
 * */

/**
 * @OA\Post(
 *     path="/users/{userId}",
 *     tags={"Users"},
 *     summary="Users - Update",
 *     description="Users - Update",
 *     operationId="usersUpdate",
 *     @OA\Parameter(
 *         name="userId",
 *         in="path",
 *         description="Unique id of the user",
 *         required=true,
 *         @OA\Schema(
 *             type="integer",
 *             format="int64",
 *         )
 *     ),
 *      @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *               @OA\Property(
 *                     description="First name of the user",
 *                     property="first_name",
 *                     type="string"
 *                 ),
 *               @OA\Property(
 *                     description="Last name of the user",
 *                     property="last_name",
 *                     type="string"
 *                 ),
 *                 @OA\Property(
 *                     description="Email address",
 *                     property="email",
 *                     type="string"
 *                 ),
 *                 @OA\Property(
 *                    property="is_active",
 *                    description="Is Active",
 *                    type="string",
 *                    enum={"1", "0"},
 *                    example="1"
 *                 ),
 *                 required={}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response="400",
 *         description="Failed"
 *     )
 * )
 * */

class Users extends Base
{

}