<?php

namespace App\Traits;

use App\Models\GraduateAuditLog;
use Illuminate\Support\Facades\Auth;

trait HasGraduateAuditLog
{
    public function logAuditTrail($action, $description, $fieldName = null, $oldValue = null, $newValue = null, $metadata = [])
    {
        GraduateAuditLog::create([
            'graduate_id' => $this->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public function logFieldChange($fieldName, $oldValue, $newValue, $action = 'updated')
    {
        $description = "Updated {$fieldName}";
        if ($oldValue !== $newValue) {
            $description .= " from '{$oldValue}' to '{$newValue}'";
        }

        $this->logAuditTrail($action, $description, $fieldName, $oldValue, $newValue);
    }

    public function logEmploymentUpdate($oldStatus, $newStatus, $jobDetails = [])
    {
        $description = "Employment status changed from '{$oldStatus}' to '{$newStatus}'";
        if (! empty($jobDetails)) {
            $details = [];
            if (isset($jobDetails['job_title'])) {
                $details[] = "Job: {$jobDetails['job_title']}";
            }
            if (isset($jobDetails['company'])) {
                $details[] = "Company: {$jobDetails['company']}";
            }
            if (! empty($details)) {
                $description .= ' ('.implode(', ', $details).')';
            }
        }

        $this->logAuditTrail('employment_updated', $description, 'employment_status', $oldStatus, $newStatus, $jobDetails);
    }

    public function logPrivacyUpdate($changes)
    {
        $description = 'Privacy settings updated: '.implode(', ', array_keys($changes));
        $this->logAuditTrail('privacy_updated', $description, null, null, null, $changes);
    }
}
