<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimelineCommentRequest;
use App\Http\Resources\TimelineCommentResource;
use App\Models\Incident;
use App\Models\TimelineComment;
use Illuminate\Http\Response;

class TimelineCommentController extends Controller
{
    public function store(TimelineCommentRequest $request, Incident $incident): TimelineCommentResource
    {
        $comment = $incident->comments()->create([
            'user_id' => auth()->id(),
            'body' => $request->body,
            'created_at' => $request->created_at ?? now()
        ]);

        return new TimelineCommentResource($comment->load('user'));
    }

    public function update(TimelineCommentRequest $request, Incident $incident, TimelineComment $comment): TimelineCommentResource
    {
        abort_if($comment->incident_id !== $incident->id, Response::HTTP_NOT_FOUND);

        $comment->update([
            'body' => $request->body,
            'created_at' => $request->created_at
        ]);

        return new TimelineCommentResource($comment->load('user'));
    }
}
