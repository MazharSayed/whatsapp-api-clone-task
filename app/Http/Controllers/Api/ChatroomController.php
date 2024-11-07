<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chatroom;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="WhatsApp API Clone",
 *     version="1.0.0",
 *     description="This is the API documentation for the WhatsApp clone application.",
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 */

class ChatroomController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/chatrooms",
     *     summary="Create a new chatroom",
     *     tags={"Chatroom"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "max_members"},
     *             @OA\Property(property="name", type="string", example="General Chat"),
     *             @OA\Property(property="max_members", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Chatroom created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="General Chat"),
     *             @OA\Property(property="max_members", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation errors occurred")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'max_members' => 'required|integer|min:1',
        ]);

        $chatroom = Chatroom::create($request->all());

        return response()->json($chatroom, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/chatrooms",
     *     summary="Get a list of chatrooms",
     *     tags={"Chatroom"},
     *     @OA\Response(
     *         response=200,
     *         description="List of chatrooms",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="General Chat"),
     *                 @OA\Property(property="max_members", type="integer", example=50)
     *             )
     *         )
     *     )
     * )
     */
    public function list()
    {
        return Chatroom::paginate(10);
    }

    /**
     * @OA\Post(
     *     path="/api/chatrooms/{id}/join",
     *     summary="Join a chatroom",
     *     tags={"Chatroom"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the chatroom",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully joined the chatroom",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You have successfully joined the chatroom")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Chatroom is full",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Chatroom is full")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User is already in the chatroom",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="You are already in this chatroom")
     *         )
     *     )
     * )
     */
    public function join($id)
    {
        $chatroom = Chatroom::findOrFail($id);

        if ($chatroom->users()->count() >= $chatroom->max_members) {
            return response()->json(['error' => 'Chatroom is full'], 403);
        }

        if ($chatroom->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['error' => 'You are already in this chatroom'], 400);
        }

        $chatroom->users()->attach(auth()->id());

        return response()->json(['message' => 'You have successfully joined the chatroom']);
    }

    /**
     * @OA\Post(
     *     path="/api/chatrooms/{id}/leave",
     *     summary="Leave a chatroom",
     *     tags={"Chatroom"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the chatroom",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully left the chatroom",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Left successfully")
     *         )
     *     )
     * )
     */
    public function leave($id)
    {
        $chatroom = Chatroom::findOrFail($id);
        $chatroom->users()->detach(auth()->id());

        return response()->json(['message' => 'Left successfully']);
    }
}
