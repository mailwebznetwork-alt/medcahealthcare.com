import { createIcons } from 'lucide';
import {
    Activity,
    Bell,
    ChevronRight,
    CircleUser,
    Database,
    FileChartColumn,
    FolderKanban,
    Gauge,
    Layers,
    LayoutDashboard,
    LineChart,
    LogOut,
    Moon,
    Orbit,
    PanelLeft,
    Search,
    Settings,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    Users,
} from 'lucide';

const lucideIcons = {
    Activity,
    Bell,
    ChevronRight,
    CircleUser,
    Database,
    FileChartColumn,
    FolderKanban,
    Gauge,
    Layers,
    LayoutDashboard,
    LineChart,
    LogOut,
    Moon,
    Orbit,
    PanelLeft,
    Search,
    Settings,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    Users,
};

function bootIcons() {
    createIcons({
        icons: lucideIcons,
        attrs: {
            'stroke-width': 1.75,
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bootIcons();
});

document.addEventListener('livewire:navigated', () => {
    bootIcons();
});
