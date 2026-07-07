<?php

namespace App\Support;

use App\Models\User;

class PropertyFilter
{
    /**
     * Session key holding the globally selected property id (null = all).
     */
    public const SESSION_KEY = 'selected_property_id';

    /**
     * The currently selected property id for this user, or null for "all".
     *
     * Falls back to null (all) when nothing is selected or the stored id no
     * longer belongs to the user (e.g. the property was deleted).
     */
    public static function selectedId(?User $user): ?int
    {
        if ($user === null) {
            return null;
        }

        $id = session(self::SESSION_KEY);
        if ($id === null) {
            return null;
        }

        $id = (int) $id;

        return $user->properties()->whereKey($id)->exists() ? $id : null;
    }
}
