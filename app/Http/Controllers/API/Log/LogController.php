<?php

namespace App\Http\Controllers\API\Log;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function logUser(Request $request, $user_id)
    {
        $log = Log::where('user_id', '=',$user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($log->isEmpty()) {
            return response()->json(['message' => 'No logs found for this user.'], 404);
        }

        return response()->json($log);
    }
}
