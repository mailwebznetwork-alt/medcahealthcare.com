import { createIcons } from 'lucide';
import {
    Activity,
    BriefcaseBusiness,
    ChevronRight,
    CircleUser,
    Database,
    DraftingCompass,
    FileChartColumn,
    FolderKanban,
    Gauge,
    Inbox,
    Layers,
    LayoutDashboard,
    LineChart,
    LogOut,
    Megaphone,
    Orbit,
    PanelLeft,
    Search,
    Settings,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    Users,
    UsersRound,
    Workflow,
} from 'lucide';

const lucideIcons = {
    Activity,
    BriefcaseBusiness,
    ChevronRight,
    CircleUser,
    Database,
    DraftingCompass,
    FileChartColumn,
    FolderKanban,
    Gauge,
    Inbox,
    Layers,
    LayoutDashboard,
    LineChart,
    LogOut,
    Megaphone,
    Orbit,
    PanelLeft,
    Search,
    Settings,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    Users,
    UsersRound,
    Workflow,
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
