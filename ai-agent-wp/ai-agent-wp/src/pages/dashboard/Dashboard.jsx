import { useMemo } from 'react';
import {
  Box, Grid, Typography, Card, CardContent, Stack, Chip, Skeleton,
  Alert, LinearProgress, Button, Avatar, Divider,
} from '@mui/material';
import {
  AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, Legend,
} from 'recharts';
import {
  FolderOpen, People, Assignment, AttachMoney, TrendingUp,
  AddCircleOutline, TaskAlt, BarChart as BarChartIcon, PersonAddAlt,
  ArrowForward, CheckCircleOutline, RadioButtonUnchecked,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@apollo/client';
import StatsCard from '../../components/saas/StatsCard';
import PageHeader from '../../components/saas/PageHeader';
import StatusBadge from '../../components/saas/StatusBadge';
import { GET_DASHBOARD_STATS, GET_PROJECTS, GET_TASKS } from '../../graphql/queries';
import { fmtDate } from '../../utils/dates';

// ── Priority colour map ──────────────────────────────────────────
const priorityBorder = {
  Critical: '#DC2626',
  High: '#D97706',
  Medium: '#8E43F0',
  Low: '#16A34A',
};

// ── Custom chart tooltip ─────────────────────────────────────────
const CustomTooltip = ({ active, payload, label }) => {
  if (!active || !payload?.length) return null;
  return (
    <Box sx={{ bgcolor: '#fff', p: 1.5, borderRadius: 2, border: '1px solid #DDD4F8', boxShadow: '0 4px 20px rgba(142,67,240,0.12)' }}>
      <Typography variant="caption" fontWeight={700} sx={{ mb: 0.5, display: 'block' }}>{label}</Typography>
      {payload.map((p) => (
        <Box key={p.name} sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
          <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: p.color, flexShrink: 0 }} />
          <Typography variant="caption" color="text.secondary">{p.name}:</Typography>
          <Typography variant="caption" fontWeight={600}>
            {typeof p.value === 'number' && p.name === 'revenue' ? `£${p.value.toLocaleString()}` : p.value}
          </Typography>
        </Box>
      ))}
    </Box>
  );
};

// ── Skeletons ────────────────────────────────────────────────────
function StatsSkeleton() {
  return (
    <Grid container spacing={2.5} sx={{ mb: 3 }}>
      {[1, 2, 3, 4].map((i) => (
        <Grid item xs={6} lg={3} key={i}>
          <Card sx={{ borderRadius: 3, border: '1px solid #DDD4F8', boxShadow: 'none', p: 2.5 }}>
            <Skeleton variant="rounded" width={44} height={44} sx={{ mb: 1.5 }} />
            <Skeleton variant="text" width="50%" height={36} />
            <Skeleton variant="text" width="70%" />
          </Card>
        </Grid>
      ))}
    </Grid>
  );
}

function ChartSkeleton({ height = 260 }) {
  return <Skeleton variant="rounded" width="100%" height={height} sx={{ borderRadius: 3 }} />;
}

function ListSkeleton({ rows = 5 }) {
  return (
    <Stack spacing={1.5}>
      {Array.from({ length: rows }).map((_, i) => (
        <Box key={i} sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
          <Skeleton variant="circular" width={38} height={38} />
          <Box sx={{ flex: 1 }}>
            <Skeleton variant="text" width="60%" />
            <Skeleton variant="text" width="40%" />
          </Box>
          <Skeleton variant="rounded" width={60} height={22} />
        </Box>
      ))}
    </Stack>
  );
}

