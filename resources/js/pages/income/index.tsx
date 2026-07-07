import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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

type Option = { value: string; label: string };
type PropertyOption = { id: number; name: string };

type Income = {
    id: number;
    property_id: number;
    property_name: string;
    type: string;
    label: string | null;
    amount: number;
    frequency: string;
    start_date: string | null;
    end_date: string | null;
};

type Props = {
    incomes: Income[];
    properties: PropertyOption[];
    incomeTypes: Option[];
    frequencies: Option[];
};

function IncomeDialog({
    income,
    properties,
    incomeTypes,
    frequencies,
}: {
    income?: Income;
    properties: PropertyOption[];
    incomeTypes: Option[];
    frequencies: Option[];
}) {
    const [open, setOpen] = useState(false);
    const editing = Boolean(income);

    const form = useForm({
        property_id: income?.property_id?.toString() ?? properties[0]?.id.toString() ?? '',
        type: income?.type ?? 'rent',
        label: income?.label ?? '',
        amount: income?.amount?.toString() ?? '',
        frequency: income?.frequency ?? 'monthly',
        start_date: income?.start_date ?? '',
        end_date: income?.end_date ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const onSuccess = () => {
            setOpen(false);
            if (!editing) form.reset('label', 'amount', 'start_date', 'end_date');
        };
        if (editing) {
            form.put(`/income/${income!.id}`, { onSuccess, preserveScroll: true });
        } else {
            form.post('/income', { onSuccess, preserveScroll: true });
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {editing ? (
                    <Button variant="ghost" size="icon" aria-label="Edit income">
                        <Pencil className="size-4" />
                    </Button>
                ) : (
                    <Button disabled={properties.length === 0}>
                        <Plus className="size-4" /> Add income
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit income' : 'Add income'}</DialogTitle>
                        <DialogDescription>
                            Record rent or other income. Rent drives percentage-of-rent expenses.
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

                    <div className="grid gap-2">
                        <Label>Type</Label>
                        <Select value={form.data.type} onValueChange={(v) => form.setData('type', v)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {incomeTypes.map((o) => (
                                    <SelectItem key={o.value} value={o.value}>
                                        {o.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="label">Description (optional)</Label>
                        <Input
                            id="label"
                            value={form.data.label}
                            onChange={(e) => form.setData('label', e.target.value)}
                            placeholder="e.g. Parking, Laundry"
                        />
                        <InputError message={form.errors.label} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="amount">Amount ($)</Label>
                            <Input
                                id="amount"
                                type="number"
                                step="0.01"
                                min="0"
                                value={form.data.amount}
                                onChange={(e) => form.setData('amount', e.target.value)}
                                placeholder="e.g. 2000"
                            />
                            <InputError message={form.errors.amount} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Frequency</Label>
                            <Select
                                value={form.data.frequency}
                                onValueChange={(v) => form.setData('frequency', v)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {frequencies.map((o) => (
                                        <SelectItem key={o.value} value={o.value}>
                                            {o.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.frequency} />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="start_date">Start date (optional)</Label>
                            <Input
                                id="start_date"
                                type="date"
                                value={form.data.start_date}
                                onChange={(e) => form.setData('start_date', e.target.value)}
                            />
                            <InputError message={form.errors.start_date} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="end_date">End date (optional)</Label>
                            <Input
                                id="end_date"
                                type="date"
                                value={form.data.end_date}
                                onChange={(e) => form.setData('end_date', e.target.value)}
                            />
                            <InputError message={form.errors.end_date} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Save changes' : 'Add income'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function IncomeIndex({ incomes, properties, incomeTypes, frequencies }: Props) {
    const { propertyFilter } = usePage().props;
    // A single property is selected, so its name is redundant in every row.
    const hideProperty = propertyFilter?.selected != null;

    const typeLabel = (v: string) => incomeTypes.find((o) => o.value === v)?.label ?? v;
    const frequencyLabel = (v: string) => frequencies.find((o) => o.value === v)?.label ?? v;

    const destroy = (income: Income) => {
        if (confirm('Delete this income entry?')) {
            router.delete(`/income/${income.id}`, { preserveScroll: true });
        }
    };

    return (
        <div className="flex flex-col gap-6 p-4">
            <Head title="Income" />
            <div className="flex items-center justify-between">
                <Heading title="Income" description="Rent and other income across your properties." />
                <IncomeDialog
                    properties={properties}
                    incomeTypes={incomeTypes}
                    frequencies={frequencies}
                />
            </div>

            {properties.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Add a property first, then you can record its income.
                </p>
            ) : incomes.length === 0 ? (
                <p className="text-sm text-muted-foreground">No income recorded yet.</p>
            ) : (
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                {!hideProperty && <TableHead>Property</TableHead>}
                                <TableHead>Type</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Frequency</TableHead>
                                <TableHead>Dates</TableHead>
                                <TableHead className="w-24 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {incomes.map((income) => (
                                <TableRow key={income.id}>
                                    {!hideProperty && (
                                        <TableCell className="font-medium">
                                            {income.property_name}
                                        </TableCell>
                                    )}
                                    <TableCell>{typeLabel(income.type)}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {income.label ?? '—'}
                                    </TableCell>
                                    <TableCell>{formatMoney(income.amount)}</TableCell>
                                    <TableCell>{frequencyLabel(income.frequency)}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {income.start_date || income.end_date
                                            ? `${income.start_date ?? '…'} → ${income.end_date ?? 'ongoing'}`
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <IncomeDialog
                                                income={income}
                                                properties={properties}
                                                incomeTypes={incomeTypes}
                                                frequencies={frequencies}
                                            />
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Delete income"
                                                onClick={() => destroy(income)}
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

IncomeIndex.layout = {
    breadcrumbs: [{ title: 'Income', href: '/income' }],
};
