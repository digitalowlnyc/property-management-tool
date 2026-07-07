<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    /**
     * A user may only view/modify properties they own.
     */
    public function view(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    public function update(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->owns($user, $property);
    }

    protected function owns(User $user, Property $property): bool
    {
        return $property->user_id === $user->id;
    }
}
