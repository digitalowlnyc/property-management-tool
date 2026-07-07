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
type PropertyOption = { id: number; name: string; current_monthly_rent: number };
type PropertyTax = {
    property_id: number;
    property_name: string;
    amount: number | null;
    estimated: boolean;
    bill_date: string | null;
    accrued: number | null;
    monthly_amount: number | null;
};

type Expense = {
    id: number;
    property_id: number;
    property_name: string;
    label: string;
    frequency: string;
    amount_type: string;
    amount: number;
    rent_basis: number | null;
    monthly_amount: number;
};

type Props = {
    expenses: Expense[];
    properties: PropertyOption[];
    propertyTaxes: PropertyTax[];
    currentYear: number;
    frequencies: Option[];
    amountTypes: Option[];
};

function ExpenseDialog({
    expense,
    properties,
    frequencies,
    amountTypes,
}: {
    expense?: Expense;
    properties: PropertyOption[];
    frequencies: Option[];
    amountTypes: Option[];
}) {
    const [open, setOpen] = useState(false);
    const editing = Boolean(expense);

    const form = useForm({
        property_id: expense?.property_id?.toString() ?? properties[0]?.id.toString() ?? '',
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
    const selectedRent =
        properties.find((p) => p.id.toString() === form.data.property_id)?.current_monthly_rent ?? 0;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const onSuccess = () => {
            setOpen(false);
            if (!editing) form.reset('label', 'amount', 'rent_basis');
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
                    <Button disabled={properties.length === 0}>
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
                        <Label htmlFor="label">Description</Label>
                        <Input
                            id="label"
                            value={form.data.label}
                            onChange={(e) => form.setData('label', e.target.value)}
                            placeholder="e.g. Property management fee"
                        />
                        <InputError message={form.errors.label} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
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
                                placeholder={`Defaults to current rent (${formatMoney(selectedRent)})`}
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

export default function ExpensesIndex({
    expenses,
    properties,
    propertyTaxes,
    currentYear,
    frequencies,
    amountTypes,
}: Props) {
    const { propertyFilter } = usePage().props;
    // A single property is selected, so its name is redundant in every row.
    const hideProperty = propertyFilter?.selected != null;

    const frequencyLabel = (v: string) => frequencies.find((o) => o.value === v)?.label ?? v;

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
            <Head title="Expenses" />
            <div className="flex items-center justify-between">
                <Heading title="Expenses" description="All expenses across your properties." />
                <ExpenseDialog
                    properties={properties}
                    frequencies={frequencies}
                    amountTypes={amountTypes}
                />
            </div>

            {properties.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Add a property first, then you can record its expenses.
                </p>
            ) : expenses.length === 0 ? (
                <p className="text-sm text-muted-foreground">No expenses recorded yet.</p>
            ) : (
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                {!hideProperty && <TableHead>Property</TableHead>}
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
                                    {!hideProperty && (
                                        <TableCell className="font-medium">
                                            {expense.property_name}
                                        </TableCell>
                                    )}
                                    <TableCell>{expense.label}</TableCell>
                                    <TableCell>{frequencyLabel(expense.frequency)}</TableCell>
                                    <TableCell>{amountDisplay(expense)}</TableCell>
                                    <TableCell className="text-right">
                                        {formatMoney(expense.monthly_amount)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <ExpenseDialog
                                                expense={expense}
                                                properties={properties}
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

            {properties.length > 0 && (
                <div className="flex flex-col gap-3">
                    <Heading
                        title={`Property taxes (${currentYear})`}
                        description="Managed on the Property Taxes page; the current year counts toward cashflow."
                        variant="small"
                    />
                    <div className="rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {!hideProperty && <TableHead>Property</TableHead>}
                                    <TableHead>Amount</TableHead>
                                    <TableHead className="text-right">Monthly equiv.</TableHead>
                                    <TableHead className="text-right">Accrued</TableHead>
                                    <TableHead className="w-24 text-right"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {propertyTaxes.map((tax) => (
                                    <TableRow key={tax.property_id}>
                                        {!hideProperty && (
                                            <TableCell className="font-medium">
                                                {tax.property_name}
                                            </TableCell>
                                        )}
                                        {tax.amount !== null ? (
                                            <>
                                                <TableCell>
                                                    <span className="inline-flex items-center gap-2">
                                                        {formatMoney(tax.amount)}
                                                        {tax.estimated && (
                                                            <Badge variant="secondary">
                                                                Estimated
                                                            </Badge>
                                                        )}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(tax.monthly_amount ?? 0)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {tax.accrued !== null ? (
                                                        formatMoney(tax.accrued)
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Link
                                                        href="/property-taxes"
                                                        className="text-sm underline"
                                                    >
                                                        Manage
                                                    </Link>
                                                </TableCell>
                                            </>
                                        ) : (
                                            <>
                                                <TableCell
                                                    colSpan={3}
                                                    className="text-muted-foreground"
                                                >
                                                    Not entered for {currentYear}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Link
                                                        href="/property-taxes"
                                                        className="text-sm underline"
                                                    >
                                                        Enter taxes
                                                    </Link>
                                                </TableCell>
                                            </>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            )}
        </div>
    );
}

ExpensesIndex.layout = {
    breadcrumbs: [{ title: 'Expenses', href: '/expenses' }],
};
