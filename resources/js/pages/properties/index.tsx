import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Property = {
    id: number;
    name: string;
    address: string | null;
    incomes_count: number;
    expenses_count: number;
};

function PropertyDialog({ property }: { property?: Property }) {
    const [open, setOpen] = useState(false);
    const editing = Boolean(property);

    const form = useForm({
        name: property?.name ?? '',
        address: property?.address ?? '',
        notes: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const onSuccess = () => {
            setOpen(false);
            if (!editing) form.reset();
        };

        if (editing) {
            form.put(`/properties/${property!.id}`, { onSuccess, preserveScroll: true });
        } else {
            form.post('/properties', { onSuccess, preserveScroll: true });
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {editing ? (
                    <Button variant="ghost" size="icon" aria-label="Edit property">
                        <Pencil className="size-4" />
                    </Button>
                ) : (
                    <Button>
                        <Plus className="size-4" /> Add property
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit property' : 'Add property'}</DialogTitle>
                        <DialogDescription>
                            {editing
                                ? 'Update the details for this property.'
                                : 'Add a property to start tracking income and expenses.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="e.g. 123 Main St, Unit 2"
                            autoFocus
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="address">Address (optional)</Label>
                        <Input
                            id="address"
                            value={form.data.address}
                            onChange={(e) => form.setData('address', e.target.value)}
                        />
                        <InputError message={form.errors.address} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Notes (optional)</Label>
                        <Input
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                        />
                        <InputError message={form.errors.notes} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Save changes' : 'Add property'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function PropertiesIndex({ properties }: { properties: Property[] }) {
    const { propertyFilter } = usePage().props;
    const selectedId = propertyFilter?.selected ?? null;

    const destroy = (property: Property) => {
        if (confirm(`Delete "${property.name}"? This removes its income and expenses too.`)) {
            router.delete(`/properties/${property.id}`, { preserveScroll: true });
        }
    };

    return (
        <div className="flex flex-col gap-6 p-4">
            <Head title="Properties" />
            <div className="flex items-center justify-between">
                <Heading title="Properties" description="Your rental properties." />
                <PropertyDialog />
            </div>

            {properties.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No properties yet. Add your first one to get started.
                </p>
            ) : (
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Address</TableHead>
                                <TableHead className="text-right">Income items</TableHead>
                                <TableHead className="text-right">Expenses</TableHead>
                                <TableHead className="w-24 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {properties.map((property) => (
                                <TableRow
                                    key={property.id}
                                    data-state={property.id === selectedId ? 'selected' : undefined}
                                >
                                    <TableCell className="font-medium">
                                        <span className="inline-flex items-center gap-2">
                                            <Link
                                                href={`/properties/${property.id}`}
                                                className="hover:underline"
                                            >
                                                {property.name}
                                            </Link>
                                            {property.id === selectedId && (
                                                <Badge variant="secondary">Active</Badge>
                                            )}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {property.address ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {property.incomes_count}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {property.expenses_count}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <PropertyDialog property={property} />
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Delete property"
                                                onClick={() => destroy(property)}
                                            >
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}
        </div>
    );
}

PropertiesIndex.layout = {
    breadcrumbs: [{ title: 'Properties', href: '/properties' }],
};
