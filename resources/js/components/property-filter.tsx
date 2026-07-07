import { router, usePage } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const ALL = 'all';

/**
 * Global property selector shown in the header. Sets a server-side session
 * filter, then reloads so every list/summary view scopes to the selection.
 */
export function PropertyFilter() {
    const { propertyFilter } = usePage().props;

    if (!propertyFilter || propertyFilter.properties.length === 0) {
        return null;
    }

    const value = propertyFilter.selected ? String(propertyFilter.selected) : ALL;

    const onChange = (next: string) => {
        router.put(
            '/selected-property',
            { property_id: next === ALL ? null : Number(next) },
            { preserveScroll: true, preserveState: false },
        );
    };

    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger className="h-9 w-48" aria-label="Filter by property">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>All properties</SelectItem>
                {propertyFilter.properties.map((property) => (
                    <SelectItem key={property.id} value={String(property.id)}>
                        {property.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
