<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileNotification;
use App\Support\AppTimezone;
use Illuminate\Http\Request;

class MobileNotificationController extends Controller
{
    public function index(Request $request)
    {
        $eid = $request->user()->EID;
        if (! $eid) {
            return response()->json(['success' => false, 'message' => 'Employee not linked.'], 403);
        }

        $limit = min((int) $request->query('limit', 100), 200);
        $rows = MobileNotification::query()
            ->where('eid', $eid)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $unread = MobileNotification::query()
            ->where('eid', $eid)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $unread,
                'notifications' => $rows->map(fn (MobileNotification $n) => $this->format($n)),
            ],
        ]);
    }

    public function markRead(Request $request, $id)
    {
        $eid = $request->user()->EID;
        $row = MobileNotification::query()
            ->where('id', (int) $id)
            ->where('eid', $eid)
            ->first();

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        if (! $row->read_at) {
            $row->read_at = AppTimezone::now();
            $row->save();
        }

        return response()->json([
            'success' => true,
            'data' => $this->format($row->fresh()),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $eid = $request->user()->EID;
        MobileNotification::query()
            ->where('eid', $eid)
            ->whereNull('read_at')
            ->update(['read_at' => AppTimezone::now()]);

        return response()->json(['success' => true]);
    }

    private function format(MobileNotification $n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'route' => $n->route,
            'reference_type' => $n->reference_type,
            'reference_id' => $n->reference_id,
            'read_at' => $n->read_at ? AppTimezone::toIso8601($n->read_at) : null,
            'is_read' => $n->read_at !== null,
            'created_at' => AppTimezone::toIso8601($n->created_at),
        ];
    }
}
