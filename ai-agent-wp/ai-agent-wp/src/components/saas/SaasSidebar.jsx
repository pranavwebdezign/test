import { useState } from 'react';
import { Link as RouterLink, useLocation, useNavigate } from 'react-router-dom';
import {
  Box, Drawer, List, ListItem, ListItemButton, ListItemIcon,
  ListItemText, Typography, Divider, Avatar, Chip, Collapse,
  Badge, Tooltip, IconButton, useMediaQuery, useTheme,
} from '@mui/material';
import {
  Dashboard, FolderOpen, People, Code, Assignment, Assessment,
  Settings, Language, ViewList, FindInPage, History, Security,
  ExpandLess, ExpandMore, AccountCircle, NotificationsOutlined,
  LogoutOutlined, ChevronLeft, ChevronRight,
} from '@mui/icons-material';
import { useAuth } from '../../contexts/AuthContext';
import { useLayout } from '../../contexts/LayoutContext';
import { useQuery } from '@apollo/client';
import { GET_NOTIFICATIONS } from '../../graphql/queries';

export const DRAWER_WIDTH = 260;
export const MINI_DRAWER_WIDTH = 72;

// ── Nav definitions ───────────────────────────────────────────────
const saasNav = [
  { label: 'Dashboard', path: '/dashboard', icon: Dashboard, roles: ['SuperAdmin', 'Developer', 'Client'] },
  { label: 'Projects', path: '/projects', icon: FolderOpen, roles: ['SuperAdmin', 'Developer', 'Client'] },
  { label: 'Clients', path: '/clients', icon: People, roles: ['SuperAdmin', 'Developer'] },
  { label: 'Developers', path: '/developers', icon: Code, roles: ['SuperAdmin'] },
  { label: 'Tasks', path: '/tasks', icon: Assignment, roles: ['SuperAdmin', 'Developer', 'Client'] },
  { label: 'Users', path: '/users', icon: People, roles: ['SuperAdmin'] },
  { label: 'Reports', path: '/reports', icon: Assessment, roles: ['SuperAdmin', 'Client'] },
  { label: 'Notifications', path: '/notifications', icon: NotificationsOutlined, roles: ['SuperAdmin', 'Developer', 'Client'] },
  { label: 'Profile', path: '/profile', icon: AccountCircle, roles: ['SuperAdmin', 'Developer', 'Client'] },
];

const wpNav = [
  { label: 'Sites', path: '/sites', icon: Language, roles: ['SuperAdmin', 'Developer'] },
  { label: 'Work Queue', path: '/workqueue', icon: ViewList, roles: ['SuperAdmin', 'Developer'] },
  { label: 'Findings', path: '/findings', icon: FindInPage, roles: ['SuperAdmin', 'Developer', 'Client'] },
  { label: 'History', path: '/history', icon: History, roles: ['SuperAdmin', 'Developer'] },
  { label: 'Security', path: '/security', icon: Security, roles: ['SuperAdmin', 'Developer'] },
];

const settingsNav = [
  { label: 'Settings', path: '/settings', icon: Settings, roles: ['SuperAdmin', 'Developer', 'Client'] },
];

const roleMeta = {
  SuperAdmin: { color: '#8E43F0', bg: '#EDE8FC', label: 'Super Admin' },
  Developer: { color: '#6A1FCC', bg: '#EDE8FC', label: 'Developer' },
  Client: { color: '#059669', bg: '#D1FAE5', label: 'Client' },
};

