import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { ThemeProvider, CssBaseline } from '@mui/material';
import { SnackbarProvider } from 'notistack';
import theme from './theme/index';
import { AuthProvider } from './contexts/AuthContext';
import { ConfirmationProvider } from './context/ConfirmationContext';
import { LayoutProvider } from './contexts/LayoutContext';
import PrivateRoute from './components/saas/PrivateRoute';
import DashboardLayout from './layouts/DashboardLayout';

// ── New SaaS Auth pages ───────────────────────────────
import Login from './pages/auth/Login';
import Signup from './pages/auth/Signup';
import ForgotPassword from './pages/auth/ForgotPassword';
import ResetPassword from './pages/auth/ResetPassword';

// ── New SaaS Dashboard pages ──────────────────────────
import SaasDashboard from './pages/dashboard/Dashboard';
import Projects from './pages/projects/Projects';
import SaasClients from './pages/clients/Clients';
import Users from './pages/users/Users';
import Developers from './pages/developers/Developers';
import Tasks from './pages/tasks/Tasks';
import Reports from './pages/reports/Reports';
import SaasSettings from './pages/settings/Settings';
import Profile from './pages/profile/Profile';
import ProjectDetail from './pages/projects/ProjectDetail';
import ProjectCreate from './pages/projects/ProjectCreate';
import ProjectEdit from './pages/projects/ProjectEdit';
import ClientDetail from './pages/clients/ClientDetail';
import ClientCreate from './pages/clients/ClientCreate';
import ClientEdit from './pages/clients/ClientEdit';
import TaskDetail from './pages/tasks/TaskDetail';
import TaskCreate from './pages/tasks/TaskCreate';
import TaskEdit from './pages/tasks/TaskEdit';
import DeveloperDetail from './pages/developers/DeveloperDetail';
import UserCreate from './pages/users/UserCreate';
import UserEdit from './pages/users/UserEdit';
import DeveloperCreate from './pages/developers/DeveloperCreate';
import DeveloperEdit from './pages/developers/DeveloperEdit';
import Notifications from './pages/notifications/Notifications';

// ── Legacy WP Ops pages (now rendered inside DashboardLayout) ──
import OldDashboard from './pages/Dashboard';
import Sites from './pages/sites/Sites';
import AddSite from './pages/sites/AddSite';
import SiteDetail from './pages/sites/SiteDetail';
import EditSite from './pages/sites/EditSite';
import WorkQueue from './pages/WorkQueue';
import Findings from './pages/Findings';
import History from './pages/History';
import Security from './pages/Security';
import OldSettings from './pages/Settings';
import OldClients from './pages/Clients';
import NotFound from './pages/NotFound';

export default function App() {
  return (
    <SnackbarProvider maxSnack={3} anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }} autoHideDuration={3500}>
      <ThemeProvider theme={theme}>
        <ConfirmationProvider>
          <CssBaseline />
          <AuthProvider>
            <LayoutProvider>
              <BrowserRouter>
                <Routes>
                  {/* ── Public auth routes ── */}
                  <Route path="/login" element={<Login />} />
                  <Route path="/signup" element={<Signup />} />
                  <Route path="/forgot-password" element={<ForgotPassword />} />
                  <Route path="/reset-password" element={<ResetPassword />} />

                  {/* ── Protected routes (all inside the new DashboardLayout) ── */}
                  <Route element={<PrivateRoute><DashboardLayout /></PrivateRoute>}>

                    {/* Root → dashboard */}
                    <Route index element={<Navigate to="/dashboard" replace />} />

                    {/* ── New SaaS pages ── */}
                    <Route path="/dashboard" element={<SaasDashboard />} />
                    <Route path="/projects" element={<Projects />} />
                    <Route path="/projects/create" element={<PrivateRoute roles={['SuperAdmin']}><ProjectCreate /></PrivateRoute>} />
                    <Route path="/projects/:id" element={<ProjectDetail />} />
                    <Route path="/projects/:id/edit" element={<PrivateRoute roles={['SuperAdmin']}><ProjectEdit /></PrivateRoute>} />
                    <Route path="/tasks" element={<Tasks />} />
                    <Route path="/tasks/create" element={<PrivateRoute roles={['SuperAdmin', 'Developer']}><TaskCreate /></PrivateRoute>} />
                    <Route path="/tasks/:id" element={<TaskDetail />} />
                    <Route path="/tasks/:id/edit" element={<PrivateRoute roles={['SuperAdmin', 'Developer']}><TaskEdit /></PrivateRoute>} />
                    <Route path="/settings" element={<SaasSettings />} />
                    <Route path="/reports" element={<PrivateRoute roles={['SuperAdmin', 'Client']}><Reports /></PrivateRoute>} />

                    {/* SuperAdmin-only SaaS pages */}
                    <Route path="/clients" element={<PrivateRoute roles={['SuperAdmin', 'Developer']}><SaasClients /></PrivateRoute>} />
                    <Route path="/clients/create" element={<PrivateRoute roles={['SuperAdmin']}><ClientCreate /></PrivateRoute>} />
                    <Route path="/clients/:id" element={<PrivateRoute roles={['SuperAdmin', 'Developer']}><ClientDetail /></PrivateRoute>} />
                    <Route path="/clients/:id/edit" element={<PrivateRoute roles={['SuperAdmin']}><ClientEdit /></PrivateRoute>} />
                    <Route path="/users" element={<PrivateRoute roles={['SuperAdmin']}><Users /></PrivateRoute>} />
                    <Route path="/users/create" element={<PrivateRoute roles={['SuperAdmin']}><UserCreate /></PrivateRoute>} />
                    <Route path="/users/:id/edit" element={<PrivateRoute roles={['SuperAdmin']}><UserEdit /></PrivateRoute>} />
                    <Route path="/developers" element={<PrivateRoute roles={['SuperAdmin']}><Developers /></PrivateRoute>} />
                    <Route path="/developers/create" element={<PrivateRoute roles={['SuperAdmin']}><DeveloperCreate /></PrivateRoute>} />
                    <Route path="/developers/:id" element={<PrivateRoute roles={['SuperAdmin']}><DeveloperDetail /></PrivateRoute>} />
                    <Route path="/developers/:id/edit" element={<PrivateRoute roles={['SuperAdmin']}><DeveloperEdit /></PrivateRoute>} />
                    <Route path="/notifications" element={<PrivateRoute roles={['SuperAdmin', 'Developer', 'Client']}><Notifications /></PrivateRoute>} />

                    {/* ── Legacy WP Ops pages ── */}
                    <Route path="/sites" element={<Sites />} />
                    <Route path="/sites/new" element={<AddSite />} />
                    <Route path="/sites/:id" element={<SiteDetail />} />
                    <Route path="/sites/:id/edit" element={<EditSite />} />
                    <Route path="/workqueue" element={<WorkQueue />} />
                    <Route path="/findings" element={<Findings />} />
                    <Route path="/history" element={<History />} />
                    <Route path="/security" element={<Security />} />
                    <Route path="/wp/clients" element={<OldClients />} />
                    <Route path="/wp/settings" element={<OldSettings />} />
                    <Route path="/wp/dashboard" element={<OldDashboard />} />
                    <Route path="/profile" element={<Profile />} />
                  </Route>

                  {/* 404 — must be outside the protected layout so un-authed 404s work */}
                  <Route path="*" element={<NotFound />} />
                </Routes>
              </BrowserRouter>
            </LayoutProvider>
          </AuthProvider>
        </ConfirmationProvider>
      </ThemeProvider>
    </SnackbarProvider>
  );
}

