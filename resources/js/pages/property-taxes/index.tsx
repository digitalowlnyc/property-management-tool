import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';

type TaxRow = {
    id: number;
    year: number;
    amount: number;
    estimated: boolean;
    bill_date: string | null;
    change_abs: number | null;
    change_pct: number | null;
};

type PropertyTaxes = {
    id: number;
    name: string;
    taxes: TaxRow[];
};

type Props = {
    properties: PropertyTaxes[];
    currentYear: number;
};

// Rising taxes cost more, so increases read red and decreases read green.
function changeClass(value: number) {
    if (value > 0) return 'text-destructive';
    if (value < 0) return 'text-emerald-600 dark:text-emerald-500';
    return 'text-muted-foreground';
}

function signedMoney(value: number) {
    return `${value > 0 ? '+' : ''}${formatMoney(value)}`;
}

function signedPct(value: number) {
    return `${value > 0 ? '+' : ''}${value.toFixed(2)}%`;
}

function TaxDialog({
    properties,
    currentYear,
    record,
}: {
    properties: PropertyTaxes[];
    currentYear: number;
    record?: {
        id: number;
        property_id: number;
        year: number;
        amount: number;
        estimated: boolean;
        bill_date: string | null;
    };
}) {
    const [open, setOpen] = useState(false);
    const editing = Boolean(record);

    const form = useForm({
        property_id: record?.property_id?.toString() ?? properties[0]?.id.toString() ?? '',
        year: (record?.year ?? currentYear).toString(),
        amount: record?.amount?.toString() ?? '',
        estimated: record?.estimated ?? false,
        bill_date: record?.bill_date ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const onSuccess = () => {
            setOpen(false);
            if (!editing) form.reset('amount', 'bill_date');
        };
        if (editing) {
            form.put(`/property-taxes/${record!.id}`, { onSuccess, preserveScroll: true });
        } else {
            form.post('/property-taxes', { onSuccess, preserveScroll: true });
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {editing ? (
                    <Button variant="ghost" size="icon" aria-label="Edit tax record">
                        <Pencil className="size-4" />
                    </Button>
                ) : (
                    <Button disabled={properties.length === 0}>
                        <Plus className="size-4" /> Add tax record
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit tax record' : 'Add tax record'}</DialogTitle>
                        <DialogDescription>
                            Record the property tax billed for a given year.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label>Property</Label>
                        <Select
                            value={form.data.property_id}
                            onValueChange={(v) => form.setData('property_id', v)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select a property" />
                            </SelectTrigger>
                            <SelectContent>
                                {properties.map((p) => (
                                    <SelectItem key={p.id} value={p.id.toString()}>
                                        {p.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.property_id} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="year">Year</Label>
                            <Input
                                id="year"
                                type="number"
                                min="1900"
                                max="2100"
                                step="1"
                                value={form.data.year}
                                onChange={(e) => form.setData('year', e.target.value)}
                            />
                            <InputError message={form.errors.year} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="amount">Tax amount ($)</Label>
                            <Input
                                id="amount"
                                type="number"
                                step="0.01"
                                min="0"
                                value={form.data.amount}
                                onChange={(e) => form.setData('amount', e.target.value)}
                                placeholder="e.g. 4200"
                            />
                            <InputError message={form.errors.amount} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="bill_date">Tax bill date (optional)</Label>
                        <Input
                            id="bill_date"
                            type="date"
                            value={form.data.bill_date}
                            onChange={(e) => form.setData('bill_date', e.target.value)}
                        />
                        <p className="text-xs text-muted-foreground">
                            Used to accrue the tax over the 12 months before it's due.
                        </p>
                        <InputError message={form.errors.bill_date} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.estimated}
                            onCheckedChange={(checked) =>
                                form.setData('estimated', checked === true)
                            }
                        />
                        Estimated (not yet the final billed amount)
                    </label>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Save changes' : 'Add tax record'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function PropertyTaxesIndex({ properties, currentYear }: Props) {
    const destroy = (row: TaxRow) => {
        if (confirm(`Delete the ${row.year} tax record?`)) {
            router.delete(`/property-taxes/${row.id}`, { preserveScroll: true });
        }
    };

    return (
        <div className="flex flex-col gap-6 p-4">
            <Head title="Property Taxes" />
            <div className="flex items-center justify-between">
                <Heading
                    title="Property Taxes"
                    description="Track property taxes year over year."
                />
                <TaxDialog properties={properties} currentYear={currentYear} />
            </div>

            {properties.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Add a property first, then you can record its taxes.
                </p>
            ) : (
                properties.map((property) => (
                    <Card key={property.id}>
                        <CardHeader>
                            <CardTitle className="text-base">{property.name}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {property.taxes.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No tax records yet.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Year</TableHead>
                                            <TableHead className="text-right">Tax amount</TableHead>
                                            <TableHead className="text-right">Change ($)</TableHead>
                                            <TableHead className="text-right">Change (%)</TableHead>
                                            <TableHead className="w-24 text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {property.taxes.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="font-medium">
                                                    <span className="inline-flex items-center gap-2">
                                                        {row.year}
                                                        {row.estimated && (
                                                            <Badge variant="secondary">
                                                                Estimated
                                                            </Badge>
                                                        )}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(row.amount)}
                                                </TableCell>
                                                <TableCell
                                                    className={cn(
                                                        'text-right',
                                                        row.change_abs !== null &&
                                                            changeClass(row.change_abs),
                                                    )}
                                                >
                                                    {row.change_abs !== null
                                                        ? signedMoney(row.change_abs)
                                                        : '—'}
                                                </TableCell>
                                                <TableCell
                                                    className={cn(
                                                        'text-right',
                                                        row.change_pct !== null &&
                                                            changeClass(row.change_pct),
                                                    )}
                                                >
                                                    {row.change_pct !== null
                                                        ? signedPct(row.change_pct)
                                                        : '—'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <TaxDialog
                                                            properties={properties}
                                                            currentYear={currentYear}
                                                            record={{
                                                                id: row.id,
                                                                property_id: property.id,
                                                                year: row.year,
                                                                amount: row.amount,
                                                                estimated: row.estimated,
                                                                bill_date: row.bill_date,
                                                            }}
                                                        />
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label="Delete tax record"
                                                            onClick={() => destroy(row)}
                                                        >
                                                            <Trash2 className="size-4 text-destructive" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                ))
            )}
        </div>
    );
}

PropertyTaxesIndex.layout = {
    breadcrumbs: [{ title: 'Property Taxes', href: '/property-taxes' }],
};
