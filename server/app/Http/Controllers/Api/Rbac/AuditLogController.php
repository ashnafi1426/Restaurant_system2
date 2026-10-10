<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Models\RbacAuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = RbacAuditLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->has('action')) {
            $query->where('action', 'like', '%' . $request->query('action') . '%');
        }

        $logs = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ]
        ]);
    }
}

