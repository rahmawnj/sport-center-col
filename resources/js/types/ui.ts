export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type TableAlign = 'left' | 'center' | 'right';

export type TableColumn = {
    key: string;
    label: string;
    align?: TableAlign;
    class?: string;
};

export type TablePaginator = {
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};
