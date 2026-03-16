import { useState } from 'react';
import {
  AppBar, Toolbar, IconButton, Typography, Box, Avatar, Badge,
  Menu, MenuItem, ListItemIcon, Divider, Tooltip, useTheme,
  CircularProgress, InputAdornment, TextField,
} from '@mui/material';
import {
  Menu as MenuIcon, NotificationsOutlined, Logout, Settings,
  Circle, DoneAll, OpenInNew, Search, AccountCircle,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@apollo/client';
import { useAuth } from '../../contexts/AuthContext';
import { GET_NOTIFICATIONS } from '../../graphql/queries';
import { MARK_NOTIFICATION_READ } from '../../graphql/mutations';
import { DRAWER_WIDTH, MINI_DRAWER_WIDTH } from './SaasSidebar';

// ── Notification type colour map ──────────────────────────────────
const notifTypeColor = {
  info: 'primary.main',
  success: 'success.main',
  warning: 'warning.main',
  error: 'error.main',
  task: '#8E43F0',
  project: '#0099C2',
};

function timeAgo(iso) {
  if (!iso) return '';
  const diff = (Date.now() - new Date(iso).getTime()) / 1000;
  if (diff < 60) return 'Just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  return `${Math.floor(diff / 86400)}d ago`;
}

export default function SaasNavbar({ onMenuClick, sidebarWidth = MINI_DRAWER_WIDTH }) {
  const theme = useTheme();
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const [anchorEl, setAnchorEl] = useState(null);
  const [notifAnchor, setNotifAnchor] = useState(null);
  const [search, setSearch] = useState('');

  // ── Notifications query (poll every 60s) ─────
  const { data, loading: loadingNotifs, refetch } = useQuery(GET_NOTIFICATIONS, {
    fetchPolicy: 'cache-and-network',
    pollInterval: 60000,
  });

  const notifications = data?.notifications ?? [];
  const unread = notifications.filter((n) => !n.read_at).length;

  const [markRead] = useMutation(MARK_NOTIFICATION_READ, {
    onCompleted: () => refetch(),
  });

  const handleMarkOne = (id) => markRead({ variables: { id } });
  const handleMarkAll = () => markRead({ variables: {} });

  const handleLogout = () => {
    setAnchorEl(null);
    logout();
    navigate('/login');
  };

  const initials = user?.name?.split(' ').map((n) => n[0]).join('').slice(0, 2) ?? '?';

  return (
    <AppBar
      position="fixed"
      elevation={0}
      sx={{
        bgcolor: 'rgba(255,255,255,0.88)',
        backdropFilter: 'blur(14px)',
        WebkitBackdropFilter: 'blur(14px)',
        borderBottom: '1px solid rgba(221,212,248,0.65)',
        color: 'text.primary',
        zIndex: theme.zIndex.drawer + 1,
        ml: { xs: 0, md: `${sidebarWidth}px` },
        width: { xs: '100%', md: `calc(100% - ${sidebarWidth}px)` },
        transition: 'margin-left 0.25s ease, width 0.25s ease',
        boxShadow: '0 1px 12px rgba(142,67,240,0.06)',
      }}
    >
      <Toolbar sx={{ justifyContent: 'space-between', px: { xs: 2, md: 3 }, gap: 2 }}>

        {/* ── Left: hamburger (mobile) + greeting ───────── */}
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexShrink: 0 }}>
          <IconButton onClick={onMenuClick} sx={{ display: { md: 'none' }, color: 'text.primary' }}>
            <MenuIcon />
          </IconButton>
          <Box sx={{ display: { xs: 'none', sm: 'flex' }, alignItems: 'center', gap: 0.5 }}>
            <Typography variant="caption" color="text.secondary">Welcome back,</Typography>
            <Typography variant="caption" fontWeight={700} color="primary.main">
              {user?.name?.split(' ')[0]}
            </Typography>
          </Box>
        </Box>

        {/* ── Centre: search bar ────────────────────────── */}
        <Box sx={{ flex: 1, maxWidth: { xs: 0, sm: 280 }, display: { xs: 'none', sm: 'block' } }}>
          <TextField
            size="small"
            placeholder="Search anything…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <Search sx={{ fontSize: 17, color: 'text.disabled' }} />
                </InputAdornment>
              ),
            }}
            sx={{
              width: '100%',
              '& .MuiOutlinedInput-root': {
                borderRadius: 3,
                fontSize: '0.85rem',
                bgcolor: '#F7F5FF',
                '& fieldset': { borderColor: '#DDD4F8' },
                '&:hover fieldset': { borderColor: 'primary.main' },
              },
            }}
          />
        </Box>

        {/* ── Right: notification + avatar ─────────────── */}
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5, flexShrink: 0 }}>

          {/* Notification bell */}
          <Tooltip title="Notifications">
            <IconButton
              onClick={(e) => setNotifAnchor(e.currentTarget)}
              sx={{ color: 'text.secondary', '&:hover': { color: 'primary.main', bgcolor: 'rgba(142,67,240,0.07)' } }}
            >
              <Badge
                badgeContent={unread || null}
                color="error"
                max={9}
                sx={{ '& .MuiBadge-badge': { fontSize: '0.6rem', minWidth: 16, height: 16, fontWeight: 700 } }}
              >
                <NotificationsOutlined />
              </Badge>
            </IconButton>
          </Tooltip>

          {/* Avatar button → user menu */}
          <Tooltip title={user?.name ?? 'Account'}>
            <IconButton onClick={(e) => setAnchorEl(e.currentTarget)} sx={{ p: 0.5 }}>
              <Box sx={{
                p: '2px', borderRadius: '50%',
                background: 'linear-gradient(135deg, #8E43F0, #D4006A)',
              }}>
                <Avatar sx={{
                  width: 32, height: 32, bgcolor: 'primary.main',
                  fontSize: '0.75rem', fontWeight: 700,
                  border: '2px solid #fff',
                }}>
                  {initials}
                </Avatar>
              </Box>
            </IconButton>
          </Tooltip>
        </Box>
      </Toolbar>

      {/* ── Notification dropdown ─────────────────────────────────── */}
      <Menu
        anchorEl={notifAnchor}
        open={Boolean(notifAnchor)}
        onClose={() => setNotifAnchor(null)}
        PaperProps={{
          sx: {
            width: 370, maxHeight: 480, borderRadius: 3, mt: 1,
            boxShadow: '0 8px 36px rgba(142,67,240,0.16)',
            border: '1px solid #EDE8FC',
          },
        }}
        transformOrigin={{ horizontal: 'right', vertical: 'top' }}
        anchorOrigin={{ horizontal: 'right', vertical: 'bottom' }}
      >
        {/* Header */}
        <Box sx={{ px: 2, py: 1.5, borderBottom: '1px solid #EDE8FC', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <Box>
            <Typography fontWeight={700} fontSize="0.95rem">Notifications</Typography>
            <Typography variant="caption" color="text.secondary">
              {loadingNotifs ? 'Loading…' : `${unread} unread`}
            </Typography>
          </Box>
          {unread > 0 && (
            <Tooltip title="Mark all as read">
              <IconButton size="small" onClick={handleMarkAll} sx={{ color: 'primary.main' }}>
                <DoneAll fontSize="small" />
              </IconButton>
            </Tooltip>
          )}
        </Box>

        {/* List */}
        {loadingNotifs ? (
          <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
            <CircularProgress size={24} />
          </Box>
        ) : notifications.length === 0 ? (
          <Box sx={{ py: 5, textAlign: 'center' }}>
            <NotificationsOutlined sx={{ fontSize: 36, color: 'text.disabled', mb: 1 }} />
            <Typography variant="body2" color="text.secondary">No notifications yet</Typography>
          </Box>
        ) : (
          notifications.slice(0, 8).map((n) => {
            const isUnread = !n.read_at;
            return (
              <MenuItem
                key={n.id}
                onClick={() => { handleMarkOne(n.id); setNotifAnchor(null); }}
                sx={{
                  py: 1.5, gap: 1.5, alignItems: 'flex-start',
                  opacity: isUnread ? 1 : 0.6,
                  bgcolor: isUnread ? 'rgba(142,67,240,0.035)' : 'transparent',
                  borderLeft: isUnread ? '3px solid' : '3px solid transparent',
                  borderColor: isUnread ? 'primary.main' : 'transparent',
                  transition: 'all 0.15s',
                  '&:hover': { bgcolor: 'rgba(142,67,240,0.07)' },
                }}
              >
                <Circle sx={{ fontSize: 8, color: notifTypeColor[n.type] ?? 'text.secondary', mt: 0.8, flexShrink: 0 }} />
                <Box sx={{ flex: 1, minWidth: 0 }}>
                  <Typography variant="body2" fontWeight={isUnread ? 600 : 400} noWrap>
                    {n.message}
                  </Typography>
                  <Typography variant="caption" color="text.secondary">{timeAgo(n.created_at)}</Typography>
                </Box>
              </MenuItem>
            );
          })
        )}

        {/* Footer */}
        <Box sx={{ px: 2, py: 1.2, borderTop: '1px solid #EDE8FC', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          {unread > 0 && (
            <Typography
              variant="caption" color="primary.main" fontWeight={600}
              sx={{ cursor: 'pointer', '&:hover': { textDecoration: 'underline' } }}
              onClick={handleMarkAll}
            >
              Mark all as read
            </Typography>
          )}
          <Typography
            variant="caption" color="primary.main" fontWeight={600}
            sx={{ display: 'flex', alignItems: 'center', gap: 0.5, ml: 'auto', cursor: 'pointer', '&:hover': { textDecoration: 'underline' } }}
            onClick={() => { setNotifAnchor(null); navigate('/notifications'); }}
          >
            View all <OpenInNew sx={{ fontSize: 11 }} />
          </Typography>
        </Box>
      </Menu>

      {/* ── User / avatar menu ────────────────────────────────────── */}
      <Menu
        anchorEl={anchorEl}
        open={Boolean(anchorEl)}
        onClose={() => setAnchorEl(null)}
        PaperProps={{
          sx: {
            width: 230, borderRadius: 3, mt: 1,
            boxShadow: '0 8px 36px rgba(142,67,240,0.16)',
            border: '1px solid #EDE8FC',
          },
        }}
        transformOrigin={{ horizontal: 'right', vertical: 'top' }}
        anchorOrigin={{ horizontal: 'right', vertical: 'bottom' }}
      >
        {/* User info header */}
        <Box sx={{ px: 2, py: 1.5 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.5 }}>
            <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg, #8E43F0, #D4006A)', flexShrink: 0 }}>
              <Avatar sx={{ width: 36, height: 36, bgcolor: 'primary.main', fontSize: '0.8rem', fontWeight: 700, border: '2px solid #fff' }}>
                {initials}
              </Avatar>
            </Box>
            <Box sx={{ minWidth: 0 }}>
              <Typography fontWeight={700} variant="body2" noWrap>{user?.name}</Typography>
              <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                {user?.email}
              </Typography>
            </Box>
          </Box>
        </Box>
        <Divider sx={{ borderColor: '#EDE8FC' }} />

        <MenuItem onClick={() => { setAnchorEl(null); navigate('/profile'); }}
          sx={{ py: 1.2, '&:hover': { bgcolor: 'rgba(142,67,240,0.06)' } }}>
          <ListItemIcon><AccountCircle fontSize="small" sx={{ color: 'primary.main' }} /></ListItemIcon>
          <Typography variant="body2" fontWeight={500}>My Profile</Typography>
        </MenuItem>

        <MenuItem onClick={() => { setAnchorEl(null); navigate('/settings'); }}
          sx={{ py: 1.2, '&:hover': { bgcolor: 'rgba(142,67,240,0.06)' } }}>
          <ListItemIcon><Settings fontSize="small" sx={{ color: 'text.secondary' }} /></ListItemIcon>
          <Typography variant="body2" fontWeight={500}>Settings</Typography>
        </MenuItem>

        <Divider sx={{ borderColor: '#EDE8FC' }} />

        <MenuItem onClick={handleLogout}
          sx={{ py: 1.2, color: 'error.main', '&:hover': { bgcolor: 'rgba(204,34,34,0.06)' } }}>
          <ListItemIcon><Logout fontSize="small" sx={{ color: 'error.main' }} /></ListItemIcon>
          <Typography variant="body2" fontWeight={600}>Sign Out</Typography>
        </MenuItem>
      </Menu>
    </AppBar>
  );
}
