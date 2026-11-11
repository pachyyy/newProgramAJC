<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Filter modal tersimpan per user (DBUSERFILTER di SML), dipakai public/js/user-filter.js.
// Kunci: USERNAME (DBFLPASS.username, sama seperti DBHEADERTABLE) + HREF (path halaman)
// + FILTERKEY (id modal). Isi FILTERJSON = { idSelect: value, ... }.
class UserFilterController extends Controller
{
    private const MAX_KEYS = 30;

    private const MAX_BYTES = 4096;

    public function get(Request $req)
    {
        $req->validate([
            'href' => ['required', 'string', 'max:150'],
            'key' => ['required', 'string', 'max:50'],
        ]);

        $row = DB::connection('SML')->select(
            'SELECT TOP 1 FILTERJSON FROM DBUSERFILTER WHERE USERNAME = :u AND HREF = :h AND FILTERKEY = :k',
            ['u' => Auth::user()->username, 'h' => $req->href, 'k' => $req->key]
        );

        $data = $row ? json_decode((string) $row[0]->FILTERJSON, true) : null;

        return response()->json(is_array($data) ? (object) $data : new \stdClass);
    }

    public function save(Request $req)
    {
        $req->validate([
            'href' => ['required', 'string', 'max:150'],
            'key' => ['required', 'string', 'max:50'],
            'filters' => ['required', 'string', 'max:'.self::MAX_BYTES],
        ]);

        $filters = json_decode($req->filters, true);
        // harus objek datar ({} di-decode jadi [] — boleh), bukan array JSON
        if (! is_array($filters) || (array_is_list($filters) && $filters !== []) || count($filters) > self::MAX_KEYS) {
            return response()->json(['message' => 'Format filter tidak valid'], 422);
        }
        foreach ($filters as $k => $v) {
            if (! preg_match('/^[A-Za-z0-9_-]{1,60}$/', (string) $k)
                || ! (is_string($v) || is_int($v) || is_float($v) || $v === null)) {
                return response()->json(['message' => 'Format filter tidak valid'], 422);
            }
        }

        $params = [
            'json' => json_encode((object) $filters, JSON_UNESCAPED_UNICODE),
            'u' => Auth::user()->username,
            'h' => $req->href,
            'k' => $req->key,
        ];

        $updated = DB::connection('SML')->update(
            'UPDATE DBUSERFILTER SET FILTERJSON = :json, UPDATED_AT = GETDATE()
             WHERE USERNAME = :u AND HREF = :h AND FILTERKEY = :k',
            $params
        );

        if ($updated === 0) {
            DB::connection('SML')->insert(
                'INSERT INTO DBUSERFILTER (USERNAME, HREF, FILTERKEY, FILTERJSON, UPDATED_AT)
                 VALUES (:u, :h, :k, :json, GETDATE())',
                $params
            );
        }

        return response()->json(['success' => true]);
    }
}
