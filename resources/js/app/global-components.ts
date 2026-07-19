/**
 * Registry komponen global untuk mode code Page.
 * Memindai DOM [data-component] dan memasang perilaku React/Vanilla.
 *
 * Status: SKELETON. Diisi penuh di Fase fitur (mode code + interaktivitas).
 */

type ComponentRegistry = Record<string, (el: HTMLElement) => void>;

const registry: ComponentRegistry = {
    // 'hero': (el) => { /* hydrate */ },
    // 'carousel': (el) => { /* hydrate */ },
};

export function scanGlobalComponents(root: HTMLElement = document.body): void {
    Object.entries(registry).forEach(([name, hydrate]) => {
        root.querySelectorAll<HTMLElement>(`[data-component="${name}"]`).forEach(hydrate);
    });
}

// Auto-scan on DOM ready
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => scanGlobalComponents());
}
