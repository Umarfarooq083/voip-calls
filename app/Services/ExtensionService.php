<?php

namespace App\Services;

use App\Models\Extension;
use App\Models\PjsipAor;
use App\Models\PjsipAuth;
use App\Models\PjsipEndpoint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExtensionService
{
    public function create(array $data): Extension
    {
        return DB::transaction(function () use ($data) {
            $extension = Extension::create($data);

            $this->createPjsipRecords($extension);

            return $extension;
        });
    }

    public function update(Extension $extension, array $data): Extension
    {
        return DB::transaction(function () use ($extension, $data) {
            $extension->update($data);

            $this->updatePjsipRecords($extension);

            return $extension->fresh();
        });
    }

    public function delete(Extension $extension): bool
    {
        return DB::transaction(function () use ($extension) {
            $this->deletePjsipRecords($extension);

            return $extension->delete();
        });
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Extension::query();

        if (isset($filters['active'])) {
            $query->active();
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('extension', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function find(int $id): ?Extension
    {
        return Extension::find($id);
    }

    public function findByExtension(string $extension): ?Extension
    {
        return Extension::where('extension', $extension)->first();
    }

    protected function createPjsipRecords(Extension $extension): void
    {
        $extensionId = $extension->extension;

        $auth = PjsipAuth::create([
            'id' => $extensionId,
            'auth_type' => 'userpass',
            'auth_realm' => 'asterisk',
            'auth_username' => $extensionId,
            'auth_password' => $extension->secret,
        ]);

        $aor = PjsipAor::create([
            'id' => $extensionId,
            'aor_max_contacts' => 1,
            'aor_qualify_frequency' => 60,
        ]);

        PjsipEndpoint::create([
            'id' => $extensionId,
            'transport' => $extension->transport ?? 'udp',
            'context' => $extension->context,
            'disallow' => 'all',
            'allow' => 'ulaw',
            'auth' => $extensionId,
            'aors' => $extensionId,
            'auth_type' => 'userpass',
            'callerid' => $extension->caller_id_name ? "\"{$extension->caller_id_name}\" <{$extension->caller_id_num}>" : null,
            'timeout' => $extension->timeout ?? 30,
            'description' => $extension->display_name ?? "Extension {$extensionId}",
        ]);
    }

    protected function updatePjsipRecords(Extension $extension): void
    {
        $extensionId = $extension->extension;

        if ($extension->secret) {
            PjsipAuth::where('id', $extensionId)->update([
                'auth_password' => $extension->secret,
            ]);
        }

        PjsipEndpoint::where('id', $extensionId)->update([
            'context' => $extension->context,
            'transport' => $extension->transport ?? 'udp',
            'callerid' => $extension->caller_id_name ? "\"{$extension->caller_id_name}\" <{$extension->caller_id_num}>" : null,
            'timeout' => $extension->timeout ?? 30,
        ]);

        PjsipAor::where('id', $extensionId)->update([
            'aor_max_contacts' => 1,
        ]);
    }

    protected function deletePjsipRecords(Extension $extension): void
    {
        $extensionId = $extension->extension;

        PjsipEndpoint::where('id', $extensionId)->delete();
        PjsipAor::where('id', $extensionId)->delete();
        PjsipAuth::where('id', $extensionId)->delete();
    }
}
