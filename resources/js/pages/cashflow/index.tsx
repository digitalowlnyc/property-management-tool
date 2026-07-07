import { Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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

type PropertyRow = {
    id: number;
    name: string;
    current_monthly_rent: number;
    monthly_income: number;
    monthly_expenses: number;
    monthly_net: number;
    annual_income: number;
    annual_expenses: number;
    annual_net: number;
};

type Totals = {
    monthly_income: number;
    monthly_expenses: number;
    monthly_net: number;
    annual_income: number;
    annual_expenses: number;
    annual_net: number;
};

type Props = {
    cashflow: {
        totals: Totals;
        properties: PropertyRow[];
    };
};

function netClass(value: number) {
    return value < 0 ? 'text-destructive' : 'text-emerald-600 dark:text-emerald-500';
}

function SummaryCard({
    title,
    net,
    income,
    expenses,
}: {
    title: string;
    net: number;
    income: number;
    expenses: number;
}) {
    return (
        <Card>
            <CardHeader>
                <CardDescription>{title}</CardDescription>
                <CardTitle className={cn('text-3xl', netClass(net))}>{formatMoney(net)}</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground">
                <div className="flex justify-between">
                    <span>Income</span>
                    <span>{formatMoney(income)}</span>
                </div>
                <div className="flex justify-between">
                    <span>Expenses</span>
                    <span>{formatMoney(expenses)}</span>
                </div>
            </CardContent>
        </Card>
    );
}

export default function CashflowIndex({ cashflow }: Props) {
    const { totals, properties } = cashflow;
    const { propertyFilter } = usePage().props;
    // A single property is selected, so its name (and the total row) are redundant.
    const hideProperty = propertyFilter?.selected != null;

    return (
        <div className="flex flex-col gap-6 p-4">
            <Head title="Cashflow" />
            <Heading
                title="Cashflow"
                description="Monthly figures are the recurring run-rate; one-time items count only toward the annual totals."
            />

            <div className="grid gap-4 md:grid-cols-2">
                <SummaryCard
                    title="This month (net)"
                    net={totals.monthly_net}
                    income={totals.monthly_income}
                    expenses={totals.monthly_expenses}
                />
                <SummaryCard
                    title="This year (net)"
                    net={totals.annual_net}
                    income={totals.annual_income}
                    expenses={totals.annual_expenses}
                />
            </div>

            <Heading title="By property" variant="small" />

            {properties.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No properties yet.{' '}
                    <Link href="/properties" className="underline">
                        Add one
                    </Link>{' '}
                    to see cashflow.
                </p>
            ) : (
                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                {!hideProperty && <TableHead>Property</TableHead>}
                                <TableHead className="text-right">Monthly income</TableHead>
                                <TableHead className="text-right">Monthly expenses</TableHead>
                                <TableHead className="text-right">Monthly net</TableHead>
                                <TableHead className="text-right">Annual net</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {properties.map((row) => (
                                <TableRow key={row.id}>
                                    {!hideProperty && (
                                        <TableCell className="font-medium">
                                            <Link
                                                href={`/properties/${row.id}`}
                                                className="hover:underline"
                                            >
                                                {row.name}
                                            </Link>
                                        </TableCell>
                                    )}
                                    <TableCell className="text-right">
                                        {formatMoney(row.monthly_income)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatMoney(row.monthly_expenses)}
                                    </TableCell>
                                    <TableCell className={cn('text-right', netClass(row.monthly_net))}>
                                        {formatMoney(row.monthly_net)}
                                    </TableCell>
                                    <TableCell className={cn('text-right', netClass(row.annual_net))}>
                                        {formatMoney(row.annual_net)}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                        {!hideProperty && (
                            <TableFooter>
                                <TableRow>
                                    <TableCell className="font-medium">Total</TableCell>
                                    <TableCell className="text-right">
                                        {formatMoney(totals.monthly_income)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatMoney(totals.monthly_expenses)}
                                    </TableCell>
                                    <TableCell className={cn('text-right', netClass(totals.monthly_net))}>
                                        {formatMoney(totals.monthly_net)}
                                    </TableCell>
                                    <TableCell className={cn('text-right', netClass(totals.annual_net))}>
                                        {formatMoney(totals.annual_net)}
                                    </TableCell>
                                </TableRow>
                            </TableFooter>
                        )}
                    </Table>
                </div>
            )}
        </div>
    );
}

CashflowIndex.layout = {
    breadcrumbs: [{ title: 'Cashflow', href: '/cashflow' }],
};
