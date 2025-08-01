<?php

namespace App\Policies;

use App\Models\TaxReceipt;
use App\Models\User;

class TaxReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager', 'accountant']);
    }

    public function view(User $user, TaxReceipt $taxReceipt): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager', 'accountant']) || 
               $user->id === $taxReceipt->donor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager', 'accountant']);
    }

    public function update(User $user, TaxReceipt $taxReceipt): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager', 'accountant']);
    }

    public function delete(User $user, TaxReceipt $taxReceipt): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager']);
    }
}