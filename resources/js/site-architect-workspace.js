const STORAGE_KEY = 'medca.site-architect.sidebar.expanded';

export function siteArchitectWorkspace(defaultExpanded = ['content', 'sections']) {
    let expanded = { ...Object.fromEntries(defaultExpanded.map((k) => [k, true])) };

    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
        if (stored && typeof stored === 'object') {
            expanded = { ...expanded, ...stored };
        }
    } catch {
        // ignore
    }

    const persist = () => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(expanded));
        } catch {
            // ignore
        }
    };

    return {
        mobileOpen: false,
        expanded,
        toggleGroup(key) {
            this.expanded[key] = !this.isExpanded(key);
            persist();
        },
        isExpanded(key) {
            return this.expanded[key] !== false;
        },
    };
}

if (typeof window !== 'undefined') {
    window.siteArchitectWorkspace = siteArchitectWorkspace;
}
