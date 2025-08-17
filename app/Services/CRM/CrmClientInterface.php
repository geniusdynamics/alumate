<?php

namespace App\Services\CRM;

interface CrmClientInterface
{
    /**
     * Test the connection to the CRM
     */
    public function testConnection(): array;

    /**
     * Create a new lead in the CRM
     */
    public function createLead(array $data): array;

    /**
     * Update an existing lead in the CRM
     */
    public function updateLead(string $crmId, array $data): array;

    /**
     * Get a lead from the CRM
     */
    public function getLead(string $crmId): array;

    /**
     * Delete a lead from the CRM
     */
    public function deleteLead(string $crmId): bool;

    /**
     * Search for leads in the CRM
     */
    public function searchLeads(array $criteria): array;

    /**
     * Get available fields from the CRM
     */
    public function getAvailableFields(): array;
}
