<?php

namespace App\Http\Controllers\Incidents;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incidents\TimelineCommentRequest;
use App\Http\Resources\Incidents\TimelineCommentResource;
use App\Models\Incident;
use App\Models\TimelineComment;
use Illuminate\Http\Response;

class TimelineCommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:INCIDENTS_UPDATE')->only(['store', 'update']);
    }

    public function store(TimelineCommentRequest $request, Incident $incident): TimelineCommentResource
    {
        $comment = $incident->comments()->create([
            'user_id' => auth()->id(),
            'body' => $request->body,
            'datetime' => $request->datetime
        ]);

        return new TimelineCommentResource($comment->load('user'));
    }

    public function update(TimelineCommentRequest $request, Incident $incident, TimelineComment $comment): TimelineCommentResource
    {
        abort_if($comment->incident_id !== $incident->id, Response::HTTP_NOT_FOUND);

        $comment->update([
            'body' => $request->body,
            'datetime' => $request->datetime
        ]);

        return new TimelineCommentResource($comment->load('user'));
    }
}