// ── Single nav link ─────────────────────────────────────────────────
function NavLink({ item, onClose, unreadCount, mini }) {
  const location = useLocation();
  const active = location.pathname === item.path || location.pathname.startsWith(item.path + '/');
  const Icon = item.icon;
  const isNotif = item.path === '/notifications';

  const iconEl = isNotif && unreadCount > 0 ? (
    <Badge badgeContent={unreadCount > 9 ? '9+' : unreadCount} color="error" max={99}
      sx={{ '& .MuiBadge-badge': { fontSize: '0.6rem', minWidth: 16, height: 16 } }}>
      <Icon sx={{ fontSize: 18 }} />
    </Badge>
  ) : (
    <Icon sx={{ fontSize: 18 }} />
  );

  const btn = (
    <ListItemButton
      component={RouterLink}
      to={item.path}
      onClick={onClose}
      selected={active}
      sx={{
        borderRadius: 2,
        py: 0.9,
        justifyContent: mini ? 'center' : 'flex-start',
        position: 'relative',
        transition: 'all 0.18s ease',
        color: active ? 'primary.main' : 'text.secondary',
        backgroundColor: active ? 'rgba(142,67,240,0.09)' : 'transparent',
        '&:hover': {
          backgroundColor: 'rgba(142,67,240,0.06)',
          color: 'primary.main',
          transform: mini ? 'none' : 'translateX(4px)',
        },
        '&.Mui-selected': { backgroundColor: 'rgba(142,67,240,0.09)', color: 'primary.main' },
        '&.Mui-selected::before': mini ? {} : {
          content: '""', position: 'absolute', left: 0, top: '20%',
          height: '60%', width: 3, borderRadius: 2, backgroundColor: '#8E43F0',
        },
      }}
    >
      <ListItemIcon sx={{ minWidth: mini ? 0 : 34, color: 'inherit', justifyContent: 'center' }}>
        {iconEl}
      </ListItemIcon>
      {!mini && (
        <ListItemText
          primary={item.label}
          primaryTypographyProps={{ fontSize: '0.855rem', fontWeight: active ? 600 : 400 }}
        />
      )}
    </ListItemButton>
  );

  return (
    <ListItem disablePadding sx={{ px: mini ? 0.5 : 1.5, mb: 0.25 }}>
      {mini ? (
        <Tooltip title={item.label} placement="right" arrow>
          {btn}
        </Tooltip>
      ) : btn}
    </ListItem>
  );
}

// ── Section with collapsible ──────────────────────────────────────
function NavSection({ title, items, role, onClose, collapsible, unreadCount, mini }) {
  const [open, setOpen] = useState(true);
  const visible = items.filter((n) => n.roles.includes(role));
  if (!visible.length) return null;

  return (
    <Box sx={{ mb: 1 }}>
      {!mini && (
        <Box
          sx={{
            px: 3, mb: 0.5, display: 'flex', alignItems: 'center',
            justifyContent: 'space-between',
            cursor: collapsible ? 'pointer' : 'default',
          }}
          onClick={collapsible ? () => setOpen((v) => !v) : undefined}
        >
          <Typography
            variant="overline"
            sx={{ color: 'text.disabled', fontWeight: 700, letterSpacing: '0.1em', fontSize: '0.63rem' }}
          >
            {title}
          </Typography>
          {collapsible && (open
            ? <ExpandLess sx={{ fontSize: 14, color: 'text.secondary' }} />
            : <ExpandMore sx={{ fontSize: 14, color: 'text.secondary' }} />)}
        </Box>
      )}
      {mini && !collapsible && <Divider sx={{ mx: 1, my: 0.5, borderColor: '#EDE8FC' }} />}
      <Collapse in={!collapsible || open}>
        <List disablePadding>
          {visible.map((item) => (
            <NavLink
              key={item.path}
              item={item}
              onClose={onClose}
              unreadCount={item.path === '/notifications' ? unreadCount : 0}
              mini={mini}
            />
          ))}
        </List>
      </Collapse>
    </Box>
  );
}

