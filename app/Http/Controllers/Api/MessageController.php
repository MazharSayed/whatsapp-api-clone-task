<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Message",
 *     description="Message related operations"
 * )
 */
class MessageController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/messages/send",
     *     summary="Send a message to a chatroom",
     *     tags={"Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"chatroom_id"},
     *             @OA\Property(property="chatroom_id", type="integer", example=1),
     *             @OA\Property(property="message_text", type="string", example="Hello, World!"),
     *             @OA\Property(property="attachment", type="file")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"id", "chatroom_id", "user_id", "message_text"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="chatroom_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="message_text", type="string", example="Hello, World!"),
     *             @OA\Property(property="attachment_path", type="string", example="http://example.com/file.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Either message text or an attachment is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Something went wrong. Please try again later.")
     *         )
     *     )
     * )
     */
    public function send(Request $request)
    {
        try {
            $request->validate([
                'chatroom_id' => 'required|exists:chatrooms,id',
                'message_text' => 'nullable|string',
                'attachment' => 'nullable|file',
            ]);

            Log::info('Starting message send process');
            $path = null;
            $directory = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $mimeType = $file->getMimeType();

                if (str_contains($mimeType, 'image')) {
                    $directory = public_path('pictures');
                } elseif (str_contains($mimeType, 'video')) {
                    $directory = public_path('videos');
                } else {
                    Log::error('Unsupported file type');
                    return response()->json(['error' => 'Unsupported file type.'], 400);
                }

                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }

                $timestamp = time();
                $filename = $timestamp . '-' . $file->getClientOriginalName();
                $path = $directory . DIRECTORY_SEPARATOR . $filename;

                $file->move($directory, $filename);
            }

            if (!$request->message_text && !$path) {
                Log::error('No message text or attachment provided');
                return response()->json(['error' => 'Either message text or an attachment is required.'], 400);
            }

            $message = Message::create([
                'chatroom_id' => $request->chatroom_id,
                'user_id' => auth()->id(),
                'message_text' => $request->message_text,
                'attachment_path' => $path ? $this->getFileUrl($path) : null,
            ]);

            Log::info('Message created successfully:', ['message_id' => $message->id]);

            broadcast(new MessageSent($message))->toOthers();

            Log::info('MessageSent event broadcasted successfully', ['message_id' => $message->id]);

            return response()->json($message, 201);

        } catch (\Exception $e) {
            Log::error('Error sending message', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Something went wrong. Please try again later.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages/{id}",
     *     summary="Get messages from a chatroom",
     *     tags={"Message"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Chatroom ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of messages",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 required={"id", "chatroom_id", "user_id", "message_text"},
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="chatroom_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="message_text", type="string", example="Hello, World!"),
     *                 @OA\Property(property="attachment_path", type="string", example="http://example.com/file.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chatroom not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Chatroom not found.")
     *         )
     *     )
     * )
     */
    public function list($id)
    {
        return Message::where('chatroom_id', $id)->paginate(10);
    }
}
