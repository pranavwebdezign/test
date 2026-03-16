import { useState } from 'react';
import {
    AppBar, Toolbar, Box, Button, Typography, Avatar, Badge,
    IconButton, Tooltip, Menu, MenuItem, Divider, CircularProgress,
} from '@mui/material';
import {
    KeyboardArrowDown, NotificationsOutlined, Logout, Settings,
    AccountCircle, Dashboard, FolderOpen, Assignment, People,
    Code, Language, ViewList, Security, Assessment,
} from '@mui/icons-material';
import { useNavigate, useLocation } from 'react-router-dom';
import { useQuery, useMutation } from '@apollo/client';
import { useAuth } from '../../contexts/AuthContext';
import { GET_NOTIFICATIONS } from '../../graphql/queries';
import { MARK_NOTIFICATION_READ } from '../../graphql/mutations';

// ── Nav groups with role filtering ────────────────────────────────
const GROUPS = [
    {
        label: 'Dashboard', path: '/dashboard', icon: Dashboard,
        roles: ['SuperAdmin', 'Developer', 'Client'],
    },
    {
        label: 'Projects', icon: FolderOpen,
        roles: ['SuperAdmin', 'Developer', 'Client'],
        children: [
            { label: 'Projects List', path: '/projects' },
            { label: 'Create Project', path: '/projects/create', roles: ['SuperAdmin'] },
        ],
    },
    {
        label: 'Tasks', icon: Assignment,
        roles: ['SuperAdmin', 'Developer', 'Client'],
        children: [
            { label: 'Tasks List', path: '/tasks' },
            { label: 'Create Task', path: '/tasks/create', roles: ['SuperAdmin', 'Developer'] },
        ],
    },
    {
        label: 'Clients', icon: People,
        roles: ['SuperAdmin', 'Developer'],
        children: [
            { label: 'Clients', path: '/clients' },
            { label: 'Developers', path: '/developers', roles: ['SuperAdmin'] },
        ],
    },
    {
        label: 'WP Sites', icon: Language,
        roles: ['SuperAdmin', 'Developer'],
        children: [
            { label: 'Sites', path: '/sites' },
            { label: 'Work Queue', path: '/workqueue' },
            { label: 'Security', path: '/security' },
        ],
    },
    {
        label: 'Reports', path: '/reports', icon: Assessment,
        roles: ['SuperAdmin', 'Client'],
    },
];

