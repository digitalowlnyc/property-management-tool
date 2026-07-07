import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';

type Line = { label: string; detail: string; amount: number };

type PropertyActual = {
    id: number;
    name: string;
    occupied_label: string;
    vacant_label: string;
    occupied_days: number;
    vacant_days: number;
    income_lines: Line[];
    expense_lines: Line[];
    income_total: number;
    expense_total: number;
    net: number;
};

type Props = {
    year: number;
    availableYears: number[];
    totals: { income: number; expenses: number; net: number };
    properties: PropertyActual[];
};

function netClass(value: number) {
    return value < 0 ? 'text-destructive' : 'text-emerald-600 dark:text-emerald-500';
}

function perMonth(annual: number) {
    return `${formatMoney(annual / 12)} / mo`;
}

function LineTable({ title, lines, total }: { title: string; lines: Line[]; total: number }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>{title}</TableHead>
                    <TableHead>Detail</TableHead>
                    <TableHead className="text-right">Amount</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {lines.map((line, i) => (
                    <TableRow key={i}>
                        <TableCell className="font-medium">{line.label}</TableCell>
                        <TableCell className="text-muted-foreground">{line.detail}</TableCell>
                        <TableCell className="text-right">{formatMoney(line.amount)}</TableCell>
                    </TableRow>
                ))}
            </TableBody>
            <TableFooter>
                <TableRow>
                    <TableCell className="font-medium" colSpan={2}>
                        Total {title.toLowerCase()}
                    </TableCell>
                    <TableCell className="text-right font-medium">{formatMoney(total)}</TableCell>
                </TableRow>
            </TableFooter>
        </Table>
    );
}

export default function CashflowActualIndex({ year, availableYears, totals, properties }: Props) {
    const changeYear = (value: string) => {
        router.get('/cashflow-actual', { year: value }, { preserveScroll: true, preserveState: true });
    };

    const hasData = availableYears.length > 0;

    return (
        <div className="flex flex-col gap-6 p-4">
            <Head title="Cashflow — Yearly Actual" />
            <div className="flex flex-wrap items-end justify-between gap-4">
                <Heading
                    title="Cashflow — Yearly Actual"
                    description="Actual cashflow for the year, accounting for vacant months between rent periods."
                />
                {hasData && (
                    <div className="grid gap-2">
                        <Label>Year</Label>
                        <Select value={year.toString()} onValueChange={changeYear}>
                            <SelectTrigger className="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {availableYears.map((y) => (
                                    <SelectItem key={y} value={y.toString()}>
                                        {y}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
            </div>

            {!hasData ? (
                <p className="text-sm text-muted-foreground">
                    Add a rent entry with a start date on the{' '}
                    <Link href="/income" className="underline">
                        Income page
                    </Link>{' '}
                    to see yearly actuals.
                </p>
            ) : (
                <>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Card>
                            <CardHeader>
                                <CardDescription>Actual income ({year})</CardDescription>
                                <CardTitle className="text-2xl">
                                    {formatMoney(totals.income)}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm text-muted-foreground">
                                {perMonth(totals.income)}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardDescription>Actual expenses ({year})</CardDescription>
                                <CardTitle className="text-2xl">
                                    {formatMoney(totals.expenses)}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm text-muted-foreground">
                                {perMonth(totals.expenses)}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardDescription>Actual net ({year})</CardDescription>
                                <CardTitle className={cn('text-2xl', netClass(totals.net))}>
                                    {formatMoney(totals.net)}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className={cn('text-sm', netClass(totals.net))}>
                                {perMonth(totals.net)}
                            </CardContent>
                        </Card>
                    </div>

                    {properties.map((property) => (
                        <Card key={property.id}>
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <CardTitle className="text-base">{property.name}</CardTitle>
                                    <div className="flex items-center gap-2">
                                        <Badge variant="secondary">
                                            {property.occupied_label} occupied
                                        </Badge>
                                        {property.vacant_days > 0 && (
                                            <Badge variant="outline">
                                                {property.vacant_label} vacant
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="grid gap-6">
                                <LineTable
                                    title="Income"
                                    lines={property.income_lines}
                                    total={property.income_total}
                                />
                                <LineTable
                                    title="Expenses"
                                    lines={property.expense_lines}
                                    total={property.expense_total}
                                />
                                <div className="flex items-center justify-between border-t pt-3">
                                    <span className="font-medium">Net ({year})</span>
                                    <div className="text-right">
                                        <div
                                            className={cn(
                                                'text-lg font-semibold',
                                                netClass(property.net),
                                            )}
                                        >
                                            {formatMoney(property.net)}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {perMonth(property.net)} equivalent
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </>
            )}
        </div>
    );
}

CashflowActualIndex.layout = {
    breadcrumbs: [{ title: 'Cashflow — Yearly Actual', href: '/cashflow-actual' }],
};
