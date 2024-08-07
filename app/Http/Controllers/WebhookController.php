<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private CommentController $commentController;

    public function __construct()
    {
        $this->commentController = new CommentController;
    }

    public function subscribeWebhook()
    {
        try {
            $igId = env("IG_ID");
            $accessToken = env("ACCESS_TOKEN");
            $listOfWebhookFields = env("LIST_OF_WEBHOOK_FIELDS");
            $response = Http::post(
                "https://graph.instagram.com/v20.0/$igId/subscribed_apps?subscribed_fields=$listOfWebhookFields&access_token=$accessToken"
            );
            if ($response->getContent("success")) {
                Log::alert("BERHASIL SUBSCRIBE WEBHOOK INSTAGRAM");
                return response([
                    "message" => "berhasil subscribe instagram webhook"
                ], 200);
            }

            if ($response->failed()) {
                Log::error("WebhookController::class -> subscribeWebhook() || GAGAL SUBSCRIBE WEBHOOK => " . $response->getStatusCode());
            }
        } catch (\Throwable $th) {
            Log::error("WebhookController::class -> subscribeWebhook() || " . $th->getMessage());
        }
    }

    public function verificationRequestHandler(Request $request)
    {
        try {
            if (
                $request->input("hub_mode") == "subscribe" &&
                $request->input("hub_verify_token") == env("VERIFY_TOKEN")
            ) {
                Log::alert("PROSES VERIFICATION REQUEST BERHASIL");
                return response($request->input("hub_challenge"), 200);
            }
            return response('waduh gagal brok!!', 403);
        } catch (\Throwable $th) {
            Log::error("WebhookController::class -> verificationRequestHandler() || " . $th->getMessage());
        }
    }

    public function eventNotificationsHandler(Request $request)
    {
        try {
            
            Log::alert("***NOTIFIKASI KOMENTAR DITERIMA***");
            $requestContent = json_decode($request->getContent(), true);
            $comments = [];

            $audienceComments = $requestContent['entry'][0]['changes'];

            foreach ($audienceComments as $audienceComment) {
                if ($audienceComment['field'] == "comments") {
                    if ($audienceComment['value']['from']['id'] != env("IG_ID")) {
                        array_push($comments, [
                            "comment_id" => $audienceComment['value']['id'],
                            "username" => $audienceComment['value']['from']['username'],
                            "text" => $audienceComment['value']['text'],
                        ]);
                    }
                }
            }
            
            return $this->commentController->replyComments($comments);
            
        } catch (\Throwable $th) {
            Log::error("WebhookController::class -> eventNotificationsHandler() || " . $th->getMessage());
        }
    }
}