// ── Build chart data from real projects/tasks ────────────────────
function buildActivityData(projects, tasks) {
  const now = new Date();
  const months = ['Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
  return months.map((month, idx) => {
    const targetMonth = new Date(now.getFullYear(), now.getMonth() - (6 - idx), 1);
    const m = targetMonth.getMonth();
    const y = targetMonth.getFullYear();
    const projCount = projects.filter((p) => { const d = new Date(p.due_date); return d.getFullYear() <= y && d.getMonth() <= m; }).length;
    const taskCount = tasks.filter((t) => { const d = new Date(t.due_date); return d.getFullYear() <= y && d.getMonth() <= m; }).length;
    return { month, projects: projCount, tasks: taskCount };
  });
}

// ── Quick Actions ────────────────────────────────────────────────
const quickActions = [
  { label: 'New Project', icon: AddCircleOutline, path: '/projects/create', color: '#8E43F0', bg: '#EDE8FC' },
  { label: 'New Task', icon: TaskAlt, path: '/tasks/create', color: '#0099C2', bg: '#E0F5FB' },
  { label: 'View Reports', icon: BarChartIcon, path: '/reports', color: '#16A34A', bg: '#F0FDF4' },
  { label: 'Add Client', icon: PersonAddAlt, path: '/clients/create', color: '#D97706', bg: '#FFFBEB' },
];

// ── Main Dashboard ───────────────────────────────────────────────
export default function SaasDashboard() {
  const navigate = useNavigate();

  const { data: statsData, loading: statsLoading, error: statsError } = useQuery(GET_DASHBOARD_STATS, { fetchPolicy: 'cache-and-network' });
  const { data: projectsData, loading: projectsLoading, error: projectsError } = useQuery(GET_PROJECTS, { fetchPolicy: 'cache-and-network' });
  const { data: tasksData, loading: tasksLoading, error: tasksError } = useQuery(GET_TASKS, { fetchPolicy: 'cache-and-network' });

  const stats = statsData?.dashboardStats;
  const projects = projectsData?.projects ?? [];
  const tasks = tasksData?.tasks ?? [];

  const revenueData = useMemo(() => {
    if (!stats) return [];
    const base = stats.monthly_revenue / 7;
    return ['Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'].map((month, i) => ({
      month, revenue: Math.round(base * (0.5 + i * 0.1)),
    }));
  }, [stats]);

  const activityData = useMemo(() => buildActivityData(projects, tasks), [projects, tasks]);
  const anyError = statsError || projectsError || tasksError;
  const statsAreLoading = statsLoading && !stats;
  const today = new Date();

  return (
    <Box sx={{ pb: { xs: 10, md: 0 } }}>
      <PageHeader
        title="Dashboard"
        subtitle="Here's what's happening across your agency today."
        breadcrumbs={[{ label: 'Dashboard', path: '/dashboard' }]}
      />

      {anyError && (
        <Alert severity="error" sx={{ mb: 3, borderRadius: 2 }}>
          Failed to load dashboard data. Please refresh the page.
        </Alert>
      )}

      {/* ── KPI Stats row ── */}
      {statsAreLoading ? <StatsSkeleton /> : (
        <Grid container spacing={2.5} sx={{ mb: 3 }}>
          {[
            { title: 'Total Projects', value: stats?.total_projects ?? 0, icon: <FolderOpen />, color: 'primary', trend: 12, trendLabel: `${stats?.active_projects ?? 0} active` },
            { title: 'Total Clients', value: stats?.total_clients ?? 0, icon: <People />, color: 'success', trend: 8, trendLabel: 'vs last month' },
            { title: 'Open Tasks', value: stats?.open_tasks ?? 0, icon: <Assignment />, color: 'warning', trend: -3, trendLabel: 'vs last month' },
            { title: 'Monthly Revenue', value: `£${((stats?.monthly_revenue ?? 0) / 1000).toFixed(0)}k`, icon: <AttachMoney />, color: 'purple', trend: 18, trendLabel: 'vs last month' },
          ].map((card, idx) => (
            <Grid item xs={6} lg={3} key={card.title}
              sx={{
                animation: `fadeInUp 0.4s ease ${idx * 0.08}s both`,
                '@keyframes fadeInUp': {
                  from: { opacity: 0, transform: 'translateY(16px)' },
                  to: { opacity: 1, transform: 'translateY(0)' },
                },
              }}
            >
              <StatsCard {...card} />
            </Grid>
          ))}
        </Grid>
      )}

      {/* ── Quick Actions ── */}
      <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8', mb: 3 }}>
        <CardContent sx={{ p: 2.5 }}>
          <Typography variant="h6" fontWeight={700} sx={{ mb: 2, fontSize: '0.95rem', color: '#1A0A3C' }}>
            Quick Actions
          </Typography>
          <Grid container spacing={1.5}>
            {quickActions.map(({ label, icon: Icon, path, color, bg }) => (
              <Grid item xs={6} sm={3} key={label}>
                <Box
                  onClick={() => navigate(path)}
                  sx={{
                    display: 'flex', flexDirection: 'column', alignItems: 'center',
                    justifyContent: 'center', gap: 1,
                    p: 2, borderRadius: 3, cursor: 'pointer',
                    bgcolor: bg, border: `1px solid ${color}22`,
                    transition: 'all 0.2s',
                    '&:hover': {
                      transform: 'translateY(-2px)',
                      boxShadow: `0 6px 18px ${color}28`,
                      bgcolor: bg,
                    },
                  }}
                >
                  <Box sx={{
                    width: 40, height: 40, borderRadius: 2,
                    bgcolor: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center',
                    boxShadow: `0 2px 8px ${color}22`,
                  }}>
                    <Icon sx={{ color, fontSize: 20 }} />
                  </Box>
                  <Typography variant="caption" fontWeight={700} sx={{ color, textAlign: 'center', lineHeight: 1.2 }}>
                    {label}
                  </Typography>
                </Box>
              </Grid>
            ))}
          </Grid>
        </CardContent>
      </Card>

      {/* ── Charts row ── */}
      <Grid container spacing={2.5} sx={{ mb: 3 }}>
        {/* Project Activity */}
        <Grid item xs={12} lg={8}>
          <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8', height: '100%' }}>
            <CardContent sx={{ p: { xs: 2, sm: 3 } }}>
              <Box sx={{ mb: 2.5, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <Box>
                  <Typography variant="h6" fontWeight={700}>Project Activity</Typography>
                  <Typography variant="caption" color="text.secondary" sx={{ display: { xs: 'none', sm: 'block' } }}>Projects and tasks over the last 7 months</Typography>
                </Box>
                <Chip label="Last 7 months" size="small" sx={{ bgcolor: '#EDE8FC', color: '#8E43F0', fontWeight: 600 }} />
              </Box>
              {projectsLoading && !projectsData ? (
                <ChartSkeleton height={220} />
              ) : (
                <ResponsiveContainer width="100%" height={220}>
                  <AreaChart data={activityData} margin={{ top: 5, right: 5, left: -20, bottom: 0 }}>
                    <defs>
                      <linearGradient id="gradProj" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#8E43F0" stopOpacity={0.18} />
                        <stop offset="95%" stopColor="#8E43F0" stopOpacity={0} />
                      </linearGradient>
                      <linearGradient id="gradTask" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#0099C2" stopOpacity={0.18} />
                        <stop offset="95%" stopColor="#0099C2" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="#EDE8FC" />
                    <XAxis dataKey="month" tick={{ fontSize: 11, fill: '#9B89C4' }} axisLine={false} tickLine={false} />
                    <YAxis tick={{ fontSize: 11, fill: '#9B89C4' }} axisLine={false} tickLine={false} />
                    <Tooltip content={<CustomTooltip />} />
                    <Legend wrapperStyle={{ fontSize: 12 }} />
                    <Area type="monotone" dataKey="projects" name="Projects" stroke="#8E43F0" strokeWidth={2.5} fill="url(#gradProj)" dot={{ r: 3, fill: '#8E43F0' }} />
                    <Area type="monotone" dataKey="tasks" name="Tasks" stroke="#0099C2" strokeWidth={2.5} fill="url(#gradTask)" dot={{ r: 3, fill: '#0099C2' }} />
                  </AreaChart>
                </ResponsiveContainer>
              )}
            </CardContent>
          </Card>
        </Grid>

        {/* Revenue Chart */}
        <Grid item xs={12} lg={4}>
          <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8', height: '100%' }}>
            <CardContent sx={{ p: { xs: 2, sm: 3 } }}>
              <Typography variant="h6" fontWeight={700} sx={{ mb: 0.5 }}>Revenue</Typography>
              <Typography variant="caption" color="text.secondary" sx={{ display: { xs: 'none', sm: 'block' } }}>Monthly revenue trend</Typography>
              {statsAreLoading ? (
                <Box sx={{ mt: 2 }}>
                  <Skeleton variant="text" width="40%" height={48} />
                  <ChartSkeleton height={160} />
                </Box>
              ) : (
                <>
                  <Box sx={{ mt: 2 }}>
                    <Typography variant="h4" fontWeight={800} color="primary.main" sx={{ letterSpacing: '-0.02em' }}>
                      £{((stats?.monthly_revenue ?? 0) / 1000).toFixed(0)}k
                    </Typography>
                    <Stack direction="row" alignItems="center" spacing={0.5} sx={{ mb: 2 }}>
                      <TrendingUp sx={{ fontSize: 14, color: '#16A34A' }} />
                      <Typography variant="caption" color="success.main" fontWeight={600}>+18% vs last month</Typography>
                    </Stack>
                  </Box>
                  <ResponsiveContainer width="100%" height={160}>
                    <BarChart data={revenueData} margin={{ top: 0, right: 0, left: -25, bottom: 0 }}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#EDE8FC" vertical={false} />
                      <XAxis dataKey="month" tick={{ fontSize: 11, fill: '#9B89C4' }} axisLine={false} tickLine={false} />
                      <YAxis tick={{ fontSize: 11, fill: '#9B89C4' }} axisLine={false} tickLine={false} tickFormatter={(v) => `£${v / 1000}k`} />
                      <Tooltip content={<CustomTooltip />} />
                      <Bar dataKey="revenue" name="revenue" fill="#8E43F0" radius={[5, 5, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </>
              )}
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* ── Recent Projects + Tasks ── */}
      <Grid container spacing={2.5}>

        {/* Recent Projects */}
        <Grid item xs={12} lg={7}>
          <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8' }}>
            <CardContent sx={{ p: { xs: 2, sm: 3 } }}>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                <Typography variant="h6" fontWeight={700}>Recent Projects</Typography>
                <Chip
                  label={`${projects.length} total`} size="small"
                  sx={{ bgcolor: '#EDE8FC', color: '#8E43F0', fontWeight: 600 }}
                />
              </Box>

              {projectsLoading && !projectsData ? (
                <ListSkeleton rows={5} />
              ) : (
                <Stack spacing={1.5}>
                  {projects.slice(0, 5).map((p) => {
                    const isOverdue = p.due_date && new Date(p.due_date) < today;
                    return (
                      <Box key={p.id} sx={{
                        p: 2, borderRadius: 2.5,
                        bgcolor: '#F7F5FF', border: '1px solid #DDD4F8',
                        transition: 'all 0.15s',
                        '&:hover': { bgcolor: '#EDE8FC', borderColor: '#C4B0F0', transform: 'translateX(3px)' },
                      }}>
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 1 }}>
                          {/* Avatar with initials */}
                          <Avatar sx={{
                            width: 36, height: 36, fontSize: '0.72rem', fontWeight: 700,
                            bgcolor: 'primary.main', flexShrink: 0,
                          }}>
                            {p.name?.slice(0, 2).toUpperCase()}
                          </Avatar>
                          <Box sx={{ flex: 1, minWidth: 0 }}>
                            <Typography variant="body2" fontWeight={700} noWrap>{p.name}</Typography>
                            <Typography variant="caption" color="text.secondary">
                              {p.client?.name ?? '—'}
                              {p.due_date && (
                                <Box component="span" sx={{ color: isOverdue ? 'error.main' : 'text.secondary', ml: 0.5 }}>
                                  · Due {fmtDate(p.due_date)} {isOverdue && '⚠'}
                                </Box>
                              )}
                            </Typography>
                          </Box>
                          <Box sx={{ textAlign: 'right', flexShrink: 0, display: { xs: 'none', sm: 'block' } }}>
                            <Typography variant="caption" fontWeight={700} color="primary.main">
                              {p.progress}%
                            </Typography>
                            <Box sx={{ mt: 0.3 }}>
                              <StatusBadge value={p.status} />
                            </Box>
                          </Box>
                        </Box>
                        {/* Progress bar */}
                        <LinearProgress
                          variant="determinate"
                          value={p.progress ?? 0}
                          sx={{
                            height: 5, borderRadius: 3,
                            bgcolor: '#DDD4F8',
                            '& .MuiLinearProgress-bar': {
                              borderRadius: 3,
                              background: p.progress >= 100
                                ? 'linear-gradient(90deg,#16A34A,#22C55E)'
                                : 'linear-gradient(90deg,#8E43F0,#D4006A)',
                            },
                          }}
                        />
                      </Box>
                    );
                  })}
                </Stack>
              )}

              <Divider sx={{ my: 2, borderColor: '#EDE8FC' }} />
              <Button
                endIcon={<ArrowForward />}
                onClick={() => navigate('/projects')}
                size="small"
                sx={{ color: 'primary.main', fontWeight: 600, fontSize: '0.82rem' }}
              >
                View all projects
              </Button>
            </CardContent>
          </Card>
        </Grid>

        {/* Recent Tasks */}
        <Grid item xs={12} lg={5}>
          <Card elevation={0} sx={{ borderRadius: 3, border: '1px solid #DDD4F8', height: '100%' }}>
            <CardContent sx={{ p: { xs: 2, sm: 3 } }}>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                <Typography variant="h6" fontWeight={700}>Recent Tasks</Typography>
                <Chip
                  label={`${tasks.filter(t => t.status !== 'done').length} open`}
                  size="small"
                  sx={{ bgcolor: '#FFFBEB', color: '#D97706', fontWeight: 600 }}
                />
              </Box>

              {tasksLoading && !tasksData ? (
                <ListSkeleton rows={6} />
              ) : (
                <Stack spacing={1}>
                  {tasks.slice(0, 6).map((t) => {
                    const isOverdue = t.due_date && new Date(t.due_date) < today;
                    const borderColor = priorityBorder[t.priority] ?? '#8E43F0';
                    const isDone = t.status === 'done';
                    return (
                      <Box key={t.id} sx={{
                        display: 'flex', alignItems: 'flex-start', gap: 1.5,
                        p: 1.5, borderRadius: 2,
                        borderLeft: `3px solid ${borderColor}`,
                        bgcolor: isDone ? 'transparent' : `${borderColor}08`,
                        opacity: isDone ? 0.6 : 1,
                        transition: 'all 0.15s',
                        '&:hover': { bgcolor: `${borderColor}14` },
                      }}>
                        {isDone
                          ? <CheckCircleOutline sx={{ fontSize: 18, color: '#16A34A', mt: 0.1, flexShrink: 0 }} />
                          : <RadioButtonUnchecked sx={{ fontSize: 18, color: borderColor, mt: 0.1, flexShrink: 0 }} />
                        }
                        <Box sx={{ flex: 1, minWidth: 0 }}>
                          <Typography
                            variant="body2" fontWeight={isDone ? 400 : 600} noWrap
                            sx={{ textDecoration: isDone ? 'line-through' : 'none', color: isDone ? 'text.secondary' : 'text.primary' }}
                          >
                            {t.title}
                          </Typography>
                          <Typography variant="caption" color="text.secondary">
                            {t.project?.name ?? '—'}
                            {t.due_date && (
                              <Box component="span" sx={{ color: isOverdue && !isDone ? 'error.main' : 'text.secondary', ml: 0.5 }}>
                                · {fmtDate(t.due_date)}
                              </Box>
                            )}
                          </Typography>
                        </Box>
                        <StatusBadge value={t.status} />
                      </Box>
                    );
                  })}
                </Stack>
              )}

              <Divider sx={{ my: 2, borderColor: '#EDE8FC' }} />
              <Button
                endIcon={<ArrowForward />}
                onClick={() => navigate('/tasks')}
                size="small"
                sx={{ color: 'primary.main', fontWeight: 600, fontSize: '0.82rem' }}
              >
                View all tasks
              </Button>
            </CardContent>
          </Card>
        </Grid>
      </Grid>
    </Box>
  );
}
