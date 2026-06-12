<?php

namespace App\Http\Controllers;

use App\Models\PushToken;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'platform' => 'required|in:android,ios',
        ]);

        PushToken::updateOrCreate(
            ['token' => $request->token],
            ['user_id' => $request->user()->id, 'platform' => $request->platform]
        );

        return response()->json(['ok' => true]);
    }
}
