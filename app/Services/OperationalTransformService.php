<?php

namespace App\Services;

use App\Models\PageChange;

class OperationalTransformService
{
    /**
     * Transform a change operation against a list of concurrent operations
     * using operational transformation principles.
     */
    public function transformChange(PageChange $change, array $concurrentChanges): ?array
    {
        $transformedOperation = $change->operation_data;

        foreach ($concurrentChanges as $concurrentChange) {
            $transformedOperation = $this->transformAgainstOperation(
                $transformedOperation,
                $change->operation_type,
                $concurrentChange['operation_data'],
                $concurrentChange['operation_type']
            );

            // If transformation results in null, the operation is no longer valid
            if ($transformedOperation === null) {
                return null;
            }
        }

        return [
            'operation_type' => $change->operation_type,
            'operation_data' => $transformedOperation,
            'component_id' => $change->component_id,
            'user_id' => $change->user_id,
            'sequence_number' => $change->sequence_number,
        ];
    }

    /**
     * Transform one operation against another concurrent operation.
     */
    private function transformAgainstOperation(
        array $operation,
        string $operationType,
        array $concurrentOperation,
        string $concurrentOperationType
    ): ?array {
        // Handle different operation type combinations
        return match ([$operationType, $concurrentOperationType]) {
            ['add', 'add'] => $this->transformAddAgainstAdd($operation, $concurrentOperation),
            ['add', 'delete'] => $this->transformAddAgainstDelete($operation, $concurrentOperation),
            ['add', 'update'] => $this->transformAddAgainstUpdate($operation, $concurrentOperation),
            ['add', 'move'] => $this->transformAddAgainstMove($operation, $concurrentOperation),
            
            ['delete', 'add'] => $this->transformDeleteAgainstAdd($operation, $concurrentOperation),
            ['delete', 'delete'] => $this->transformDeleteAgainstDelete($operation, $concurrentOperation),
            ['delete', 'update'] => $this->transformDeleteAgainstUpdate($operation, $concurrentOperation),
            ['delete', 'move'] => $this->transformDeleteAgainstMove($operation, $concurrentOperation),
            
            ['update', 'add'] => $this->transformUpdateAgainstAdd($operation, $concurrentOperation),
            ['update', 'delete'] => $this->transformUpdateAgainstDelete($operation, $concurrentOperation),
            ['update', 'update'] => $this->transformUpdateAgainstUpdate($operation, $concurrentOperation),
            ['update', 'move'] => $this->transformUpdateAgainstMove($operation, $concurrentOperation),
            
            ['move', 'add'] => $this->transformMoveAgainstAdd($operation, $concurrentOperation),
            ['move', 'delete'] => $this->transformMoveAgainstDelete($operation, $concurrentOperation),
            ['move', 'update'] => $this->transformMoveAgainstUpdate($operation, $concurrentOperation),
            ['move', 'move'] => $this->transformMoveAgainstMove($operation, $concurrentOperation),
            
            default => $operation, // No transformation needed
        };
    }

    private function transformAddAgainstAdd(array $operation, array $concurrentOperation): array
    {
        // If adding to the same parent, adjust position
        if ($operation['parent_id'] === $concurrentOperation['parent_id']) {
            $position = $operation['position'] ?? 0;
            $concurrentPosition = $concurrentOperation['position'] ?? 0;
            
            if ($position >= $concurrentPosition) {
                $operation['position'] = $position + 1;
            }
        }
        
        return $operation;
    }

    private function transformAddAgainstDelete(array $operation, array $concurrentOperation): array
    {
        // If the parent was deleted, the add operation is invalid
        if ($operation['parent_id'] === $concurrentOperation['component_id']) {
            return null;
        }
        
        return $operation;
    }

    private function transformAddAgainstUpdate(array $operation, array $concurrentOperation): array
    {
        // Add operations are generally not affected by updates
        return $operation;
    }

    private function transformAddAgainstMove(array $operation, array $concurrentOperation): array
    {
        // If adding to a container that was moved, update parent reference
        if ($operation['parent_id'] === $concurrentOperation['component_id']) {
            $operation['parent_id'] = $concurrentOperation['new_parent_id'] ?? $operation['parent_id'];
        }
        
        return $operation;
    }

    private function transformDeleteAgainstAdd(array $operation, array $concurrentOperation): array
    {
        // Delete operations are generally not affected by adds
        return $operation;
    }

    private function transformDeleteAgainstDelete(array $operation, array $concurrentOperation): ?array
    {
        // If trying to delete the same component, the operation is redundant
        if ($operation['component_id'] === $concurrentOperation['component_id']) {
            return null;
        }
        
        return $operation;
    }

