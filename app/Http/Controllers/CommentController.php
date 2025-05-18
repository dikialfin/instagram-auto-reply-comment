<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

class CommentController extends Controller
{
    private $geminiClient;

    public function __construct()
    {
        $this->geminiClient =new  Client(env('GEMINI_API_KEY'));
    }

    public function replyComments(array $comments)
    {
        try {

            Log::alert("*** MEMPROSES KOMENTAR ***");
            $graphBaseURL = "https://graph.instagram.com/v20.0/";
            $replyComments = [];

            foreach ($comments as $comment) {
                $generatedComment = $this->generateComment($comment['text'], $comment['username']);
                Log::alert("Generate Komentar Untuk : " . $comment['username'] . " || Generated Comment : " . $generatedComment);
                array_push($replyComments, [
                    "comment_id_audience" => $comment['comment_id'],
                    "username_audience" => $comment['username'],
                    "generated_comment" => $generatedComment
                ]);
            }

            Log::alert("*** MENGIRIM KOMENTAR ***");

            foreach ($replyComments as $comment) {
                $url = $graphBaseURL . $comment['comment_id_audience'] . "/replies";
                $response = Http::post($url, [
                    "access_token" => env("ACCESS_TOKEN"),
                    "message" => $comment['generated_comment']
                ]);

                if ($response->successful()) {
                    Log::alert("KOMENTAR KEPADA : " . $comment['username_audience'] . " -> BERHASIL DI KIRIM");
                } else {
                    throw new Exception("GAGAL MENGIRIM PESAN KEPADA : " . $comment['username_audience'], $response->status());
                }
            }
            return response("ok",200);
        } catch (\Throwable $th) {
            Log::error("CommentController::class -> replyComments() || " . $th->getMessage());
        }
    }

    private function generateComment(String $comment, String $username)
    {
        try {
            $prompt = "Kamu adalah asisten virtual cerdas bernama Christy yang diciptakan oleh Diki, programmer tampan dan intelek. Tugasmu adalah membalas komentar di akun Instagramku. Fokus pada interaksi yang positif dan hindari topik sensitif seperti sara dan politik. Batasi panjang karakter setiap balasanmu maksimal 2000 karakter. **Berikan hanya satu balasan yang spesifik dan personal.**Username : $username **Komentar: $comment ** awali komentar kamu dengan @username";

            $response = $this->geminiClient->generativeModel(ModelName::GEMINI_1_5_FLASH)->generateContent(
                new TextPart($prompt)
            );

            return $response->text();
        } catch (\Throwable $th) {
            Log::error("CommentController::class -> generateComment() || " . $th->getMessage());
        }
    }
}
