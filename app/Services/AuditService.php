<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log a generic action.
     */
    public static function log(string $action, string $description, ?Model $subject = null, array $oldValues = [], array $newValues = [], array $metadata = [])
    {
        // Filter out sensitive data
        $hidden = ['password', 'remember_token'];
        
        $old = array_diff_key($oldValues, array_flip($hidden));
        $new = array_diff_key($newValues, array_flip($hidden));

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'description' => $description,
            'old_values' => empty($old) ? null : $old,
            'new_values' => empty($new) ? null : $new,
            'metadata' => empty($metadata) ? null : $metadata,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Automatically log creation of a model.
     */
    public static function logCreated(Model $model, string $description = '')
    {
        $desc = $description ?: 'Merekam data ' . class_basename($model) . ' baru';
        self::log('created', $desc, $model, [], $model->toArray());
    }

    /**
     * Automatically log update of a model, recording only changed attributes.
     */
    public static function logUpdated(Model $model, string $description = '')
    {
        $changes = $model->getChanges();
        $original = [];
        
        foreach (array_keys($changes) as $key) {
            if ($key === 'updated_at') continue;
            $original[$key] = $model->getOriginal($key);
        }

        // Only log if there are actual changes
        if (empty($original)) return;

        $desc = $description ?: 'Mengubah data ' . class_basename($model);
        self::log('updated', $desc, $model, $original, $changes);
    }

    /**
     * Automatically log deletion of a model.
     */
    public static function logDeleted(Model $model, string $description = '')
    {
        $desc = $description ?: 'Menghapus data ' . class_basename($model);
        self::log('deleted', $desc, $model, $model->toArray(), []);
    }
}
