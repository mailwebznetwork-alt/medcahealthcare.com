import { createIcons } from 'lucide';
import {
    Activity,
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
    Orbit,
    PanelLeft,
    Search,
    Settings,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide';

const lucideIcons = {
    Activity,
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
    Orbit,
    PanelLeft,
    Search,
    Settings,
    ShieldCheck,
    Sparkles,
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
