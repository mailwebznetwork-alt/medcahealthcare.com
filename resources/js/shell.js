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
    MapPin,
    Megaphone,
    Orbit,
    Pencil,
    PanelLeft,
    Search,
    Server,
    Settings,
    ShieldCheck,
    Sparkles,
    Trash2,
    TrendingUp,
    Upload,
    UserMinus,
    UserPlus,
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
    MapPin,
    Megaphone,
    Orbit,
    Pencil,
    PanelLeft,
    Search,
    Server,
    Settings,
    ShieldCheck,
    Sparkles,
    Trash2,
    TrendingUp,
    Upload,
    UserMinus,
    UserPlus,
    Users,
    UsersRound,
    Workflow,
};

export function bootIcons() {
    createIcons({
        icons: lucideIcons,
        attrs: {
            'stroke-width': 1.75,
        },
    });
}

function scheduleLucideIcons() {
    const run = () => {
        bootIcons();
        requestAnimationFrame(() => bootIcons());
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        run();
    }
}

scheduleLucideIcons();

document.addEventListener('livewire:navigated', () => {
    bootIcons();
});
