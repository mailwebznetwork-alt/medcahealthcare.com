import { createIcons } from 'lucide';
import {
    Activity,
    ArrowLeft,
    Bell,
    BriefcaseBusiness,
    Check,
    ListChecks,
    ChevronRight,
    CircleUser,
    Database,
    DraftingCompass,
    ExternalLink,
    FileChartColumn,
    FolderKanban,
    Gauge,
    Inbox,
    Layers,
    LayoutDashboard,
    LineChart,
    List,
    LogOut,
    MapPin,
    Megaphone,
    Orbit,
    Pencil,
    PanelLeft,
    Plus,
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
import { onBackendDomUpdate } from './livewire-dom-hooks';

const lucideIcons = {
    Activity,
    ArrowLeft,
    Bell,
    BriefcaseBusiness,
    Check,
    ListChecks,
    ChevronRight,
    CircleUser,
    Database,
    DraftingCompass,
    ExternalLink,
    FileChartColumn,
    FolderKanban,
    Gauge,
    Inbox,
    Layers,
    LayoutDashboard,
    LineChart,
    List,
    LogOut,
    MapPin,
    Megaphone,
    Orbit,
    Pencil,
    PanelLeft,
    Plus,
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

export function bootIcons(root = document) {
    const scope = root?.querySelector ? root : document;

    if (!scope.querySelector?.('[data-lucide]')) {
        return;
    }

    createIcons({
        icons: lucideIcons,
        attrs: {
            'stroke-width': 1.75,
        },
        root: scope,
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
onBackendDomUpdate(bootIcons);

document.addEventListener('livewire:navigated', () => {
    bootIcons();
});
