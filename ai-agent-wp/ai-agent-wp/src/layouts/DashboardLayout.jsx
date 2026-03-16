import { useState } from 'react';
import { Box, Toolbar } from '@mui/material';
import { Outlet } from 'react-router-dom';
import SaasNavbar from '../components/saas/SaasNavbar';
import SaasSidebar, { DRAWER_WIDTH, MINI_DRAWER_WIDTH } from '../components/saas/SaasSidebar';
import SaasTopNav from '../components/saas/SaasTopNav';
import MobileBottomNav from '../components/saas/MobileBottomNav';
import { useLayout } from '../contexts/LayoutContext';

export default function DashboardLayout() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const { navLayout, collapsed } = useLayout();

  // Width of the sidebar to use for margin offsets
  const sidebarWidth = collapsed ? MINI_DRAWER_WIDTH : DRAWER_WIDTH;

  return (
    <Box sx={{ display: 'flex', minHeight: '100vh', bgcolor: '#F7F5FF' }}>

      {/* ── Left layout: sidebar + compact navbar ── */}
      {navLayout === 'left' && (
        <>
          <SaasNavbar onMenuClick={() => setMobileOpen(true)} collapsed={collapsed} sidebarWidth={sidebarWidth} />
          <SaasSidebar mobileOpen={mobileOpen} onMobileClose={() => setMobileOpen(false)} />
        </>
      )}

      {/* ── Top layout: full-width top nav, no sidebar ── */}
      {navLayout === 'top' && <SaasTopNav />}

      {/* Main content */}
      <Box
        component="main"
        sx={{
          flexGrow: 1,
          ml: navLayout === 'left'
            ? { xs: 0, md: `${MINI_DRAWER_WIDTH}px`, lg: `${sidebarWidth}px` }
            : 0,
          minHeight: '100vh',
          display: 'flex',
          flexDirection: 'column',
          transition: 'margin-left 0.25s ease',
        }}
      >
        <Toolbar /> {/* spacer for fixed AppBar */}
        <Box sx={{ flex: 1, p: { xs: 2, sm: 3 }, maxWidth: 1400, width: '100%', mx: 'auto', pb: { xs: '88px', md: 3 } }}>
          <Outlet />
        </Box>
      </Box>

      {/* Mobile bottom nav — only xs/sm */}
      <MobileBottomNav />
    </Box>
  );
}