// ── Inner sidebar content ─────────────────────────────────────────
function SidebarContent({ onClose, mini: miniProp = false }) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const rm = roleMeta[user?.role] ?? roleMeta.Client;
  const { collapsed, toggleSidebar } = useLayout();

  // Fetch unread count for notification badge
  const { data } = useQuery(GET_NOTIFICATIONS, {
    fetchPolicy: 'cache-and-network',
    pollInterval: 60000,
  });
  const unreadCount = (data?.notifications ?? []).filter((n) => !n.read_at).length;

  const handleLogout = () => {
    onClose?.();
    logout();
    navigate('/login');
  };

  const initials = user?.name?.split(' ').map((n) => n[0]).join('').slice(0, 2) ?? '?';
  // collapsed state: from LayoutContext (manual toggle) or from mini prop (tablet)
  const mini = collapsed || miniProp;

  return (
    <Box sx={{ height: '100%', display: 'flex', flexDirection: 'column', bgcolor: '#fff', overflow: 'hidden' }}>

      {/* ── Logo + Collapse toggle ────────────────── */}
      <Box sx={{
        px: mini ? 0 : 3, py: 2.5,
        display: 'flex', alignItems: 'center',
        justifyContent: mini ? 'center' : 'space-between',
        gap: mini ? 0 : 1.5,
        borderBottom: '1px solid', borderColor: 'divider', flexShrink: 0,
      }}>
        <Box sx={{
          width: 36, height: 36, borderRadius: 2, flexShrink: 0,
          background: 'linear-gradient(135deg, #8E43F0 0%, #6A1FCC 100%)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          boxShadow: '0 4px 14px rgba(142,67,240,0.35)',
        }}>
          <Typography sx={{ color: '#fff', fontWeight: 800, fontSize: '1rem' }}>W</Typography>
        </Box>
        {!mini && (
          <Box sx={{ minWidth: 0, flex: 1 }}>
            <Typography variant="h6" fontWeight={700} sx={{ color: '#1A0A3C', lineHeight: 1, fontSize: '0.95rem' }}>
              Webdezign
            </Typography>
            <Typography variant="caption" color="text.secondary" sx={{ fontSize: '0.72rem' }}>
              Admin Panel
            </Typography>
          </Box>
        )}
        {/* Collapse toggle — only on desktop, only when not a mobile temp drawer */}
        {!miniProp && (
          <Tooltip title={mini ? 'Expand sidebar' : 'Collapse sidebar'} placement="right">
            <IconButton
              size="small" onClick={toggleSidebar}
              sx={{
                ml: mini ? 0 : 'auto',
                color: 'text.secondary',
                '&:hover': { color: 'primary.main', bgcolor: 'rgba(142,67,240,0.08)' },
              }}
            >
              {mini ? <ChevronRight sx={{ fontSize: 18 }} /> : <ChevronLeft sx={{ fontSize: 18 }} />}
            </IconButton>
          </Tooltip>
        )}
      </Box>

      {/* ── Scrollable nav ────────────────────────── */}
      <Box sx={{
        flex: 1, overflowY: 'auto', py: 1.5,
        '&::-webkit-scrollbar': { width: 4 },
        '&::-webkit-scrollbar-thumb': { bgcolor: '#DDD4F8', borderRadius: 4 },
      }}>
        <NavSection
          title="Agency" items={saasNav} role={user?.role}
          onClose={onClose} unreadCount={unreadCount} mini={mini}
        />
        {!mini && <Divider sx={{ mx: 2, my: 1, borderColor: '#EDE8FC' }} />}
        <NavSection
          title="WP Operations" items={wpNav} role={user?.role}
          onClose={onClose} collapsible unreadCount={0} mini={mini}
        />
      </Box>

      {/* ── Settings + User card ──────────────────── */}
      <Box sx={{ borderTop: '1px solid', borderColor: 'divider', flexShrink: 0, py: 0.5 }}>
        <List disablePadding sx={{ py: 0.5 }}>
          {settingsNav.filter((n) => n.roles.includes(user?.role)).map((item) => (
            <NavLink key={item.path} item={item} onClose={onClose} unreadCount={0} mini={mini} />
          ))}
        </List>

        {/* User profile card */}
        {mini ? (
          <Tooltip title={`${user?.name} — Sign out`} placement="right" arrow>
            <Box
              onClick={handleLogout}
              sx={{
                display: 'flex', justifyContent: 'center', pb: 1.5, cursor: 'pointer',
              }}
            >
              <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg,#8E43F0,#D4006A)' }}>
                <Avatar sx={{ width: 32, height: 32, bgcolor: rm.color, fontSize: '0.7rem', fontWeight: 700, border: '2px solid #fff' }}>
                  {initials}
                </Avatar>
              </Box>
            </Box>
          </Tooltip>
        ) : (
          <Box sx={{ px: 2, pb: 2, pt: 0.5 }}>
            <Box sx={{
              display: 'flex', alignItems: 'center', gap: 1.5,
              p: 1.5, borderRadius: 2.5,
              background: 'linear-gradient(135deg, #F7F5FF 0%, #EDE8FC 100%)',
              border: '1px solid #DDD4F8', position: 'relative',
            }}>
              <Box sx={{ p: '2px', borderRadius: '50%', background: 'linear-gradient(135deg, #8E43F0, #D4006A)', flexShrink: 0 }}>
                <Avatar sx={{ width: 34, height: 34, bgcolor: rm.color, fontSize: '0.72rem', fontWeight: 700, border: '2px solid #fff' }}>
                  {initials}
                </Avatar>
              </Box>
              <Box sx={{ flex: 1, minWidth: 0 }}>
                <Typography variant="body2" fontWeight={700} noWrap sx={{ fontSize: '0.8rem', color: '#1A0A3C' }}>
                  {user?.name}
                </Typography>
                <Chip label={rm.label} size="small"
                  sx={{ height: 16, fontSize: '0.6rem', fontWeight: 700, bgcolor: rm.bg, color: rm.color, mt: 0.3 }} />
              </Box>
              <Tooltip title="Sign out" placement="top">
                <IconButton size="small" onClick={handleLogout}
                  sx={{ color: 'text.secondary', flexShrink: 0, '&:hover': { color: 'error.main', bgcolor: 'rgba(204,34,34,0.08)' }, transition: 'all 0.15s' }}>
                  <LogoutOutlined sx={{ fontSize: 16 }} />
                </IconButton>
              </Tooltip>
            </Box>
          </Box>
        )}
      </Box>
    </Box>
  );
}

