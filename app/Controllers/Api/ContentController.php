<?php

namespace App\Controllers\Api;

use App\Models\Content;
use App\Response\JsonResponse;
use Core\Auth\Auth;
use Core\Routing\Controller;
use Core\Http\Request;
use Core\Database\DataBase;
use Core\Facades\App;
use Core\Support\Time;

class ContentController extends Controller
{
    private const MAX_KEYS = 100;
    private const MAX_KEY_LENGTH = 50;
    private const MAX_VALUE_LENGTH = 5000;

    private $json;

    public function __construct(JsonResponse $json)
    {
        $this->json = $json;
    }

    /**
     * Every stored text for this invitation, as a flat key => value map.
     *
     * @return array
     */
    private function map(): array
    {
        $rows = Content::where('user_id', Auth::id())->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->content_key] = $row->content_value;
        }

        return $result;
    }


    /**
     * New keys are written in a single statement; inserting them one at a time
     * is what made a first save slow enough to time out.
     *
     * @param array<string, string> $items
     * @return void
     */
    private function insertMany(array $items): void
    {
        $now = Time::factory()->format('Y-m-d H:i:s');

        $rows = [];
        $bind = [];
        $i = 0;

        foreach ($items as $key => $value) {
            $rows[] = sprintf('(:u%1$d, :k%1$d, :v%1$d, :c%1$d, :c%1$d)', $i);
            $bind[':u' . $i] = Auth::id();
            $bind[':k' . $i] = $key;
            $bind[':v' . $i] = $value;
            $bind[':c' . $i] = $now;
            $i++;
        }

        /** @var DataBase $db */
        $db = App::get()->singleton(DataBase::class);
        $db->query(sprintf(
            'INSERT INTO contents (user_id, content_key, content_value, created_at, updated_at) VALUES %s',
            join(', ', $rows)
        ));

        foreach ($bind as $param => $value) {
            $db->bind($param, $value);
        }

        $db->execute();
    }

    /**
     * Updates every changed row in one statement. One UPDATE per field was
     * still ~28 sequential round trips when filling in a form that already had
     * rows, which is close enough to the function timeout to matter.
     *
     * @param array<int, string> $items id => value
     * @return void
     */
    private function updateMany(array $items): void
    {
        $rows = [];
        $bind = [];
        $i = 0;

        foreach ($items as $id => $value) {
            $rows[] = sprintf('(CAST(:i%1$d AS BIGINT), CAST(:v%1$d AS TEXT))', $i);
            $bind[':i' . $i] = $id;
            $bind[':v' . $i] = $value;
            $i++;
        }

        /** @var DataBase $db */
        $db = App::get()->singleton(DataBase::class);
        $db->query(sprintf(
            'UPDATE contents SET content_value = v.value FROM (VALUES %s) AS v(id, value) WHERE contents.id = v.id',
            join(', ', $rows)
        ));

        foreach ($bind as $param => $value) {
            $db->bind($param, $value);
        }

        $db->execute();
    }

    public function index(): JsonResponse
    {
        return $this->json->successOK($this->map());
    }

    public function update(Request $request): JsonResponse
    {
        $items = $request->get('contents');

        if (!is_array($items) || count($items) === 0) {
            return $this->json->errorBadRequest(['contents must be a non-empty object.']);
        }

        if (count($items) > self::MAX_KEYS) {
            return $this->json->errorBadRequest([sprintf('At most %d texts can be saved at once.', self::MAX_KEYS)]);
        }

        // Validate everything before writing anything, so a bad key cannot leave
        // the invitation half updated.
        foreach ($items as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-z0-9_]{1,' . self::MAX_KEY_LENGTH . '}$/', $key)) {
                return $this->json->errorBadRequest([sprintf('Invalid text name: %s.', is_string($key) ? $key : 'unknown')]);
            }

            if ($value !== null && !is_string($value)) {
                return $this->json->errorBadRequest([sprintf('%s must be text.', $key)]);
            }

            if (is_string($value) && strlen($value) > self::MAX_VALUE_LENGTH) {
                return $this->json->errorBadRequest([sprintf('%s is longer than %d characters.', $key, self::MAX_VALUE_LENGTH)]);
            }
        }

        // One read, then only the writes that actually change something. Doing a
        // select plus a write per key meant ~90 sequential round trips for a full
        // form, which at Supabase latency overran Vercel's 10s function limit.
        $existing = [];
        foreach (Content::where('user_id', Auth::id())->get() as $row) {
            $existing[$row->content_key] = $row;
        }

        $insert = [];
        $remove = [];
        $change = [];

        foreach ($items as $key => $value) {
            $value = is_string($value) ? trim($value) : '';
            $current = $existing[$key] ?? null;

            // Empty means "fall back to whatever the template says", so the row is
            // dropped rather than stored as an empty string.
            if ($value === '') {
                if ($current) {
                    $remove[] = intval($current->id);
                }

                continue;
            }

            if (!$current) {
                $insert[$key] = $value;
                continue;
            }

            if (strval($current->content_value) !== $value) {
                $change[intval($current->id)] = $value;
            }
        }

        if (count($remove) > 0) {
            Content::whereIn('id', $remove)->delete();
        }

        if (count($change) > 0) {
            $this->updateMany($change);
        }

        if (count($insert) > 0) {
            $this->insertMany($insert);
        }

        // Built from what was just written rather than re-reading the table: at
        // roughly half a second per round trip to the database, every avoidable
        // query is worth removing from this request.
        $result = [];
        foreach ($items as $key => $value) {
            $value = is_string($value) ? trim($value) : '';

            if ($value !== '') {
                $result[$key] = $value;
            }
        }

        foreach ($existing as $key => $row) {
            if (!array_key_exists($key, $items) && strval($row->content_value) !== '') {
                $result[$key] = $row->content_value;
            }
        }

        return $this->json->successOK($result);
    }
}
