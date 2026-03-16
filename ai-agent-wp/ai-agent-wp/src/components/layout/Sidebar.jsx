import { Link, useLocation } from 'react-router-dom';
import {
  Box, Drawer, List, ListItem, ListItemButton,
  ListItemIcon, ListItemText, Typography, Divider, Avatar, IconButton,
} from '@mui/material';
import {
  Dashboard, Public, List as ListIcon, ReportProblem,
  People, History, Security, Settings, Menu,
} from '@mui/icons-material';

const SIDEBAR_WIDTH = 240;

const navGroups = [
  {
    label: 'MAIN',
    items: [
      { path: '/', label: 'Dashboard', Icon: Dashboard },
      { path: '/sites', label: 'Sites', Icon: Public },
      { path: '/work-queue', label: 'Work Queue', Icon: ListIcon },
      { path: '/findings', label: 'Findings', Icon: ReportProblem },
    ],
  },
  {
    label: 'MANAGE',
    items: [
      { path: '/clients', label: 'Clients', Icon: People },
      { path: '/history', label: 'Check History', Icon: History },
      { path: '/security', label: 'Security', Icon: Security },
      { path: '/settings', label: 'Settings', Icon: Settings },
    ],
  },
];

function SidebarContent({ location, onClose }) {
  return (
    <Box
      sx={{
        width: SIDEBAR_WIDTH,
        height: '100%',
        display: 'flex',
        flexDirection: 'column',
        background: '#001529',
        color: '#fff',
      }}
    >
      {/* Logo */}
      <Box sx={{ height: 64, display: 'flex', alignItems: 'center', justifyContent: 'center', borderBottom: '1px solid rgba(255,255,255,0.1)', gap: 1 }}>
        <Public sx={{ color: '#1890ff', fontSize: 26 }} />
        <Typography variant="h6" fontWeight={700} sx={{ color: '#fff' }}>WP Ops</Typography>
      </Box>

      {/* Nav */}
      <Box sx={{ flex: 1, overflow: 'auto', pt: 1 }}>
        {navGroups.map((group, gi) => (
          <Box key={gi}>
            <Typography
              variant="caption"
              sx={{ px: 2, py: 1, display: 'block', color: 'rgba(255,255,255,0.35)', fontWeight: 600, letterSpacing: 1 }}
            >
              {group.label}
            </Typography>
            <List dense disablePadding>
              {group.items.map(({ path, label, Icon }) => {
                const active = location.pathname === path;
                return (
                  <ListItem key={path} disablePadding>
                    <ListItemButton
                      component={Link}
                      to={path}
                      onClick={onClose}
                      sx={{
                        mx: 1,
                        borderRadius: 1,
                        mb: 0.5,
                        color: active ? '#fff' : 'rgba(255,255,255,0.65)',
                        background: active ? '#1890ff' : 'transparent',
                        '&:hover': {
                          background: active ? '#1890ff' : 'rgba(255,255,255,0.08)',
                          color: '#fff',
                        },
                      }}
                    >
                      <ListItemIcon sx={{ minWidth: 36, color: 'inherit' }}>
                        <Icon fontSize="small" />
                      </ListItemIcon>
                      <ListItemText primary={label} primaryTypographyProps={{ fontSize: 14 }} />
                    </ListItemButton>
                  </ListItem>
                );
              })}
            </List>
            {gi < navGroups.length - 1 && <Box sx={{ my: 1 }} />}
          </Box>
        ))}
      </Box>

      {/* User */}
      <Box sx={{ p: 2, borderTop: '1px solid rgba(255,255,255,0.1)', display: 'flex', alignItems: 'center', gap: 1.5 }}>
        <Avatar sx={{ width: 32, height: 32, bgcolor: '#1890ff', fontSize: 12, fontWeight: 700 }}>JD</Avatar>
        <Box>
          <Typography variant="body2" sx={{ color: '#fff', fontWeight: 500, lineHeight: 1.2 }}>John Doe</Typography>
          <Typography variant="caption" sx={{ color: 'rgba(255,255,255,0.45)' }}>Admin</Typography>
        </Box>
      </Box>
    </Box>
  );
}

export default function Sidebar({ isMobile, mobileOpen, onMobileClose, onMobileOpen }) {
  const location = useLocation();

  const drawerContent = <SidebarContent location={location} onClose={onMobileClose} />;

  if (isMobile) {
    return (
      <>
        {/* Hamburger button shown in top-left on mobile */}
        <Box
          sx={{
            position: 'fixed',
            top: 12,
            left: 12,
            zIndex: 1300,
            display: { md: 'none' },
          }}
        >
          <IconButton
            onClick={onMobileOpen}
            sx={{ background: '#001529', color: '#fff', '&:hover': { background: '#0a2540' } }}
            size="small"
          >
            <Menu />
          </IconButton>
        </Box>
        <Drawer
          variant="temporary"
          open={mobileOpen}
          onClose={onMobileClose}
          ModalProps={{ keepMounted: true }}
          PaperProps={{ sx: { width: SIDEBAR_WIDTH, border: 'none' } }}
        >
          {drawerContent}
        </Drawer>
      </>
    );
  }

  return (
    <Drawer
      variant="permanent"
      PaperProps={{
        sx: {
          width: SIDEBAR_WIDTH,
          border: 'none',
          boxShadow: '2px 0 8px rgba(0,0,0,0.15)',
        },
      }}
    >
      {drawerContent}
    </Drawer>
  );
}
