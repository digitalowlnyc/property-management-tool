import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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

type Property = {
    id: number;
    name: string;
    address: string | null;
    notes: string | null;
    current_monthly_rent: number;
};

type Expense = {
    id: number;
    label: string;
    frequency: string;
    amount_type: string;
    amount: number;
    rent_basis: number | null;
    monthly_amount: number;
};

type Props = {
    property: Property;
    expenses: Expense[];
    frequencies: Option[];
    amountTypes: Option[];
};

function ExpenseDialog({
    property,
    expense,
    frequencies,
    amountTypes,
}: {
    property: Property;
    expense?: Expense;
    frequencies: Option[];
    amountTypes: Option[];
}) {
    const [open, setOpen] = useState(false);
    const editing = Boolean(expense);

    const form = useForm({
        property_id: property.id,
        label: expense?.label ?? '',
        frequency: expense?.frequency ?? 'monthly',
        amount_type: expense?.amount_type ?? 'fixed',
        amount: expense?.amount?.toString() ?? '',
        rent_basis: expense?.rent_basis?.toString() ?? '',
    });

    const amountType = form.data.amount_type;
    const usesRentBasis = amountType === 'percentage' || amountType === 'months_of_rent';
    const amountLabel =
        amountType === 'percentage'
            ? 'Percentage of rent (%)'
            : amountType === 'months_of_rent'
              ? 'Months of rent (1–12)'
              : 'Amount ($)';
    const amountPlaceholder =
        amountType === 'percentage' ? 'e.g. 8' : amountType === 'months_of_rent' ? 'e.g. 1' : 'e.g. 200';

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const onSuccess = () => {
            setOpen(false);
            if (!editing) form.reset();
        };
        if (editing) {
            form.put(`/expenses/${expense!.id}`, { onSuccess, preserveScroll: true });
        } else {
            form.post('/expenses', { onSuccess, preserveScroll: true });
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {editing ? (
                    <Button variant="ghost" size="icon" aria-label="Edit expense">
                        <Pencil className="size-4" />
                    </Button>
                ) : (
                    <Button>
                        <Plus className="size-4" /> Add expense
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit expense' : 'Add expense'}</DialogTitle>
                        <DialogDescription>
                            Expenses can be a fixed dollar amount or a percentage of rent.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="label">Description</Label>
                        <Input
                            id="label"
                            value={form.data.label}
                            onChange={(e) => form.setData('label', e.target.value)}
                            placeholder="e.g. Property management fee"
                            autoFocus
                        />
                        <InputError message={form.errors.label} />
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

                    <div className="grid gap-2">
                        <Label>Amount type</Label>
                        <Select
                            value={form.data.amount_type}
                            onValueChange={(v) => form.setData('amount_type', v)}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {amountTypes.map((o) => (
                                    <SelectItem key={o.value} value={o.value}>
                                        {o.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="amount">{amountLabel}</Label>
                        <Input
                            id="amount"
                            type="number"
                            step={amountType === 'months_of_rent' ? '0.5' : '0.01'}
                            min={amountType === 'months_of_rent' ? '1' : '0'}
                            max={amountType === 'months_of_rent' ? '12' : undefined}
                            value={form.data.amount}
                            onChange={(e) => form.setData('amount', e.target.value)}
                            placeholder={amountPlaceholder}
                        />
                        <InputError message={form.errors.amount} />
                    </div>

                    {usesRentBasis && (
                        <div className="grid gap-2">
                            <Label htmlFor="rent_basis">Rent to use (optional)</Label>
                            <Input
                                id="rent_basis"
                                type="number"
                                step="0.01"
                                min="0"
                                value={form.data.rent_basis}
                                onChange={(e) => form.setData('rent_basis', e.target.value)}
                                placeholder={`Defaults to current rent (${formatMoney(property.current_monthly_rent)})`}
                            />
                            <InputError message={form.errors.rent_basis} />
                        </div>
                    )}

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {editing ? 'Save changes' : 'Add expense'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function PropertyShow({ property, expenses, frequencies, amountTypes }: Props) {
    const frequencyLabel = (value: string) =>
        frequencies.find((f) => f.value === value)?.label ?? value;

    const amountDisplay = (expense: Expense) =>
        expense.amount_type === 'percentage'
            ? `${expense.amount}% of rent`
            : expense.amount_type === 'months_of_rent'
              ? `${expense.amount} mo. of rent`
              : formatMoney(expense.amount);

    const destroy = (expense: Expense) => {
        if (confirm(`Delete "${expense.label}"?`)) {
            router.delete(`/expenses/${expense.id}`, { preserveScroll: true });
        }
    };

    return (
        <div className="flex flex-col gap-6 p-4">
            <Head title={property.name} />

            <div>
                <Link
                    href="/properties"
                    className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:underline"
                >
                    <ArrowLeft className="size-4" /> All properties
                </Link>
                <Heading title={property.name} description={property.address ?? undefined} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardDescription>Current monthly rent</CardDescription>
                        <CardTitle className="text-2xl">
                            {formatMoney(property.current_monthly_rent)}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            Set rent on the{' '}
                            <Link href="/income" className="underline">
                                Income page
                            </Link>
                            . Percentage expenses use this figure by default.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div className="flex items-center justify-between">
                <Heading title="Expenses" variant="small" />
                <ExpenseDialog
                    property={property}
                    frequencies={frequencies}
                    amountTypes={amountTypes}
                />
            </div>

            {expenses.length === 0 ? (
                <p className="text-sm text-muted-foreground">No expenses yet for this property.</p>
            ) : (
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Description</TableHead>
                                <TableHead>Frequency</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead className="text-right">Monthly equiv.</TableHead>
                                <TableHead className="w-24 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {expenses.map((expense) => (
                                <TableRow key={expense.id}>
                                    <TableCell className="font-medium">{expense.label}</TableCell>
                                    <TableCell>{frequencyLabel(expense.frequency)}</TableCell>
                                    <TableCell>{amountDisplay(expense)}</TableCell>
                                    <TableCell className="text-right">
                                        {formatMoney(expense.monthly_amount)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <ExpenseDialog
                                                property={property}
                                                expense={expense}
                                                frequencies={frequencies}
                                                amountTypes={amountTypes}
                                            />
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label="Delete expense"
                                                onClick={() => destroy(expense)}
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

PropertyShow.layout = {
    breadcrumbs: [
        { title: 'Properties', href: '/properties' },
        { title: 'Property', href: '#' },
    ],
};