    private function transformDeleteAgainstUpdate(array $operation, array $concurrentOperation): array
    {
        // Delete takes precedence over update
        return $operation;
    }

    private function transformDeleteAgainstMove(array $operation, array $concurrentOperation): array
    {
        // Delete takes precedence over move
        return $operation;
    }

    private function transformUpdateAgainstAdd(array $operation, array $concurrentOperation): array
    {
        // Update operations are generally not affected by adds
        return $operation;
    }

    private function transformUpdateAgainstDelete(array $operation, array $concurrentOperation): ?array
    {
        // If the component being updated was deleted, the update is invalid
        if ($operation['component_id'] === $concurrentOperation['component_id']) {
            return null;
        }
        
        return $operation;
    }

    private function transformUpdateAgainstUpdate(array $operation, array $concurrentOperation): array
    {
        // If updating the same component, merge the changes
        if ($operation['component_id'] === $concurrentOperation['component_id']) {
            return $this->mergeUpdates($operation, $concurrentOperation);
        }
        
        return $operation;
    }

    private function transformUpdateAgainstMove(array $operation, array $concurrentOperation): array
    {
        // Update operations are generally not affected by moves
        return $operation;
    }

    private function transformMoveAgainstAdd(array $operation, array $concurrentOperation): array
    {
        // Adjust move position if adding to the same parent
        if ($operation['new_parent_id'] === $concurrentOperation['parent_id']) {
            $position = $operation['new_position'] ?? 0;
            $addPosition = $concurrentOperation['position'] ?? 0;
            
            if ($position >= $addPosition) {
                $operation['new_position'] = $position + 1;
            }
        }
        
        return $operation;
    }

    private function transformMoveAgainstDelete(array $operation, array $concurrentOperation): ?array
    {
        // If the component being moved was deleted, the move is invalid
        if ($operation['component_id'] === $concurrentOperation['component_id']) {
            return null;
        }
        
        // If the new parent was deleted, the move is invalid
        if ($operation['new_parent_id'] === $concurrentOperation['component_id']) {
            return null;
        }
        
        return $operation;
    }

    private function transformMoveAgainstUpdate(array $operation, array $concurrentOperation): array
    {
        // Move operations are generally not affected by updates
        return $operation;
    }

    private function transformMoveAgainstMove(array $operation, array $concurrentOperation): array
    {
        // If moving the same component, the later operation takes precedence
        if ($operation['component_id'] === $concurrentOperation['component_id']) {
            return null; // This move is superseded
        }
        
        // Adjust positions if moving to the same parent
        if ($operation['new_parent_id'] === $concurrentOperation['new_parent_id']) {
            $position = $operation['new_position'] ?? 0;
            $concurrentPosition = $concurrentOperation['new_position'] ?? 0;
            
            if ($position >= $concurrentPosition) {
                $operation['new_position'] = $position + 1;
            }
        }
        
        return $operation;
    }

    private function mergeUpdates(array $operation, array $concurrentOperation): array
    {
        // Merge update operations by combining their changes
        $mergedChanges = array_merge(
            $concurrentOperation['changes'] ?? [],
            $operation['changes'] ?? []
        );
        
        $operation['changes'] = $mergedChanges;
        
        return $operation;
    }

    /**
     * Check if two operations conflict with each other.
     */
    public function hasConflict(PageChange $change1, PageChange $change2): bool
    {
        // Same component operations always have potential for conflict
        if ($change1->component_id === $change2->component_id) {
            return true;
        }
        
        // Parent-child relationship conflicts
        if ($this->hasParentChildConflict($change1, $change2)) {
            return true;
        }
        
        // Position-based conflicts
        if ($this->hasPositionConflict($change1, $change2)) {
            return true;
        }
        
        return false;
    }

    private function hasParentChildConflict(PageChange $change1, PageChange $change2): bool
    {
        $data1 = $change1->operation_data;
        $data2 = $change2->operation_data;
        
        // Check if one operation affects the parent of another
        return ($data1['parent_id'] ?? null) === $change2->component_id ||
               ($data2['parent_id'] ?? null) === $change1->component_id;
    }

    private function hasPositionConflict(PageChange $change1, PageChange $change2): bool
    {
        $data1 = $change1->operation_data;
        $data2 = $change2->operation_data;
        
        // Check if operations affect the same position in the same parent
        return ($data1['parent_id'] ?? null) === ($data2['parent_id'] ?? null) &&
               ($data1['position'] ?? null) === ($data2['position'] ?? null) &&
               $data1['parent_id'] !== null;
    }
}
