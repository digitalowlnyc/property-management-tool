import { Link } from '@inertiajs/react';
import { Building2, CalendarClock, Landmark, LayoutGrid, Receipt, TrendingUp, Wallet } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Properties',
        href: '/properties',
        icon: Building2,
    },
    {
        title: 'Income',
        href: '/income',
        icon: Wallet,
    },
    {
        title: 'Expenses',
        href: '/expenses',
        icon: Receipt,
    },
    {
        title: 'Property Taxes',
        href: '/property-taxes',
        icon: Landmark,
    },
    {
        title: 'Cashflow',
        href: '/cashflow',
        icon: TrendingUp,
    },
    {
        title: 'Cashflow - Yearly Actual',
        href: '/cashflow-actual',
        icon: CalendarClock,
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
