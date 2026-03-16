import { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import {
    BottomNavigation, BottomNavigationAction, Paper,
    SwipeableDrawer, List, ListItem, ListItemButton,
    ListItemIcon, ListItemText, Box, Typography, Divider,
    Badge, Avatar,
} from '@mui/material';
import {
    Home, FolderOpen, Assignment, NotificationsOutlined,
    MoreHoriz, People, Code, Assessment, Language,
    ViewList, FindInPage, History, Security, Settings,
    AccountCircle, Close,
} from '@mui/icons-material';
import { useAuth } from '../../contexts/AuthContext';
import { useQuery } from '@apollo/client';
import { GET_NOTIFICATIONS } from '../../graphql/queries';

// ── Bottom nav "More" drawer items ────────────────────────────────
const moreItems = [
    { label: 'Clients', path: '/clients', icon: People, roles: ['SuperAdmin', 'Developer'] },
    { label: 'Developers', path: '/developers', icon: Code, roles: ['SuperAdmin'] },
    { label: 'Users', path: '/users', icon: People, roles: ['SuperAdmin'] },
    { label: 'Reports', path: '/reports', icon: Assessment, roles: ['SuperAdmin', 'Client'] },
    { label: 'Profile', path: '/profile', icon: AccountCircle, roles: ['SuperAdmin', 'Developer', 'Client'] },
    { label: 'Sites', path: '/sites', icon: Language, roles: ['SuperAdmin', 'Developer'] },
    { label: 'Work Queue', path: '/workqueue', icon: ViewList, roles: ['SuperAdmin', 'Developer'] },
    { label: 'Findings', path: '/findings', icon: FindInPage, roles: ['SuperAdmin', 'Developer', 'Client'] },
    { label: 'History', path: '/history', icon: History, roles: ['SuperAdmin', 'Developer'] },
    { label: 'Security', path: '/security', icon: Security, roles: ['SuperAdmin', 'Developer'] },
    { label: 'Settings', path: '/settings', icon: Settings, roles: ['SuperAdmin', 'Developer', 'Client'] },
];

const roleMeta = {
    SuperAdmin: { color: '#8E43F0', bg: '#EDE8FC', label: 'Super Admin' },
    Developer: { color: '#6A1FCC', bg: '#EDE8FC', label: 'Developer' },
    Client: { color: '#059669', bg: '#D1FAE5', label: 'Client' },
};

export default function MobileBottomNav() {
    const { user } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [drawerOpen, setDrawerOpen] = useState(false);

    // Unread notifications badge
    const { data } = useQuery(GET_NOTIFICATIONS, {
        fetchPolicy: 'cache-and-network',
        pollInterval: 60000,
    });
    const unread = (data?.notifications ?? []).filter((n) => !n.read_at).length;

    const hasRole = (roles) => roles.includes(user?.role);

    // Derive active bottom nav value from pathname
    const path = location.pathname;
    const bottomValue =
        path === '/dashboard' || path.startsWith('/dashboard') ? 'dashboard' :
            path.startsWith('/projects') ? 'projects' :
                path.startsWith('/tasks') ? 'tasks' :
                    path.startsWith('/notifications') ? 'notifications' : 'more';

    const handleNav = (_, val) => {
        if (val === 'more') { setDrawerOpen(true); return; }
        const map = { dashboard: '/dashboard', projects: '/projects', tasks: '/tasks', notifications: '/notifications' };
        if (map[val]) navigate(map[val]);
    };

    const initials = user?.name?.split(' ').map((n) => n[0]).join('').slice(0, 2) ?? '?';
    const meta = roleMeta[user?.role] ?? roleMeta.Client;

    return (
        <>
            {/* ── Fixed bottom navigation bar ─────────────────────── */}
            <Paper
                elevation={8}
                sx={{
                    position: 'fixed', bottom: 0, left: 0, right: 0,
                    zIndex: 1300,
                    display: { xs: 'block', md: 'none' },
                    borderTop: '1px solid #DDD4F8',
                    bgcolor: 'rgba(255,255,255,0.95)',
                    backdropFilter: 'blur(12px)',
                    WebkitBackdropFilter: 'blur(12px)',
                }}
            >
                <BottomNavigation
                    value={bottomValue}
                    onChange={handleNav}
                    sx={{
                        bgcolor: 'transparent',
                        height: 60,
                        '& .MuiBottomNavigationAction-root': {
                            minWidth: 0,
                            color: '#9B89C4',
                            transition: 'color 0.15s',
                            '&.Mui-selected': { color: '#8E43F0' },
                        },
                        '& .MuiBottomNavigationAction-label': {
                            fontSize: '0.65rem', fontWeight: 600, mt: 0.25,
                            '&.Mui-selected': { fontSize: '0.65rem' },
                        },
                    }}
                >
                    <BottomNavigationAction label="Home" value="dashboard" icon={<Home />} />
                    <BottomNavigationAction label="Projects" value="projects" icon={<FolderOpen />} />
                    <BottomNavigationAction label="Tasks" value="tasks" icon={<Assignment />} />
                    <BottomNavigationAction
                        label="Alerts" value="notifications"
                        icon={
                            <Badge badgeContent={unread || null} color="error" max={9}
                                sx={{ '& .MuiBadge-badge': { fontSize: '0.6rem', minWidth: 14, height: 14 } }}>
                                <NotificationsOutlined />
                            </Badge>
                        }
                    />
                    <BottomNavigationAction label="More" value="more" icon={<MoreHoriz />} />
                </BottomNavigation>
            </Paper>

            {/* ── "More" SwipeableDrawer ───────────────────────────── */}
            <SwipeableDrawer
                anchor="bottom"
                open={drawerOpen}
                onOpen={() => setDrawerOpen(true)}
                onClose={() => setDrawerOpen(false)}
                disableSwipeToOpen
                PaperProps={{
                    sx: {
                        borderRadius: '20px 20px 0 0',
                        maxHeight: '80vh',
                        display: { md: 'none' },
                    },
                }}
            >
                {/* Handle bar */}
                <Box sx={{ display: 'flex', justifyContent: 'center', pt: 1.5, pb: 0.5 }}>
                    <Box sx={{ width: 40, height: 4, borderRadius: 2, bgcolor: '#DDD4F8' }} />
                </Box>

                {/* User header */}
                <Box sx={{ px: 3, py: 2, display: 'flex', alignItems: 'center', gap: 2 }}>
                    <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg,#8E43F0,#D4006A)', flexShrink: 0 }}>
                        <Avatar sx={{ width: 40, height: 40, bgcolor: '#8E43F0', fontSize: '0.9rem', fontWeight: 700, border: '2px solid #fff' }}>
                            {initials}
                        </Avatar>
                    </Box>
                    <Box sx={{ minWidth: 0 }}>
                        <Typography fontWeight={700} variant="body2" noWrap>{user?.name}</Typography>
                        <Box sx={{ display: 'inline-flex', alignItems: 'center', px: 1, py: 0.25, borderRadius: 10, bgcolor: meta.bg, mt: 0.25 }}>
                            <Typography variant="caption" fontWeight={700} sx={{ color: meta.color, fontSize: '0.68rem' }}>{meta.label}</Typography>
                        </Box>
                    </Box>
                    <Box sx={{ ml: 'auto' }}>
                        <Box
                            onClick={() => setDrawerOpen(false)}
                            sx={{ width: 32, height: 32, borderRadius: '50%', bgcolor: '#F5F0FF', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', '&:hover': { bgcolor: '#EDE8FC' } }}
                        >
                            <Close sx={{ fontSize: 18, color: '#9B89C4' }} />
                        </Box>
                    </Box>
                </Box>

                <Divider sx={{ borderColor: '#EDE8FC' }} />

                {/* More items list */}
                <List sx={{ px: 1.5, py: 1, overflowY: 'auto' }}>
                    {moreItems.filter((item) => hasRole(item.roles)).map((item) => {
                        const Icon = item.icon;
                        const active = path.startsWith(item.path);
                        return (
                            <ListItem key={item.path} disablePadding sx={{ mb: 0.25 }}>
                                <ListItemButton
                                    onClick={() => { navigate(item.path); setDrawerOpen(false); }}
                                    sx={{
                                        borderRadius: 2.5, py: 1.2,
                                        bgcolor: active ? 'rgba(142,67,240,0.09)' : 'transparent',
                                        color: active ? 'primary.main' : 'text.secondary',
                                        '&:hover': { bgcolor: 'rgba(142,67,240,0.06)', color: 'primary.main' },
                                        transition: 'all 0.15s',
                                    }}
                                >
                                    <ListItemIcon sx={{ minWidth: 38, color: 'inherit' }}>
                                        <Icon fontSize="small" />
                                    </ListItemIcon>
                                    <ListItemText
                                        primary={item.label}
                                        primaryTypographyProps={{ variant: 'body2', fontWeight: active ? 700 : 500 }}
                                    />
                                    {active && <Box sx={{ width: 6, height: 6, borderRadius: '50%', bgcolor: 'primary.main' }} />}
                                </ListItemButton>
                            </ListItem>
                        );
                    })}
                </List>

                {/* Bottom safe area padding */}
                <Box sx={{ pb: 2 }} />
            </SwipeableDrawer>
        </>
    );
}
