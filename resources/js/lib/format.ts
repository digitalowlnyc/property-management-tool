const currency = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
});

/** Format a number as USD, e.g. 1640 -> "$1,640.00". */
export function formatMoney(value: number): string {
    return currency.format(value ?? 0);
}