// ── Main export ───────────────────────────────────────────────────
export default function SaasSidebar({ mobileOpen, onMobileClose }) {
  const theme = useTheme();
  const isTablet = useMediaQuery(theme.breakpoints.between('md', 'lg'));
  const { collapsed } = useLayout();

  // mini = true when on tablet OR when user has manually collapsed
  const isMini = isTablet || collapsed;
  const drawerW = isMini ? MINI_DRAWER_WIDTH : DRAWER_WIDTH;

  return (
    <>
      {/* Mobile drawer — xs/sm only */}
      <Drawer
        variant="temporary"
        open={mobileOpen}
        onClose={onMobileClose}
        ModalProps={{ keepMounted: true }}
        sx={{
          display: { xs: 'block', md: 'none' },
          '& .MuiDrawer-paper': {
            width: DRAWER_WIDTH,
            boxSizing: 'border-box',
            boxShadow: '4px 0 24px rgba(142,67,240,0.12)',
          },
        }}
      >
        <SidebarContent onClose={onMobileClose} mini={false} />
      </Drawer>

      {/* Desktop / tablet permanent drawer */}
      <Drawer
        variant="permanent"
        sx={{
          display: { xs: 'none', md: 'block' },
          '& .MuiDrawer-paper': {
            width: drawerW,
            boxSizing: 'border-box',
            top: 0, height: '100%',
            borderRight: '1px solid #EDE8FC',
            boxShadow: 'none',
            overflowX: 'hidden',
            transition: 'width 0.25s ease',
          },
        }}
        open
      >
        <SidebarContent onClose={() => { }} mini={isTablet} />
      </Drawer>
    </>
  );
}

// DRAWER_WIDTH is already exported at declaration above
