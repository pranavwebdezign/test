import { createContext, useContext, useState } from 'react';

const LayoutContext = createContext();

export function LayoutProvider({ children }) {
    const [navLayout, setNavLayout] = useState(
        () => localStorage.getItem('navLayout') ?? 'left'
    );
    const [collapsed, setCollapsed] = useState(
        () => localStorage.getItem('sidebarCollapsed') === 'true'
    );

    const toggleNav = (value) => {
        setNavLayout(value);
        localStorage.setItem('navLayout', value);
    };

    const toggleSidebar = () => {
        setCollapsed((prev) => {
            const next = !prev;
            localStorage.setItem('sidebarCollapsed', String(next));
            return next;
        });
    };

    return (
        <LayoutContext.Provider value={{ navLayout, collapsed, toggleNav, toggleSidebar }}>
            {children}
        </LayoutContext.Provider>
    );
}

export const useLayout = () => useContext(LayoutContext);