export default function SaasTopNav() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();

    const [anchorEl, setAnchorEl] = useState(null);
    const [notifAnchor, setNotifAnchor] = useState(null);
    const [menuAnchors, setMenuAnchors] = useState({});

    const { data, refetch } = useQuery(GET_NOTIFICATIONS, {
        fetchPolicy: 'cache-and-network',
        pollInterval: 60000,
    });
    const notifications = data?.notifications ?? [];
    const unread = notifications.filter((n) => !n.read_at).length;

    const [markRead] = useMutation(MARK_NOTIFICATION_READ, { onCompleted: () => refetch() });

    const handleLogout = () => { setAnchorEl(null); logout(); navigate('/login'); };
    const initials = user?.name?.split(' ').map((n) => n[0]).join('').slice(0, 2) ?? '?';

    const openGroupMenu = (key, e) => setMenuAnchors((p) => ({ ...p, [key]: e.currentTarget }));
    const closeGroupMenu = (key) => setMenuAnchors((p) => ({ ...p, [key]: null }));

    const isActive = (path) =>
        path && (location.pathname === path || location.pathname.startsWith(path + '/'));

    const isGroupActive = (group) =>
        group.path
            ? isActive(group.path)
            : group.children?.some((c) => isActive(c.path));

    const visibleGroups = GROUPS.filter((g) => g.roles.includes(user?.role));

    return (
        <AppBar
            position="fixed"
            elevation={0}
            sx={{
                bgcolor: 'rgba(255,255,255,0.92)',
                backdropFilter: 'blur(14px)',
                WebkitBackdropFilter: 'blur(14px)',
                borderBottom: '1px solid rgba(221,212,248,0.65)',
                color: 'text.primary',
                zIndex: 1300,
                width: '100%',
                ml: 0,
                boxShadow: '0 1px 12px rgba(142,67,240,0.06)',
            }}
        >
            <Toolbar sx={{ gap: 1, px: { xs: 2, md: 3 }, minHeight: { xs: 56, md: 64 } }}>

                {/* ── Logo ─────────────────────────────── */}
                <Box
                    sx={{ display: 'flex', alignItems: 'center', gap: 1, mr: 2, cursor: 'pointer', flexShrink: 0 }}
                    onClick={() => navigate('/dashboard')}
                >
                    <Box sx={{
                        width: 30, height: 30, borderRadius: 1.5, flexShrink: 0,
                        background: 'linear-gradient(135deg,#8E43F0,#6A1FCC)',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        boxShadow: '0 3px 10px rgba(142,67,240,0.35)',
                    }}>
                        <Typography sx={{ color: '#fff', fontWeight: 800, fontSize: '0.85rem' }}>W</Typography>
                    </Box>
                    <Typography variant="h6" fontWeight={800} sx={{ color: '#1A0A3C', fontSize: '0.95rem', display: { xs: 'none', sm: 'block' } }}>
                        Webdezign
                    </Typography>
                </Box>

                {/* ── Centre: Nav groups ───────────────── */}
                <Box sx={{ display: { xs: 'none', md: 'flex' }, alignItems: 'center', gap: 0.25, flex: 1 }}>
                    {visibleGroups.map((group) => {
                        const active = isGroupActive(group);
                        if (!group.children) {
                            return (
                                <Button
                                    key={group.label}
                                    onClick={() => navigate(group.path)}
                                    sx={{
                                        color: active ? 'primary.main' : 'text.secondary',
                                        fontWeight: active ? 700 : 500,
                                        fontSize: '0.85rem',
                                        textTransform: 'none',
                                        borderRadius: 2,
                                        px: 1.5,
                                        borderBottom: active ? '2px solid' : '2px solid transparent',
                                        borderColor: active ? 'primary.main' : 'transparent',
                                        '&:hover': { bgcolor: 'rgba(142,67,240,0.06)', color: 'primary.main' },
                                    }}
                                >
                                    {group.label}
                                </Button>
                            );
                        }

                        const visibleChildren = group.children.filter(
                            (c) => !c.roles || c.roles.includes(user?.role)
                        );

                        return (
                            <Box key={group.label}>
                                <Button
                                    endIcon={<KeyboardArrowDown sx={{ fontSize: 16 }} />}
                                    onClick={(e) => openGroupMenu(group.label, e)}
                                    sx={{
                                        color: active ? 'primary.main' : 'text.secondary',
                                        fontWeight: active ? 700 : 500,
                                        fontSize: '0.85rem',
                                        textTransform: 'none',
                                        borderRadius: 2,
                                        px: 1.5,
                                        borderBottom: active ? '2px solid' : '2px solid transparent',
                                        borderColor: active ? 'primary.main' : 'transparent',
                                        '&:hover': { bgcolor: 'rgba(142,67,240,0.06)', color: 'primary.main' },
                                    }}
                                >
                                    {group.label}
                                </Button>
                                <Menu
                                    anchorEl={menuAnchors[group.label]}
                                    open={Boolean(menuAnchors[group.label])}
                                    onClose={() => closeGroupMenu(group.label)}
                                    PaperProps={{
                                        sx: {
                                            mt: 1, borderRadius: 2.5, boxShadow: '0 8px 32px rgba(142,67,240,0.14)',
                                            border: '1px solid #DDD4F8', minWidth: 180,
                                        },
                                    }}
                                    transformOrigin={{ horizontal: 'left', vertical: 'top' }}
                                    anchorOrigin={{ horizontal: 'left', vertical: 'bottom' }}
                                >
                                    {visibleChildren.map((child) => (
                                        <MenuItem
                                            key={child.path}
                                            onClick={() => { navigate(child.path); closeGroupMenu(group.label); }}
                                            selected={isActive(child.path)}
                                            sx={{
                                                borderRadius: 1.5, mx: 0.5, my: 0.25, fontSize: '0.875rem',
                                                '&.Mui-selected': { bgcolor: 'rgba(142,67,240,0.08)', color: 'primary.main', fontWeight: 600 },
                                                '&:hover': { bgcolor: 'rgba(142,67,240,0.06)' },
                                            }}
                                        >
                                            {child.label}
                                        </MenuItem>
                                    ))}
                                </Menu>
                            </Box>
                        );
                    })}
                </Box>

                <Box sx={{ flex: 1 }} />

                {/* ── Right: Notif + Avatar ─────────────── */}
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5, flexShrink: 0 }}>
                    <Tooltip title="Notifications">
                        <IconButton
                            onClick={(e) => setNotifAnchor(e.currentTarget)}
                            sx={{ color: 'text.secondary', '&:hover': { color: 'primary.main', bgcolor: 'rgba(142,67,240,0.07)' } }}
                        >
                            <Badge badgeContent={unread || null} color="error" max={9}
                                sx={{ '& .MuiBadge-badge': { fontSize: '0.6rem', minWidth: 16, height: 16, fontWeight: 700 } }}>
                                <NotificationsOutlined />
                            </Badge>
                        </IconButton>
                    </Tooltip>

                    {/* Notification dropdown */}
                    <Menu
                        anchorEl={notifAnchor} open={Boolean(notifAnchor)} onClose={() => setNotifAnchor(null)}
                        PaperProps={{
                            sx: {
                                mt: 1.5, width: 340, borderRadius: 3,
                                boxShadow: '0 8px 32px rgba(142,67,240,0.14)',
                                border: '1px solid #DDD4F8',
                            },
                        }}
                    >
                        <Box sx={{ px: 2, py: 1.5, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                            <Typography variant="subtitle2" fontWeight={700}>Notifications</Typography>
                            {unread > 0 && (
                                <Button size="small" onClick={() => markRead({ variables: {} })} sx={{ fontSize: '0.75rem' }}>
                                    Mark all read
                                </Button>
                            )}
                        </Box>
                        <Divider />
                        {notifications.slice(0, 5).map((n) => (
                            <MenuItem key={n.id} onClick={() => { markRead({ variables: { id: n.id } }); setNotifAnchor(null); }}
                                sx={{ py: 1.5, gap: 1.5, alignItems: 'flex-start', borderRadius: 0, opacity: n.read_at ? 0.65 : 1 }}>
                                <Box sx={{ flex: 1, minWidth: 0 }}>
                                    <Typography variant="body2" fontWeight={n.read_at ? 400 : 600} noWrap>{n.title}</Typography>
                                    <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }} noWrap>{n.message}</Typography>
                                </Box>
                                {!n.read_at && <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: 'primary.main', flexShrink: 0, mt: 0.5 }} />}
                            </MenuItem>
                        ))}
                        {notifications.length === 0 && (
                            <Box sx={{ py: 3, textAlign: 'center' }}>
                                <Typography variant="body2" color="text.secondary">No notifications</Typography>
                            </Box>
                        )}
                        <Divider />
                        <MenuItem onClick={() => { navigate('/notifications'); setNotifAnchor(null); }}
                            sx={{ justifyContent: 'center', color: 'primary.main', fontWeight: 600, fontSize: '0.85rem' }}>
                            View all notifications
                        </MenuItem>
                    </Menu>

                    {/* Avatar / user menu */}
                    <Tooltip title={user?.name ?? 'Account'}>
                        <IconButton onClick={(e) => setAnchorEl(e.currentTarget)} sx={{ p: 0.5 }}>
                            <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg,#8E43F0,#D4006A)' }}>
                                <Avatar sx={{ width: 32, height: 32, bgcolor: 'primary.main', fontSize: '0.75rem', fontWeight: 700, border: '2px solid #fff' }}>
                                    {initials}
                                </Avatar>
                            </Box>
                        </IconButton>
                    </Tooltip>

                    <Menu anchorEl={anchorEl} open={Boolean(anchorEl)} onClose={() => setAnchorEl(null)}
                        PaperProps={{ sx: { mt: 1.5, borderRadius: 2.5, boxShadow: '0 8px 32px rgba(142,67,240,0.14)', border: '1px solid #DDD4F8', minWidth: 200 } }}>
                        <Box sx={{ px: 2, py: 1.5 }}>
                            <Typography variant="body2" fontWeight={700}>{user?.name}</Typography>
                            <Typography variant="caption" color="text.secondary">{user?.email}</Typography>
                        </Box>
                        <Divider />
                        <MenuItem onClick={() => { navigate('/profile'); setAnchorEl(null); }} sx={{ gap: 1.5, borderRadius: 1, mx: 0.5 }}>
                            <AccountCircle fontSize="small" color="action" /> Profile
                        </MenuItem>
                        <MenuItem onClick={() => { navigate('/settings'); setAnchorEl(null); }} sx={{ gap: 1.5, borderRadius: 1, mx: 0.5 }}>
                            <Settings fontSize="small" color="action" /> Settings
                        </MenuItem>
                        <Divider />
                        <MenuItem onClick={handleLogout} sx={{ gap: 1.5, color: 'error.main', borderRadius: 1, mx: 0.5, mb: 0.5 }}>
                            <Logout fontSize="small" /> Sign out
                        </MenuItem>
                    </Menu>
                </Box>
            </Toolbar>
        </AppBar>
    );
}
