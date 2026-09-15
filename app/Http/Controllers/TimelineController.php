<?php

namespace App\Http\Controllers;

use App\Models\ExternalUserAttendance;
use App\Models\TimelineComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TimelineController extends Controller
{
    // 全ユーザーの参加記録を投稿（登録）した順に並べたタイムライン。
    // 各カードにはアーティスト名・ツアー名・会場・日程・投稿者・星評価・コメントを表示する。
    // ?user_id= を指定すると、そのユーザーの投稿だけの「自分の投稿一覧」としても使える。
    // 初期表示（user_id未指定）はデフォルトで全員分（?user_id=all相当）。
    public function index(Request $request)
    {
        $query = ExternalUserAttendance::with([
            'externalUser',
            'dbSetlist.tour.artist',
            'userSetlist.concert.artist',
            'comments',
        ]);

        $userId = $request->input('user_id');
        $filterUser = null;
        if ($userId && $userId !== 'all') {
            $filterUser = \App\Models\ExternalUser::find($userId);
            $query->where('external_user_id', $userId);
        }

        $attendances = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('mypage.timeline.index', compact('attendances', 'filterUser'));
    }

    // 星評価（投稿者本人の自己評価）の更新（インライン編集からのAjax）
    public function update(Request $request, ExternalUserAttendance $attendance)
    {
        abort_unless($attendance->external_user_id === Auth::guard('external')->id(), 403);

        $validator = Validator::make($request->all(), [
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $attendance->update([
            'rating' => $request->input('rating') ?: null,
        ]);

        return response()->json([
            'message' => '評価を更新しました。',
            'rating' => $attendance->rating,
        ]);
    }

    // 他ユーザーの投稿へのコメント（誰でも複数件付けられる）
    public function storeComment(Request $request, ExternalUserAttendance $attendance)
    {
        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $comment = $attendance->comments()->create([
            'external_user_id' => Auth::guard('external')->id(),
            'body' => $request->input('body'),
        ]);
        $comment->load('externalUser');

        return response()->json([
            'message' => 'コメントを投稿しました。',
            'comment' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'user_name' => $comment->externalUser->name ?: 'ゲスト',
                'update_url' => route('mypage.timeline.comments.update', $comment),
                'delete_url' => route('mypage.timeline.comments.destroy', $comment),
            ],
        ]);
    }

    // コメントの編集（投稿者本人のみ）
    public function updateComment(Request $request, TimelineComment $comment)
    {
        abort_unless($comment->external_user_id === Auth::guard('external')->id(), 403);

        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $comment->update(['body' => $request->input('body')]);

        return response()->json([
            'message' => 'コメントを更新しました。',
            'body' => $comment->body,
        ]);
    }

    // コメントの削除（投稿者本人のみ）
    public function destroyComment(TimelineComment $comment)
    {
        abort_unless($comment->external_user_id === Auth::guard('external')->id(), 403);
        $comment->delete();

        return response()->json(['message' => 'コメントを削除しました。']);
    }
}
