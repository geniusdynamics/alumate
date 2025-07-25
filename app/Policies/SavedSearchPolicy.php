<?php

namespace App\Policies;

use App\Models\SavedSearch;
use App\Models\User;

class SavedSearchPolicy
{
    public function view(User $user, SavedSearch $savedSearch)
    {
        return $user->id === $savedSearch->user_id;
    }

    public function update(User $user, SavedSearch $savedSearch)
    {
        return $user->id === $savedSearch->user_id;
    }

    public function delete(User $user, SavedSearch $savedSearch)
    {
        return $user->id === $savedSearch->user_id;
    }
}